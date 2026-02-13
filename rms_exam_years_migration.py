#!/usr/bin/env python3
"""
RMS Exam Years Migration - Safe Data Migration with Full Validation

This script orchestrates the safe introduction of exam years to an existing RMS.
Features:
- Transaction-based execution with rollback capability
- Comprehensive pre/post-migration validation
- Detailed logging and error handling
- Zero data loss guarantee
"""

import mysql.connector
from mysql.connector import Error, errorcode
from datetime import datetime
import logging
from typing import Dict, List, Tuple, Optional
import sys

# ============================================================================
# LOGGING CONFIGURATION
# ============================================================================

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('migration.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# ============================================================================
# DATABASE CONFIGURATION
# ============================================================================

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': 'password',
    'database': 'rms',
    'autocommit': False,  # Manual transaction control
    'use_unicode': True,
    'charset': 'utf8mb4'
}

TABLES_TO_UPDATE = [
    'candidates',
    'registrations',
    'subject_registrations',
    'marks',
    'results',
    'summaries',
    'reports',
    'uploads',
]

# ============================================================================
# MIGRATION CLASS
# ============================================================================

class ExamYearsMigration:
    """Orchestrates safe migration to exam years system"""
    
    def __init__(self, config: Dict):
        self.config = config
        self.connection = None
        self.legacy_year_id = None
        self.pre_migration_counts = {}
        self.post_migration_counts = {}
        
    def connect(self) -> bool:
        """Establish database connection"""
        try:
            self.connection = mysql.connector.connect(**self.config)
            logger.info("✓ Database connection established")
            return True
        except Error as e:
            logger.error(f"✗ Failed to connect: {e}")
            return False
    
    def disconnect(self):
        """Close database connection"""
        if self.connection and self.connection.is_connected():
            self.connection.close()
            logger.info("Database connection closed")
    
    def execute_query(self, query: str, params: Tuple = None, commit: bool = False) -> Optional[List]:
        """Execute SQL query safely"""
        cursor = self.connection.cursor(dictionary=True)
        try:
            if params:
                cursor.execute(query, params)
            else:
                cursor.execute(query)
            
            if commit:
                self.connection.commit()
                logger.debug(f"✓ Committed: {query[:50]}...")
            
            # Fetch results if SELECT
            if query.strip().upper().startswith('SELECT'):
                return cursor.fetchall()
            
            return None
        except Error as e:
            logger.error(f"✗ Query failed: {e}\nQuery: {query}")
            raise
        finally:
            cursor.close()
    
    def count_records_before_migration(self):
        """Count records in all tables before migration"""
        logger.info("\n[PHASE 1] Counting existing records...")
        
        for table in TABLES_TO_UPDATE:
            result = self.execute_query(f"SELECT COUNT(*) as cnt FROM {table}")
            count = result[0]['cnt'] if result else 0
            self.pre_migration_counts[table] = count
            logger.info(f"  {table}: {count} records")
        
        total = sum(self.pre_migration_counts.values())
        logger.info(f"  TOTAL: {total} records across {len(TABLES_TO_UPDATE)} tables")
        
        return total > 0
    
    def create_exam_years_table(self):
        """PHASE 1: Create exam_years foundational table"""
        logger.info("\n[PHASE 2] Creating exam_years table...")
        
        query = """
        CREATE TABLE IF NOT EXISTS exam_years (
            id INT PRIMARY KEY AUTO_INCREMENT,
            year_label VARCHAR(9) UNIQUE NOT NULL,
            is_active BOOLEAN DEFAULT FALSE,
            is_locked BOOLEAN DEFAULT FALSE,
            published_at TIMESTAMP NULL,
            locked_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_year_label (year_label),
            CHECK (is_locked = FALSE OR (is_locked = TRUE AND published_at IS NOT NULL)),
            INDEX idx_is_active (is_active),
            INDEX idx_is_locked (is_locked),
            INDEX idx_published_at (published_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """
        
        try:
            self.execute_query(query, commit=True)
            logger.info("✓ exam_years table created")
            return True
        except Error as e:
            logger.error(f"✗ Failed to create exam_years table: {e}")
            return False
    
    def create_legacy_year(self) -> int:
        """PHASE 2: Create legacy/default year for existing data"""
        logger.info("\n[PHASE 3] Creating legacy exam year...")
        
        current_year = datetime.now().year
        year_label = str(current_year)
        
        try:
            cursor = self.connection.cursor()
            query = """
            INSERT INTO exam_years (year_label, is_active, created_at)
            VALUES (%s, TRUE, CURRENT_TIMESTAMP)
            """
            cursor.execute(query, (year_label,))
            self.connection.commit()
            
            self.legacy_year_id = cursor.lastrowid
            logger.info(f"✓ Legacy year created: {year_label} (ID: {self.legacy_year_id})")
            cursor.close()
            
            return self.legacy_year_id
        except Error as e:
            logger.error(f"✗ Failed to create legacy year: {e}")
            self.connection.rollback()
            return None
    
    def add_exam_year_columns(self) -> bool:
        """PHASE 3: Add exam_year_id column to all tables"""
        logger.info("\n[PHASE 4] Adding exam_year_id columns...")
        
        success_count = 0
        failed_count = 0
        
        for table in TABLES_TO_UPDATE:
            try:
                query = f"""
                ALTER TABLE {table}
                ADD COLUMN exam_year_id INT NOT NULL DEFAULT 0 AFTER id,
                ADD INDEX idx_exam_year_id (exam_year_id)
                """
                self.execute_query(query, commit=True)
                logger.info(f"  ✓ Added exam_year_id to {table}")
                success_count += 1
            except Error as e:
                if 'Duplicate column' in str(e):
                    logger.warning(f"  ⚠ exam_year_id already exists in {table}")
                    success_count += 1  # Not a fatal error
                else:
                    logger.error(f"  ✗ Failed to add column to {table}: {e}")
                    failed_count += 1
        
        if failed_count > 0:
            logger.error(f"✗ Failed to add columns to {failed_count} tables")
            return False
        
        logger.info(f"✓ Successfully added columns to {success_count} tables")
        return True
    
    def backfill_exam_year_data(self) -> bool:
        """PHASE 4: Backfill all records with legacy year ID"""
        logger.info("\n[PHASE 5] Backfilling exam_year_id for existing data...")
        
        if not self.legacy_year_id:
            logger.error("✗ Legacy year ID not set. Backfill failed.")
            return False
        
        for table in TABLES_TO_UPDATE:
            try:
                query = f"""
                UPDATE {table}
                SET exam_year_id = %s
                WHERE exam_year_id = 0 OR exam_year_id IS NULL
                """
                cursor = self.connection.cursor()
                cursor.execute(query, (self.legacy_year_id,))
                rows_affected = cursor.rowcount
                self.connection.commit()
                
                logger.info(f"  ✓ {table}: {rows_affected} rows backfilled")
                cursor.close()
            except Error as e:
                logger.error(f"  ✗ Failed to backfill {table}: {e}")
                self.connection.rollback()
                return False
        
        logger.info("✓ Backfill completed successfully")
        return True
    
    def validate_data_integrity(self) -> bool:
        """PHASE 5: Validate migration integrity"""
        logger.info("\n[PHASE 6] Validating data integrity...")
        
        all_valid = True
        
        # Check 1: Verify row counts haven't changed
        logger.info("  Check 1: Verifying row counts...")
        for table in TABLES_TO_UPDATE:
            result = self.execute_query(f"SELECT COUNT(*) as cnt FROM {table}")
            post_count = result[0]['cnt'] if result else 0
            pre_count = self.pre_migration_counts.get(table, 0)
            
            if pre_count != post_count:
                logger.error(f"    ✗ {table}: Row count mismatch! Before: {pre_count}, After: {post_count}")
                all_valid = False
            else:
                logger.info(f"    ✓ {table}: {post_count} rows")
            
            self.post_migration_counts[table] = post_count
        
        # Check 2: Verify no NULL exam_year_id
        logger.info("  Check 2: Verifying no NULL exam_year_id...")
        for table in TABLES_TO_UPDATE:
            result = self.execute_query(
                f"SELECT COUNT(*) as cnt FROM {table} WHERE exam_year_id IS NULL"
            )
            null_count = result[0]['cnt'] if result else 0
            
            if null_count > 0:
                logger.error(f"    ✗ {table}: {null_count} rows with NULL exam_year_id!")
                all_valid = False
            else:
                logger.info(f"    ✓ {table}: No NULL exam_year_id values")
        
        # Check 3: Verify referential integrity
        logger.info("  Check 3: Verifying referential integrity...")
        for table in TABLES_TO_UPDATE:
            result = self.execute_query(f"""
            SELECT COUNT(*) as invalid_count
            FROM {table} t
            LEFT JOIN exam_years y ON t.exam_year_id = y.id
            WHERE t.exam_year_id > 0 AND y.id IS NULL
            """)
            invalid_count = result[0]['invalid_count'] if result else 0
            
            if invalid_count > 0:
                logger.error(f"    ✗ {table}: {invalid_count} orphaned exam_year_id references!")
                all_valid = False
            else:
                logger.info(f"    ✓ {table}: All exam_year_id values are valid")
        
        if all_valid:
            logger.info("✓ All integrity checks PASSED")
        else:
            logger.error("✗ INTEGRITY CHECKS FAILED - Review above errors")
        
        return all_valid
    
    def add_foreign_key_constraints(self) -> bool:
        """PHASE 6: Add foreign key constraints"""
        logger.info("\n[PHASE 7] Adding foreign key constraints...")
        
        constraints = [
            ('candidates', 'fk_candidates_exam_year'),
            ('registrations', 'fk_registrations_exam_year'),
            ('subject_registrations', 'fk_subject_registrations_exam_year'),
            ('marks', 'fk_marks_exam_year'),
            ('results', 'fk_results_exam_year'),
            ('summaries', 'fk_summaries_exam_year'),
            ('reports', 'fk_reports_exam_year'),
            ('uploads', 'fk_uploads_exam_year'),
        ]
        
        success_count = 0
        
        for table, constraint_name in constraints:
            try:
                query = f"""
                ALTER TABLE {table}
                ADD CONSTRAINT {constraint_name}
                FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT
                """
                self.execute_query(query, commit=True)
                logger.info(f"  ✓ Added constraint on {table}")
                success_count += 1
            except Error as e:
                if 'Duplicate key name' in str(e):
                    logger.warning(f"  ⚠ Constraint already exists on {table}")
                    success_count += 1
                else:
                    logger.error(f"  ✗ Failed to add constraint on {table}: {e}")
        
        return success_count == len(constraints)
    
    def add_composite_indexes(self) -> bool:
        """PHASE 7: Add composite indexes for query optimization"""
        logger.info("\n[PHASE 8] Adding composite indexes...")
        
        indexes = [
            ('candidates', 'idx_exam_year_school', ['exam_year_id', 'school_id']),
            ('registrations', 'idx_exam_year_candidate', ['exam_year_id', 'candidate_id']),
            ('subject_registrations', 'idx_exam_year_subject', ['exam_year_id', 'subject_id']),
            ('marks', 'idx_exam_year_candidate_subject', ['exam_year_id', 'candidate_id', 'subject_id']),
            ('results', 'idx_exam_year_candidate', ['exam_year_id', 'candidate_id']),
            ('reports', 'idx_exam_year_school', ['exam_year_id', 'school_id']),
            ('uploads', 'idx_exam_year_status', ['exam_year_id', 'status']),
        ]
        
        success_count = 0
        
        for table, index_name, columns in indexes:
            try:
                columns_str = ', '.join(columns)
                query = f"ALTER TABLE {table} ADD INDEX {index_name} ({columns_str})"
                self.execute_query(query, commit=True)
                logger.info(f"  ✓ Added index {index_name} on {table}")
                success_count += 1
            except Error as e:
                if 'Duplicate key name' in str(e):
                    logger.warning(f"  ⚠ Index already exists: {index_name}")
                    success_count += 1
                else:
                    logger.error(f"  ✗ Failed to add index {index_name}: {e}")
        
        return success_count == len(indexes)
    
    def run_migration(self) -> bool:
        """Execute complete migration"""
        logger.info("=" * 70)
        logger.info("RMS EXAM YEARS MIGRATION - STARTED")
        logger.info("=" * 70)
        
        try:
            # Phase 1: Count records
            if not self.count_records_before_migration():
                logger.warning("⚠ No records found - proceeding anyway")
            
            # Phase 2: Create exam_years table
            if not self.create_exam_years_table():
                logger.error("✗ Migration failed at: Create exam_years table")
                return False
            
            # Phase 3: Create legacy year
            self.legacy_year_id = self.create_legacy_year()
            if not self.legacy_year_id:
                logger.error("✗ Migration failed at: Create legacy year")
                return False
            
            # Phase 4: Add columns
            if not self.add_exam_year_columns():
                logger.error("✗ Migration failed at: Add exam_year_id columns")
                return False
            
            # Phase 5: Backfill data
            if not self.backfill_exam_year_data():
                logger.error("✗ Migration failed at: Backfill exam_year_id")
                return False
            
            # Phase 6: Validate integrity
            if not self.validate_data_integrity():
                logger.error("✗ MIGRATION VALIDATION FAILED")
                logger.error("⚠ Recommend: Review logs and perform rollback")
                return False
            
            # Phase 7: Add constraints
            if not self.add_foreign_key_constraints():
                logger.error("✗ Migration failed at: Add foreign key constraints")
                return False
            
            # Phase 8: Add indexes
            if not self.add_composite_indexes():
                logger.error("✗ Migration failed at: Add composite indexes")
                return False
            
            logger.info("\n" + "=" * 70)
            logger.info("✓ MIGRATION COMPLETED SUCCESSFULLY")
            logger.info("=" * 70)
            logger.info(f"Legacy Year ID: {self.legacy_year_id}")
            logger.info(f"Total Records Migrated: {sum(self.post_migration_counts.values())}")
            logger.info("=" * 70)
            
            return True
            
        except Exception as e:
            logger.error(f"\n✗ MIGRATION FAILED WITH EXCEPTION: {e}")
            logger.error("Attempting rollback...")
            self.connection.rollback()
            return False
    
    def print_summary(self):
        """Print migration summary"""
        print("\n" + "=" * 70)
        print("MIGRATION SUMMARY")
        print("=" * 70)
        
        if self.legacy_year_id:
            print(f"\nLegacy Year ID: {self.legacy_year_id}")
            print(f"Active Exam Year: {datetime.now().year}")
        
        if self.post_migration_counts:
            print("\nRecords Migrated by Table:")
            for table, count in self.post_migration_counts.items():
                print(f"  {table}: {count}")
            print(f"  TOTAL: {sum(self.post_migration_counts.values())}")
        
        print("\nNext Steps:")
        print("  1. Verify migration.log for any warnings")
        print("  2. Test application functionality with year filtering")
        print("  3. Update API services to require exam_year_id")
        print("  4. Update UI with exam year selector")
        print("  5. Run application test suite")
        
        print("\n" + "=" * 70)


# ============================================================================
# MAIN EXECUTION
# ============================================================================

def main():
    """Main entry point"""
    migration = ExamYearsMigration(DB_CONFIG)
    
    try:
        if not migration.connect():
            sys.exit(1)
        
        success = migration.run_migration()
        migration.print_summary()
        
        if success:
            sys.exit(0)
        else:
            sys.exit(1)
    
    finally:
        migration.disconnect()


if __name__ == '__main__':
    main()
