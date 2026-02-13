# Result Management System - Exam Years Architecture & Implementation

## Executive Summary

This document outlines a comprehensive system to introduce exam year management to a Python-based RMS (Result Management System). The implementation ensures:

- ✓ Centralized academic year management
- ✓ Strict multi-year data isolation
- ✓ Year locking after publication (immutability)
- ✓ Safe data migration from legacy systems
- ✓ Zero data loss
- ✓ ACID-compliant constraints

---

## PART 1: FOUNDATIONAL LAYER - EXAM YEARS TABLE

### Database Schema

```sql
CREATE TABLE exam_years (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique exam year identifier',
    year_label VARCHAR(9) UNIQUE NOT NULL COMMENT 'Academic year label (e.g., "2024", "2023-2024")',
    is_active BOOLEAN DEFAULT FALSE COMMENT 'Only one year can be active at a time',
    is_locked BOOLEAN DEFAULT FALSE COMMENT 'Locked years are read-only',
    published_at TIMESTAMP NULL COMMENT 'When results were published for this year',
    locked_at TIMESTAMP NULL COMMENT 'When this year was locked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints
    CONSTRAINT chk_only_one_active CHECK (
        (SELECT COUNT(*) FROM exam_years WHERE is_active = TRUE) <= 1
    ),
    CONSTRAINT chk_locked_published CHECK (
        is_locked = FALSE OR (is_locked = TRUE AND published_at IS NOT NULL)
    ),
    
    -- Indexes
    INDEX idx_is_active (is_active),
    INDEX idx_is_locked (is_locked),
    INDEX idx_year_label (year_label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Centralized academic year management with isolation and locking';
```

### SQL Stored Procedure: Enforce Single Active Year

```sql
DELIMITER //

CREATE PROCEDURE deactivate_all_other_years(IN p_year_id INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Deactivate all other years
    UPDATE exam_years 
    SET is_active = FALSE, updated_at = CURRENT_TIMESTAMP
    WHERE id != p_year_id AND is_active = TRUE;
    
    -- Activate target year
    UPDATE exam_years 
    SET is_active = TRUE, updated_at = CURRENT_TIMESTAMP
    WHERE id = p_year_id;
    
    COMMIT;
END //

DELIMITER ;
```

### SQL Trigger: Lock Year After Publication

```sql
DELIMITER //

CREATE TRIGGER lock_year_after_publication
BEFORE UPDATE ON exam_years
FOR EACH ROW
BEGIN
    -- If published_at is being set for first time, prepare for locking
    IF NEW.published_at IS NOT NULL AND OLD.published_at IS NULL THEN
        SET NEW.locked_at = CURRENT_TIMESTAMP;
        SET NEW.is_locked = TRUE;
    END IF;
    
    -- Prevent unlocking
    IF OLD.is_locked = TRUE AND NEW.is_locked = FALSE THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot unlock a published exam year';
    END IF;
END //

DELIMITER ;
```

---

## PART 2: MULTI-YEAR RESULT ISOLATION

### Tables to Modify (Add exam_year_id)

All exam-related tables must include `exam_year_id` with constraints:

#### 1. **candidates** table
```sql
ALTER TABLE candidates 
ADD COLUMN exam_year_id INT NOT NULL AFTER id,
ADD CONSTRAINT fk_candidates_exam_year 
    FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT,
ADD INDEX idx_exam_year_school (exam_year_id, school_id),
ADD UNIQUE KEY uq_candidate_year (candidate_id, exam_year_id);
```

#### 2. **registrations** table
```sql
ALTER TABLE registrations 
ADD COLUMN exam_year_id INT NOT NULL AFTER id,
ADD CONSTRAINT fk_registrations_exam_year 
    FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT,
ADD INDEX idx_exam_year_candidate (exam_year_id, candidate_id),
ADD UNIQUE KEY uq_registration_year (registration_number, exam_year_id);
```

#### 3. **subject_registrations** table
```sql
ALTER TABLE subject_registrations 
ADD COLUMN exam_year_id INT NOT NULL AFTER id,
ADD CONSTRAINT fk_subject_registrations_exam_year 
    FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT,
ADD INDEX idx_exam_year_subject (exam_year_id, subject_id),
ADD INDEX idx_exam_year_registration (exam_year_id, registration_id);
```

#### 4. **marks** table
```sql
ALTER TABLE marks 
ADD COLUMN exam_year_id INT NOT NULL AFTER id,
ADD CONSTRAINT fk_marks_exam_year 
    FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT,
ADD INDEX idx_exam_year_subject (exam_year_id, subject_id),
ADD INDEX idx_exam_year_candidate (exam_year_id, candidate_id);
```

#### 5. **results** table
```sql
ALTER TABLE results 
ADD COLUMN exam_year_id INT NOT NULL AFTER id,
ADD CONSTRAINT fk_results_exam_year 
    FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT,
ADD INDEX idx_exam_year_candidate (exam_year_id, candidate_id),
ADD UNIQUE KEY uq_result_candidate_year (candidate_id, exam_year_id);
```

#### 6. **summaries** table
```sql
ALTER TABLE summaries 
ADD COLUMN exam_year_id INT NOT NULL AFTER id,
ADD CONSTRAINT fk_summaries_exam_year 
    FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT,
ADD INDEX idx_exam_year (exam_year_id);
```

#### 7. **reports** table
```sql
ALTER TABLE reports 
ADD COLUMN exam_year_id INT NOT NULL AFTER id,
ADD CONSTRAINT fk_reports_exam_year 
    FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT,
ADD INDEX idx_exam_year_school (exam_year_id, school_id);
```

#### 8. **uploads** table
```sql
ALTER TABLE uploads 
ADD COLUMN exam_year_id INT NOT NULL AFTER id,
ADD CONSTRAINT fk_uploads_exam_year 
    FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT,
ADD INDEX idx_exam_year_status (exam_year_id, status);
```

#### 9. **csv_templates** table
```sql
ALTER TABLE csv_templates 
ADD COLUMN exam_year_id INT NOT NULL AFTER id,
ADD CONSTRAINT fk_csv_templates_exam_year 
    FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT,
ADD INDEX idx_exam_year_school_subject (exam_year_id, school_id, subject_id);
```

#### 10. **moderation_outputs** table
```sql
ALTER TABLE moderation_outputs 
ADD COLUMN exam_year_id INT NOT NULL AFTER id,
ADD CONSTRAINT fk_moderation_outputs_exam_year 
    FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT,
ADD INDEX idx_exam_year_status (exam_year_id, status);
```

### Write Protection Trigger

```sql
DELIMITER //

CREATE TRIGGER prevent_write_to_locked_years
BEFORE INSERT ON marks
FOR EACH ROW
BEGIN
    DECLARE v_is_locked BOOLEAN;
    SELECT is_locked INTO v_is_locked FROM exam_years WHERE id = NEW.exam_year_id;
    
    IF v_is_locked = TRUE THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot write to locked exam year';
    END IF;
END //

-- Repeat for: UPDATE, DELETE, INSERT operations on all exam tables
-- Example for UPDATE:
CREATE TRIGGER prevent_update_to_locked_years
BEFORE UPDATE ON marks
FOR EACH ROW
BEGIN
    DECLARE v_is_locked BOOLEAN;
    SELECT is_locked INTO v_is_locked FROM exam_years WHERE id = NEW.exam_year_id;
    
    IF v_is_locked = TRUE THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot write to locked exam year';
    END IF;
END //

DELIMITER ;
```

---

## PART 3: PYTHON SERVICE LAYER REFACTORING

### Base Service Class with Year Validation

```python
# services/base_service.py
from abc import ABC
from typing import Optional, Dict, Any
import mysql.connector
from mysql.connector import Error

class ExamYearRequiredError(Exception):
    """Raised when exam_year_id is missing or invalid"""
    pass

class YearLockedError(Exception):
    """Raised when attempting to write to a locked year"""
    pass

class BaseService(ABC):
    """
    Base service class enforcing exam year as first-class domain entity.
    All methods MUST validate exam_year_id explicitly.
    """
    
    def __init__(self, db_config: Dict[str, Any]):
        self.db_config = db_config
        self.connection = None
    
    def connect(self):
        """Establish database connection"""
        try:
            self.connection = mysql.connector.connect(**self.db_config)
        except Error as e:
            raise RuntimeError(f"Database connection failed: {e}")
    
    def disconnect(self):
        """Close database connection"""
        if self.connection and self.connection.is_connected():
            self.connection.close()
    
    def _validate_exam_year(self, exam_year_id: int) -> bool:
        """
        Validate that exam_year_id exists and get its status.
        
        Args:
            exam_year_id: The exam year to validate
            
        Returns:
            True if valid, raises exception otherwise
            
        Raises:
            ExamYearRequiredError: If exam_year_id is None or invalid
        """
        if exam_year_id is None:
            raise ExamYearRequiredError("exam_year_id is required")
        
        if not isinstance(exam_year_id, int):
            raise ExamYearRequiredError(f"exam_year_id must be integer, got {type(exam_year_id)}")
        
        cursor = self.connection.cursor(dictionary=True)
        try:
            query = "SELECT id, is_locked, is_active FROM exam_years WHERE id = %s"
            cursor.execute(query, (exam_year_id,))
            result = cursor.fetchone()
            
            if not result:
                raise ExamYearRequiredError(f"Exam year {exam_year_id} does not exist")
            
            return True
        finally:
            cursor.close()
    
    def _check_year_not_locked(self, exam_year_id: int) -> None:
        """
        Check if year is locked before write operations.
        
        Args:
            exam_year_id: The exam year to check
            
        Raises:
            YearLockedError: If year is locked
        """
        cursor = self.connection.cursor(dictionary=True)
        try:
            query = "SELECT is_locked FROM exam_years WHERE id = %s"
            cursor.execute(query, (exam_year_id,))
            result = cursor.fetchone()
            
            if result and result['is_locked']:
                raise YearLockedError(f"Exam year {exam_year_id} is locked and read-only")
        finally:
            cursor.close()
    
    def get_active_exam_year(self) -> Dict[str, Any]:
        """Get currently active exam year"""
        cursor = self.connection.cursor(dictionary=True)
        try:
            query = "SELECT id, year_label, is_locked FROM exam_years WHERE is_active = TRUE LIMIT 1"
            cursor.execute(query)
            return cursor.fetchone()
        finally:
            cursor.close()
    
    def set_active_exam_year(self, exam_year_id: int) -> bool:
        """
        Set the active exam year.
        Deactivates all others (one-at-a-time constraint).
        """
        self._validate_exam_year(exam_year_id)
        
        cursor = self.connection.cursor()
        try:
            # Use stored procedure to enforce single active year
            cursor.callproc('deactivate_all_other_years', [exam_year_id])
            self.connection.commit()
            return True
        except Error as e:
            self.connection.rollback()
            raise RuntimeError(f"Failed to set active year: {e}")
        finally:
            cursor.close()
```

### Candidate Service with Year Isolation

```python
# services/candidate_service.py
from typing import List, Dict, Any, Optional
from .base_service import BaseService, ExamYearRequiredError, YearLockedError

class CandidateService(BaseService):
    """Service for candidate management with exam year isolation"""
    
    def get_registered_candidates(
        self, 
        subject_id: int, 
        school_id: int,
        exam_year_id: int
    ) -> List[Dict[str, Any]]:
        """
        Get candidates registered for a subject in a specific exam year.
        
        Args:
            subject_id: Subject ID
            school_id: School ID
            exam_year_id: MANDATORY - Exam year ID
            
        Returns:
            List of candidate records
            
        Raises:
            ExamYearRequiredError: If exam_year_id is missing
        """
        self._validate_exam_year(exam_year_id)
        
        cursor = self.connection.cursor(dictionary=True)
        try:
            query = """
            SELECT 
                c.id,
                c.candidate_id,
                c.full_name,
                c.gender,
                c.school_id,
                c.exam_year_id,
                sr.subject_id
            FROM candidates c
            INNER JOIN subject_registrations sr 
                ON c.id = sr.candidate_id 
                AND c.exam_year_id = sr.exam_year_id
            WHERE 
                sr.subject_id = %s
                AND c.school_id = %s
                AND c.exam_year_id = %s
                AND c.is_active = TRUE
            ORDER BY c.candidate_id
            """
            cursor.execute(query, (subject_id, school_id, exam_year_id))
            return cursor.fetchall()
        finally:
            cursor.close()
    
    def create_candidate(
        self,
        candidate_data: Dict[str, Any],
        exam_year_id: int
    ) -> int:
        """
        Create a new candidate for a specific exam year.
        
        Args:
            candidate_data: Dictionary with candidate details
            exam_year_id: MANDATORY - Exam year ID
            
        Returns:
            Newly created candidate ID
            
        Raises:
            ExamYearRequiredError: If exam_year_id is missing
            YearLockedError: If year is locked
        """
        self._validate_exam_year(exam_year_id)
        self._check_year_not_locked(exam_year_id)
        
        cursor = self.connection.cursor()
        try:
            query = """
            INSERT INTO candidates (
                candidate_id, full_name, gender, school_id, exam_year_id, is_active
            ) VALUES (%s, %s, %s, %s, %s, TRUE)
            """
            cursor.execute(query, (
                candidate_data['candidate_id'],
                candidate_data['full_name'],
                candidate_data['gender'],
                candidate_data['school_id'],
                exam_year_id
            ))
            self.connection.commit()
            return cursor.lastrowid
        except Exception as e:
            self.connection.rollback()
            raise RuntimeError(f"Failed to create candidate: {e}")
        finally:
            cursor.close()
    
    def update_candidate(
        self,
        candidate_id: int,
        candidate_data: Dict[str, Any],
        exam_year_id: int
    ) -> bool:
        """Update candidate information"""
        self._validate_exam_year(exam_year_id)
        self._check_year_not_locked(exam_year_id)
        
        cursor = self.connection.cursor()
        try:
            query = """
            UPDATE candidates
            SET full_name = %s, gender = %s, updated_at = CURRENT_TIMESTAMP
            WHERE id = %s AND exam_year_id = %s
            """
            cursor.execute(query, (
                candidate_data.get('full_name'),
                candidate_data.get('gender'),
                candidate_id,
                exam_year_id
            ))
            self.connection.commit()
            return cursor.rowcount > 0
        except Exception as e:
            self.connection.rollback()
            raise RuntimeError(f"Failed to update candidate: {e}")
        finally:
            cursor.close()
    
    def delete_candidate(
        self,
        candidate_id: int,
        exam_year_id: int
    ) -> bool:
        """Delete candidate (marks as inactive for audit)"""
        self._validate_exam_year(exam_year_id)
        self._check_year_not_locked(exam_year_id)
        
        cursor = self.connection.cursor()
        try:
            # Soft delete for audit trail
            query = """
            UPDATE candidates
            SET is_active = FALSE, updated_at = CURRENT_TIMESTAMP
            WHERE id = %s AND exam_year_id = %s
            """
            cursor.execute(query, (candidate_id, exam_year_id))
            self.connection.commit()
            return cursor.rowcount > 0
        except Exception as e:
            self.connection.rollback()
            raise RuntimeError(f"Failed to delete candidate: {e}")
        finally:
            cursor.close()
    
    def get_candidates_by_year(self, exam_year_id: int) -> List[Dict[str, Any]]:
        """Get all candidates in a specific exam year"""
        self._validate_exam_year(exam_year_id)
        
        cursor = self.connection.cursor(dictionary=True)
        try:
            query = """
            SELECT *
            FROM candidates
            WHERE exam_year_id = %s AND is_active = TRUE
            ORDER BY candidate_id
            """
            cursor.execute(query, (exam_year_id,))
            return cursor.fetchall()
        finally:
            cursor.close()
```

### Marks Service with Year Isolation and Locking

```python
# services/marks_service.py
from typing import Dict, Any, List
from .base_service import BaseService, YearLockedError

class MarksService(BaseService):
    """Service for marks management with year isolation and locking"""
    
    def submit_marks(
        self,
        candidate_id: int,
        subject_id: int,
        marks_data: Dict[str, float],
        exam_year_id: int
    ) -> int:
        """
        Submit marks for a candidate in a subject.
        
        Args:
            candidate_id: Candidate ID
            subject_id: Subject ID
            marks_data: Dictionary with paper_1, paper_2, etc.
            exam_year_id: MANDATORY - Exam year ID
            
        Returns:
            Mark record ID
            
        Raises:
            YearLockedError: If year is locked
        """
        self._validate_exam_year(exam_year_id)
        self._check_year_not_locked(exam_year_id)
        
        cursor = self.connection.cursor()
        try:
            query = """
            INSERT INTO marks (
                candidate_id,
                subject_id,
                exam_year_id,
                paper_1,
                paper_2,
                paper_3,
                submitted_at
            ) VALUES (%s, %s, %s, %s, %s, %s, CURRENT_TIMESTAMP)
            """
            cursor.execute(query, (
                candidate_id,
                subject_id,
                exam_year_id,
                marks_data.get('paper_1'),
                marks_data.get('paper_2'),
                marks_data.get('paper_3')
            ))
            self.connection.commit()
            return cursor.lastrowid
        except Exception as e:
            self.connection.rollback()
            raise RuntimeError(f"Failed to submit marks: {e}")
        finally:
            cursor.close()
    
    def get_marks_by_candidate_and_year(
        self,
        candidate_id: int,
        exam_year_id: int
    ) -> List[Dict[str, Any]]:
        """Get all marks for a candidate in a specific year"""
        self._validate_exam_year(exam_year_id)
        
        cursor = self.connection.cursor(dictionary=True)
        try:
            query = """
            SELECT *
            FROM marks
            WHERE candidate_id = %s AND exam_year_id = %s
            ORDER BY subject_id
            """
            cursor.execute(query, (candidate_id, exam_year_id))
            return cursor.fetchall()
        finally:
            cursor.close()
    
    def update_marks(
        self,
        mark_id: int,
        marks_data: Dict[str, float],
        exam_year_id: int
    ) -> bool:
        """Update marks for a candidate"""
        self._validate_exam_year(exam_year_id)
        self._check_year_not_locked(exam_year_id)
        
        cursor = self.connection.cursor()
        try:
            query = """
            UPDATE marks
            SET 
                paper_1 = %s,
                paper_2 = %s,
                paper_3 = %s,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = %s AND exam_year_id = %s
            """
            cursor.execute(query, (
                marks_data.get('paper_1'),
                marks_data.get('paper_2'),
                marks_data.get('paper_3'),
                mark_id,
                exam_year_id
            ))
            self.connection.commit()
            return cursor.rowcount > 0
        except Exception as e:
            self.connection.rollback()
            raise RuntimeError(f"Failed to update marks: {e}")
        finally:
            cursor.close()
    
    def publish_results(self, exam_year_id: int) -> bool:
        """
        Publish results for a year (triggers locking).
        
        After publication:
        - Year is locked
        - No further writes allowed
        - Read-only mode enforced
        """
        self._validate_exam_year(exam_year_id)
        
        cursor = self.connection.cursor()
        try:
            # Update exam_year to mark as published
            # Trigger will automatically lock it
            query = """
            UPDATE exam_years
            SET published_at = CURRENT_TIMESTAMP
            WHERE id = %s AND is_locked = FALSE
            """
            cursor.execute(query, (exam_year_id,))
            self.connection.commit()
            
            if cursor.rowcount == 0:
                return False  # Already published
            
            return True
        except Exception as e:
            self.connection.rollback()
            raise RuntimeError(f"Failed to publish results: {e}")
        finally:
            cursor.close()
    
    def get_year_lock_status(self, exam_year_id: int) -> Dict[str, Any]:
        """Get lock status of an exam year"""
        self._validate_exam_year(exam_year_id)
        
        cursor = self.connection.cursor(dictionary=True)
        try:
            query = """
            SELECT id, year_label, is_locked, is_active, published_at, locked_at
            FROM exam_years
            WHERE id = %s
            """
            cursor.execute(query, (exam_year_id,))
            return cursor.fetchone()
        finally:
            cursor.close()
```

### CSV Template Service with Year Integration

```python
# services/csv_template_service.py
from typing import Dict, Any
from .base_service import BaseService

class CsvTemplateService(BaseService):
    """Generate CSV templates with year isolation"""
    
    def generate_csv_template(
        self,
        subject_id: int,
        school_id: int,
        exam_year_id: int
    ) -> Dict[str, Any]:
        """
        Generate CSV template for mark entry.
        
        Template structure embeds exam_year_id as metadata.
        Filename includes exam year for clarity.
        
        Args:
            subject_id: Subject ID
            school_id: School ID
            exam_year_id: MANDATORY - Exam year ID
            
        Returns:
            Dictionary with template metadata and CSV content
        """
        self._validate_exam_year(exam_year_id)
        
        # Get exam year label for filename
        year_info = self.get_active_exam_year()
        if not year_info or year_info['id'] != exam_year_id:
            cursor = self.connection.cursor(dictionary=True)
            try:
                query = "SELECT year_label FROM exam_years WHERE id = %s"
                cursor.execute(query, (exam_year_id,))
                year_info = cursor.fetchone()
            finally:
                cursor.close()
        
        # Get eligible candidates for this school/subject/year
        cursor = self.connection.cursor(dictionary=True)
        try:
            query = """
            SELECT c.candidate_id, c.full_name
            FROM candidates c
            INNER JOIN subject_registrations sr 
                ON c.id = sr.candidate_id
            WHERE 
                c.school_id = %s
                AND sr.subject_id = %s
                AND c.exam_year_id = %s
                AND c.is_active = TRUE
            ORDER BY c.candidate_id
            """
            cursor.execute(query, (school_id, subject_id, exam_year_id))
            candidates = cursor.fetchall()
        finally:
            cursor.close()
        
        # Build CSV content
        csv_lines = ['index_number,sex,paper_p1,paper_p2,paper_p3']
        for candidate in candidates:
            csv_lines.append(f"{candidate['candidate_id']},,,,")
        
        # Filename pattern: SCHOOL_SUBJECT_YEAR.csv
        filename = f"marks_{school_id}_{subject_id}_{exam_year_id}.csv"
        
        return {
            'filename': filename,
            'exam_year_id': exam_year_id,
            'year_label': year_info['year_label'],
            'candidate_count': len(candidates),
            'content': '\n'.join(csv_lines),
            'metadata': {
                'school_id': school_id,
                'subject_id': subject_id,
                'exam_year_id': exam_year_id
            }
        }
    
    def validate_csv_upload(
        self,
        filename: str,
        exam_year_id: int
    ) -> bool:
        """
        Validate uploaded CSV matches expected year and school/subject.
        
        Filename must be: marks_<school>_<subject>_<year>.csv
        """
        self._validate_exam_year(exam_year_id)
        self._check_year_not_locked(exam_year_id)
        
        # Parse filename
        parts = filename.replace('.csv', '').split('_')
        if len(parts) != 5:  # marks_<school>_<subject>_<year>.csv
            raise ValueError("Invalid CSV filename format")
        
        try:
            upload_year_id = int(parts[4])
            if upload_year_id != exam_year_id:
                raise ValueError(f"CSV year {upload_year_id} doesn't match expected {exam_year_id}")
        except (ValueError, IndexError):
            raise ValueError("Invalid exam year in filename")
        
        return True
```

---

## PART 4: SAFE DATA MIGRATION STRATEGY

### Migration Script Checklist

```python
# migrations/001_introduce_exam_years.py
"""
Safe migration to introduce exam years with multi-year isolation.

Phases:
1. Create exam_years table
2. Create legacy/default year
3. Add exam_year_id columns to all tables
4. Backfill data
5. Validate integrity
6. Add constraints
"""

import mysql.connector
from datetime import datetime

def create_exam_years_table(cursor):
    """Phase 1: Create exam_years table"""
    print("[1/5] Creating exam_years table...")
    
    cursor.execute("""
    CREATE TABLE exam_years (
        id INT PRIMARY KEY AUTO_INCREMENT,
        year_label VARCHAR(9) UNIQUE NOT NULL,
        is_active BOOLEAN DEFAULT FALSE,
        is_locked BOOLEAN DEFAULT FALSE,
        published_at TIMESTAMP NULL,
        locked_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT chk_only_one_active CHECK (
            (SELECT COUNT(*) FROM exam_years WHERE is_active = TRUE) <= 1
        ),
        INDEX idx_is_active (is_active),
        INDEX idx_is_locked (is_locked)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    """)
    print("✓ exam_years table created")


def create_default_year(cursor):
    """Phase 2: Create default/legacy year"""
    print("[2/5] Creating legacy exam year...")
    
    # Detect current year or use default
    current_year = datetime.now().year
    year_label = str(current_year)
    
    cursor.execute("""
    INSERT INTO exam_years (year_label, is_active)
    VALUES (%s, TRUE)
    """, (year_label,))
    
    legacy_year_id = cursor.lastrowid
    print(f"✓ Created legacy year: {year_label} (ID: {legacy_year_id})")
    
    return legacy_year_id


def add_exam_year_columns(cursor):
    """Phase 3: Add exam_year_id to all tables"""
    print("[3/5] Adding exam_year_id columns to all tables...")
    
    tables_to_update = [
        'candidates',
        'registrations',
        'subject_registrations',
        'marks',
        'results',
        'summaries',
        'reports',
        'uploads',
        'csv_templates',
        'moderation_outputs'
    ]
    
    for table in tables_to_update:
        try:
            cursor.execute(f"""
            ALTER TABLE {table}
            ADD COLUMN exam_year_id INT NOT NULL DEFAULT 0
            """)
            print(f"  ✓ Added exam_year_id to {table}")
        except mysql.connector.Error as e:
            if 'Duplicate column' in str(e):
                print(f"  ⚠ exam_year_id already exists in {table}")
            else:
                raise


def backfill_exam_year_data(cursor, legacy_year_id):
    """Phase 4: Backfill all records with legacy year"""
    print("[4/5] Backfilling exam_year_id for existing data...")
    
    tables_to_backfill = [
        'candidates',
        'registrations',
        'subject_registrations',
        'marks',
        'results',
        'summaries',
        'reports',
        'uploads',
        'csv_templates',
        'moderation_outputs'
    ]
    
    before_counts = {}
    
    for table in tables_to_backfill:
        # Count before backfill
        cursor.execute(f"SELECT COUNT(*) as cnt FROM {table}")
        before_counts[table] = cursor.fetchone()[0]
        
        # Backfill
        cursor.execute(f"""
        UPDATE {table}
        SET exam_year_id = %s
        WHERE exam_year_id = 0
        """, (legacy_year_id,))
        
        rows_affected = cursor.rowcount
        print(f"  ✓ {table}: {rows_affected} rows updated")
    
    return before_counts


def validate_integrity(cursor, before_counts):
    """Phase 5: Validate migration integrity"""
    print("[5/5] Validating data integrity...")
    
    tables_to_validate = [
        'candidates',
        'registrations',
        'subject_registrations',
        'marks',
        'results',
        'summaries',
        'reports',
        'uploads',
        'csv_templates',
        'moderation_outputs'
    ]
    
    all_valid = True
    
    for table in tables_to_validate:
        cursor.execute(f"SELECT COUNT(*) as cnt FROM {table}")
        after_count = cursor.fetchone()[0]
        before_count = before_counts.get(table, 0)
        
        if before_count != after_count:
            print(f"  ✗ {table}: Row count mismatch! Before: {before_count}, After: {after_count}")
            all_valid = False
        else:
            print(f"  ✓ {table}: Row count verified ({after_count} rows)")
        
        # Check for NULL exam_year_id
        cursor.execute(f"""
        SELECT COUNT(*) as cnt FROM {table} WHERE exam_year_id IS NULL
        """)
        null_count = cursor.fetchone()[0]
        
        if null_count > 0:
            print(f"  ✗ {table}: {null_count} rows with NULL exam_year_id!")
            all_valid = False
    
    if all_valid:
        print("✓ All integrity checks passed")
    else:
        print("✗ Integrity checks failed - ROLLBACK RECOMMENDED")
    
    return all_valid


def add_constraints(cursor):
    """Add foreign key constraints after data validation"""
    print("[6/5] Adding constraints...")
    
    constraints = [
        ("candidates", "fk_candidates_exam_year", "exam_years"),
        ("registrations", "fk_registrations_exam_year", "exam_years"),
        ("subject_registrations", "fk_subject_registrations_exam_year", "exam_years"),
        ("marks", "fk_marks_exam_year", "exam_years"),
        ("results", "fk_results_exam_year", "exam_years"),
        ("summaries", "fk_summaries_exam_year", "exam_years"),
        ("reports", "fk_reports_exam_year", "exam_years"),
        ("uploads", "fk_uploads_exam_year", "exam_years"),
        ("csv_templates", "fk_csv_templates_exam_year", "exam_years"),
        ("moderation_outputs", "fk_moderation_outputs_exam_year", "exam_years"),
    ]
    
    for table, constraint_name, ref_table in constraints:
        try:
            cursor.execute(f"""
            ALTER TABLE {table}
            ADD CONSTRAINT {constraint_name}
            FOREIGN KEY (exam_year_id) REFERENCES {ref_table}(id) ON DELETE RESTRICT
            """)
            print(f"  ✓ Added constraint on {table}")
        except mysql.connector.Error as e:
            if 'Duplicate key name' in str(e):
                print(f"  ⚠ Constraint already exists on {table}")
            else:
                raise


def run_migration():
    """Execute full migration with transaction safety"""
    config = {
        'host': 'localhost',
        'user': 'root',
        'password': 'password',
        'database': 'rms'
    }
    
    connection = mysql.connector.connect(**config)
    cursor = connection.cursor()
    
    try:
        print("=" * 60)
        print("MIGRATION: Introduce Exam Years with Multi-Year Isolation")
        print("=" * 60)
        
        # Create exam_years table
        create_exam_years_table(cursor)
        connection.commit()
        
        # Create default year
        legacy_year_id = create_default_year(cursor)
        connection.commit()
        
        # Add columns
        add_exam_year_columns(cursor)
        connection.commit()
        
        # Backfill data
        before_counts = backfill_exam_year_data(cursor, legacy_year_id)
        connection.commit()
        
        # Validate
        if not validate_integrity(cursor, before_counts):
            print("\n⚠ WARNING: Integrity issues detected. Rollback recommended.")
            connection.rollback()
            return False
        
        # Add constraints
        add_constraints(cursor)
        connection.commit()
        
        print("\n" + "=" * 60)
        print("✓ MIGRATION COMPLETED SUCCESSFULLY")
        print("=" * 60)
        return True
        
    except Exception as e:
        print(f"\n✗ MIGRATION FAILED: {e}")
        connection.rollback()
        return False
    finally:
        cursor.close()
        connection.close()


if __name__ == '__main__':
    success = run_migration()
    exit(0 if success else 1)
```

---

## PART 5: API LAYER INTEGRATION

### Flask/FastAPI Middleware for Year Context

```python
# middleware/exam_year_middleware.py
from functools import wraps
from flask import request, g, jsonify
from services.base_service import ExamYearRequiredError, YearLockedError

def require_exam_year(f):
    """
    Decorator that ensures exam_year_id is present in request.
    Automatically added to context.
    """
    @wraps(f)
    def decorated_function(*args, **kwargs):
        # Try to get exam_year_id from multiple sources
        exam_year_id = None
        
        # 1. Query parameter
        if 'exam_year_id' in request.args:
            exam_year_id = int(request.args['exam_year_id'])
        
        # 2. Request body (JSON)
        elif request.is_json:
            exam_year_id = request.json.get('exam_year_id')
        
        # 3. Header
        elif 'X-Exam-Year-ID' in request.headers:
            exam_year_id = int(request.headers['X-Exam-Year-ID'])
        
        if not exam_year_id:
            return jsonify({
                'error': 'Missing required parameter: exam_year_id',
                'message': 'exam_year_id is mandatory for all exam-related operations'
            }), 400
        
        # Store in context
        g.exam_year_id = exam_year_id
        
        try:
            return f(*args, **kwargs)
        except ExamYearRequiredError as e:
            return jsonify({'error': str(e)}), 400
        except YearLockedError as e:
            return jsonify({
                'error': 'Locked Year',
                'message': str(e),
                'http_status': 423  # Locked resource
            }), 423
    
    return decorated_function
```

### Flask Endpoints with Year Isolation

```python
# routes/candidates.py
from flask import Blueprint, request, jsonify, g
from services.candidate_service import CandidateService
from middleware.exam_year_middleware import require_exam_year

candidates_bp = Blueprint('candidates', __name__, url_prefix='/api/candidates')

@candidates_bp.route('/by-subject', methods=['GET'])
@require_exam_year
def get_by_subject():
    """
    Get candidates registered for a subject in a specific year.
    
    Required params:
    - subject_id (query)
    - school_id (query)
    - exam_year_id (query, header, or body)
    """
    subject_id = request.args.get('subject_id', type=int)
    school_id = request.args.get('school_id', type=int)
    exam_year_id = g.exam_year_id
    
    service = CandidateService(db_config)
    service.connect()
    try:
        candidates = service.get_registered_candidates(
            subject_id, school_id, exam_year_id
        )
        return jsonify({
            'success': True,
            'exam_year_id': exam_year_id,
            'count': len(candidates),
            'data': candidates
        })
    finally:
        service.disconnect()


@candidates_bp.route('', methods=['POST'])
@require_exam_year
def create():
    """Create a new candidate"""
    candidate_data = request.json
    exam_year_id = g.exam_year_id
    
    service = CandidateService(db_config)
    service.connect()
    try:
        candidate_id = service.create_candidate(candidate_data, exam_year_id)
        return jsonify({
            'success': True,
            'candidate_id': candidate_id,
            'exam_year_id': exam_year_id
        }), 201
    finally:
        service.disconnect()


@candidates_bp.route('/<int:candidate_id>', methods=['PUT'])
@require_exam_year
def update(candidate_id):
    """Update candidate information"""
    candidate_data = request.json
    exam_year_id = g.exam_year_id
    
    service = CandidateService(db_config)
    service.connect()
    try:
        success = service.update_candidate(candidate_id, candidate_data, exam_year_id)
        return jsonify({
            'success': success,
            'exam_year_id': exam_year_id
        })
    finally:
        service.disconnect()
```

---

## PART 6: UI & WORKFLOW ADJUSTMENTS

### Exam Year Selector Component

```html
<!-- templates/components/exam_year_selector.html -->
<div class="exam-year-selector">
    <label for="exam-year-select">Academic Year:</label>
    <select id="exam-year-select" name="exam_year_id" onchange="handleYearChange(this)">
        <option value="">-- Select Year --</option>
    </select>
    
    <div id="year-status" class="status-badge">
        <!-- Will show: ACTIVE, LOCKED, etc. -->
    </div>
</div>

<script>
// Load available years
async function loadExamYears() {
    const response = await fetch('/api/exam-years');
    const data = await response.json();
    
    const select = document.getElementById('exam-year-select');
    data.years.forEach(year => {
        const option = document.createElement('option');
        option.value = year.id;
        option.textContent = `${year.year_label}${year.is_locked ? ' (Read-Only)' : ''}`;
        select.appendChild(option);
    });
    
    // Set active year as default
    const activeYear = data.active_year;
    if (activeYear) {
        select.value = activeYear.id;
    }
}

// Handle year change
async function handleYearChange(select) {
    const exam_year_id = select.value;
    
    // Store in session/localStorage
    sessionStorage.setItem('exam_year_id', exam_year_id);
    
    // Fetch year status
    const response = await fetch(`/api/exam-years/${exam_year_id}`);
    const year = await response.json();
    
    // Show lock status
    const statusDiv = document.getElementById('year-status');
    if (year.is_locked) {
        statusDiv.innerHTML = '<span class="badge badge-warning">🔒 READ-ONLY</span>';
        disableEditButtons();
    } else {
        statusDiv.innerHTML = '<span class="badge badge-success">✓ Editable</span>';
        enableEditButtons();
    }
    
    // Reload current view
    location.reload();
}

// Disable buttons when year is locked
function disableEditButtons() {
    document.querySelectorAll('[data-action="create"], [data-action="edit"], [data-action="delete"]')
        .forEach(btn => {
            btn.disabled = true;
            btn.title = 'Cannot edit locked year';
        });
}

function enableEditButtons() {
    document.querySelectorAll('[data-action="create"], [data-action="edit"], [data-action="delete"]')
        .forEach(btn => {
            btn.disabled = false;
        });
}

// Load on page load
document.addEventListener('DOMContentLoaded', loadExamYears);
</script>
```

---

## SUMMARY

This architecture provides:

✅ **Centralized exam year management** - Single source of truth  
✅ **Multi-year data isolation** - Strict boundaries per year  
✅ **Year locking** - Immutability after publication  
✅ **Safe migration** - Zero data loss, full validation  
✅ **Service layer** - Year as mandatory parameter  
✅ **API enforcement** - Middleware validates year context  
✅ **UI integration** - Year selector, read-only mode  
✅ **Database constraints** - Trigger-based protection

This design aligns with NECTA examination standards and ensures auditability.
