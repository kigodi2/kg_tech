-- ============================================================================
-- EXAM YEARS MIGRATION - Complete SQL Implementation
-- ============================================================================
-- This script introduces exam years with multi-year isolation
-- Execution phases: Create -> Backfill -> Validate -> Constrain -> Lock
-- ============================================================================

-- ============================================================================
-- PHASE 1: CREATE EXAM_YEARS TABLE (Foundation)
-- ============================================================================

CREATE TABLE IF NOT EXISTS exam_years (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique exam year identifier',
    year_label VARCHAR(9) UNIQUE NOT NULL COMMENT 'Academic year label (e.g., "2024", "2023-2024")',
    is_active BOOLEAN DEFAULT FALSE COMMENT 'Only one year active at a time (enforced by constraint)',
    is_locked BOOLEAN DEFAULT FALSE COMMENT 'Locked years are immutable (read-only)',
    published_at TIMESTAMP NULL COMMENT 'When results were first published',
    locked_at TIMESTAMP NULL COMMENT 'When year was locked (automatic on publish)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- CONSTRAINTS
    UNIQUE KEY uq_year_label (year_label),
    CHECK (is_locked = FALSE OR (is_locked = TRUE AND published_at IS NOT NULL)),
    
    -- INDEXES
    INDEX idx_is_active (is_active),
    INDEX idx_is_locked (is_locked),
    INDEX idx_published_at (published_at),
    INDEX idx_year_label (year_label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Centralized academic year management with isolation and locking';

-- ============================================================================
-- PHASE 2: STORED PROCEDURES FOR YEAR MANAGEMENT
-- ============================================================================

DELIMITER //

-- Enforce "only one active year" constraint
CREATE PROCEDURE IF NOT EXISTS deactivate_all_other_years(IN p_year_id INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Verify year exists
    IF NOT EXISTS (SELECT 1 FROM exam_years WHERE id = p_year_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Exam year does not exist';
    END IF;
    
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

-- Publish results for a year (triggers automatic locking)
CREATE PROCEDURE IF NOT EXISTS publish_exam_year_results(IN p_year_id INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Verify year exists and not already published
    IF NOT EXISTS (SELECT 1 FROM exam_years WHERE id = p_year_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Exam year does not exist';
    END IF;
    
    IF EXISTS (SELECT 1 FROM exam_years WHERE id = p_year_id AND published_at IS NOT NULL) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Year already published and locked';
    END IF;
    
    -- Publish year (trigger will lock it)
    UPDATE exam_years
    SET published_at = CURRENT_TIMESTAMP
    WHERE id = p_year_id;
    
    COMMIT;
END //

-- Get currently active exam year
CREATE PROCEDURE IF NOT EXISTS get_active_exam_year(OUT p_year_id INT, OUT p_year_label VARCHAR(9), OUT p_is_locked BOOLEAN)
BEGIN
    SELECT id, year_label, is_locked 
    INTO p_year_id, p_year_label, p_is_locked
    FROM exam_years 
    WHERE is_active = TRUE 
    LIMIT 1;
END //

DELIMITER ;

-- ============================================================================
-- PHASE 3: TRIGGERS FOR WRITE PROTECTION (Year Locking)
-- ============================================================================

DELIMITER //

-- Auto-lock year when published
CREATE TRIGGER IF NOT EXISTS lock_year_after_publication
BEFORE UPDATE ON exam_years
FOR EACH ROW
BEGIN
    -- When published_at is first set, auto-lock
    IF NEW.published_at IS NOT NULL AND OLD.published_at IS NULL THEN
        SET NEW.locked_at = CURRENT_TIMESTAMP;
        SET NEW.is_locked = TRUE;
    END IF;
    
    -- Prevent unlocking once locked
    IF OLD.is_locked = TRUE AND NEW.is_locked = FALSE THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot unlock a published exam year';
    END IF;
END //

-- Prevent INSERT into locked years (candidates)
CREATE TRIGGER IF NOT EXISTS prevent_insert_candidates_locked_year
BEFORE INSERT ON candidates
FOR EACH ROW
BEGIN
    DECLARE v_is_locked BOOLEAN;
    SELECT is_locked INTO v_is_locked FROM exam_years WHERE id = NEW.exam_year_id;
    
    IF v_is_locked = TRUE THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot insert: Exam year is locked and read-only';
    END IF;
END //

-- Prevent UPDATE into locked years (candidates)
CREATE TRIGGER IF NOT EXISTS prevent_update_candidates_locked_year
BEFORE UPDATE ON candidates
FOR EACH ROW
BEGIN
    DECLARE v_is_locked BOOLEAN;
    SELECT is_locked INTO v_is_locked FROM exam_years WHERE id = NEW.exam_year_id;
    
    IF v_is_locked = TRUE THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot update: Exam year is locked and read-only';
    END IF;
END //

-- Prevent DELETE from locked years (candidates)
CREATE TRIGGER IF NOT EXISTS prevent_delete_candidates_locked_year
BEFORE DELETE ON candidates
FOR EACH ROW
BEGIN
    DECLARE v_is_locked BOOLEAN;
    SELECT is_locked INTO v_is_locked FROM exam_years WHERE id = OLD.exam_year_id;
    
    IF v_is_locked = TRUE THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot delete: Exam year is locked and read-only';
    END IF;
END //

-- Prevent writes to locked years (marks)
CREATE TRIGGER IF NOT EXISTS prevent_write_marks_locked_year_insert
BEFORE INSERT ON marks
FOR EACH ROW
BEGIN
    DECLARE v_is_locked BOOLEAN;
    SELECT is_locked INTO v_is_locked FROM exam_years WHERE id = NEW.exam_year_id;
    
    IF v_is_locked = TRUE THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot insert marks: Exam year is locked';
    END IF;
END //

CREATE TRIGGER IF NOT EXISTS prevent_write_marks_locked_year_update
BEFORE UPDATE ON marks
FOR EACH ROW
BEGIN
    DECLARE v_is_locked BOOLEAN;
    SELECT is_locked INTO v_is_locked FROM exam_years WHERE id = NEW.exam_year_id;
    
    IF v_is_locked = TRUE THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot update marks: Exam year is locked';
    END IF;
END //

-- Prevent writes to locked years (results)
CREATE TRIGGER IF NOT EXISTS prevent_write_results_locked_year_insert
BEFORE INSERT ON results
FOR EACH ROW
BEGIN
    DECLARE v_is_locked BOOLEAN;
    SELECT is_locked INTO v_is_locked FROM exam_years WHERE id = NEW.exam_year_id;
    
    IF v_is_locked = TRUE THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot insert results: Exam year is locked';
    END IF;
END //

CREATE TRIGGER IF NOT EXISTS prevent_write_results_locked_year_update
BEFORE UPDATE ON results
FOR EACH ROW
BEGIN
    DECLARE v_is_locked BOOLEAN;
    SELECT is_locked INTO v_is_locked FROM exam_years WHERE id = NEW.exam_year_id;
    
    IF v_is_locked = TRUE THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot update results: Exam year is locked';
    END IF;
END //

-- Prevent re-upload of CSVs for locked years
CREATE TRIGGER IF NOT EXISTS prevent_upload_locked_year
BEFORE INSERT ON uploads
FOR EACH ROW
BEGIN
    DECLARE v_is_locked BOOLEAN;
    SELECT is_locked INTO v_is_locked FROM exam_years WHERE id = NEW.exam_year_id;
    
    IF v_is_locked = TRUE THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot upload: Exam year is locked';
    END IF;
END //

DELIMITER ;

-- ============================================================================
-- PHASE 4: ADD EXAM_YEAR_ID TO EXISTING TABLES
-- ============================================================================
-- Execute these ALTER statements one by one, checking for errors

ALTER TABLE candidates 
ADD COLUMN exam_year_id INT NOT NULL DEFAULT 0 AFTER id,
ADD INDEX idx_exam_year_id (exam_year_id);

ALTER TABLE registrations 
ADD COLUMN exam_year_id INT NOT NULL DEFAULT 0 AFTER id,
ADD INDEX idx_exam_year_id (exam_year_id);

ALTER TABLE subject_registrations 
ADD COLUMN exam_year_id INT NOT NULL DEFAULT 0 AFTER id,
ADD INDEX idx_exam_year_id (exam_year_id);

ALTER TABLE marks 
ADD COLUMN exam_year_id INT NOT NULL DEFAULT 0 AFTER id,
ADD INDEX idx_exam_year_id (exam_year_id);

ALTER TABLE results 
ADD COLUMN exam_year_id INT NOT NULL DEFAULT 0 AFTER id,
ADD INDEX idx_exam_year_id (exam_year_id);

ALTER TABLE summaries 
ADD COLUMN exam_year_id INT NOT NULL DEFAULT 0 AFTER id,
ADD INDEX idx_exam_year_id (exam_year_id);

ALTER TABLE reports 
ADD COLUMN exam_year_id INT NOT NULL DEFAULT 0 AFTER id,
ADD INDEX idx_exam_year_id (exam_year_id);

ALTER TABLE uploads 
ADD COLUMN exam_year_id INT NOT NULL DEFAULT 0 AFTER id,
ADD INDEX idx_exam_year_id (exam_year_id);

-- Add similar columns to csv_templates, moderation_outputs if they exist
-- ALTER TABLE csv_templates ADD COLUMN exam_year_id INT NOT NULL DEFAULT 0 AFTER id;
-- ALTER TABLE moderation_outputs ADD COLUMN exam_year_id INT NOT NULL DEFAULT 0 AFTER id;

-- ============================================================================
-- PHASE 5: CREATE LEGACY YEAR (For existing data)
-- ============================================================================

-- Determine legacy year (current year or hardcoded)
INSERT INTO exam_years (year_label, is_active, created_at)
VALUES (YEAR(CURDATE()), TRUE, CURRENT_TIMESTAMP);

-- Capture the ID (adjust this to actual value after execution)
-- SELECT @legacy_year_id := id FROM exam_years ORDER BY id DESC LIMIT 1;

-- ============================================================================
-- PHASE 6: BACKFILL EXISTING DATA
-- ============================================================================
-- Replace <legacy_year_id> with actual ID from previous step

UPDATE candidates 
SET exam_year_id = <legacy_year_id>
WHERE exam_year_id = 0;

UPDATE registrations 
SET exam_year_id = <legacy_year_id>
WHERE exam_year_id = 0;

UPDATE subject_registrations 
SET exam_year_id = <legacy_year_id>
WHERE exam_year_id = 0;

UPDATE marks 
SET exam_year_id = <legacy_year_id>
WHERE exam_year_id = 0;

UPDATE results 
SET exam_year_id = <legacy_year_id>
WHERE exam_year_id = 0;

UPDATE summaries 
SET exam_year_id = <legacy_year_id>
WHERE exam_year_id = 0;

UPDATE reports 
SET exam_year_id = <legacy_year_id>
WHERE exam_year_id = 0;

UPDATE uploads 
SET exam_year_id = <legacy_year_id>
WHERE exam_year_id = 0;

-- ============================================================================
-- PHASE 7: VALIDATE MIGRATION (Data Integrity Checks)
-- ============================================================================

-- Check 1: Verify no NULL exam_year_id values
SELECT 'candidates' as table_name, COUNT(*) as null_count 
FROM candidates WHERE exam_year_id IS NULL
UNION ALL
SELECT 'registrations', COUNT(*) FROM registrations WHERE exam_year_id IS NULL
UNION ALL
SELECT 'subject_registrations', COUNT(*) FROM subject_registrations WHERE exam_year_id IS NULL
UNION ALL
SELECT 'marks', COUNT(*) FROM marks WHERE exam_year_id IS NULL
UNION ALL
SELECT 'results', COUNT(*) FROM results WHERE exam_year_id IS NULL
UNION ALL
SELECT 'summaries', COUNT(*) FROM summaries WHERE exam_year_id IS NULL
UNION ALL
SELECT 'reports', COUNT(*) FROM reports WHERE exam_year_id IS NULL
UNION ALL
SELECT 'uploads', COUNT(*) FROM uploads WHERE exam_year_id IS NULL;

-- Check 2: Verify all exam_year_ids reference valid years
SELECT 'candidates' as table_name, COUNT(*) as invalid_count
FROM candidates c
LEFT JOIN exam_years y ON c.exam_year_id = y.id
WHERE c.exam_year_id > 0 AND y.id IS NULL
UNION ALL
SELECT 'registrations', COUNT(*)
FROM registrations r
LEFT JOIN exam_years y ON r.exam_year_id = y.id
WHERE r.exam_year_id > 0 AND y.id IS NULL
UNION ALL
SELECT 'marks', COUNT(*)
FROM marks m
LEFT JOIN exam_years y ON m.exam_year_id = y.id
WHERE m.exam_year_id > 0 AND y.id IS NULL;

-- Check 3: Verify row counts match
SELECT 
    'candidates' as table_name,
    COUNT(*) as total_rows,
    SUM(CASE WHEN exam_year_id > 0 THEN 1 ELSE 0 END) as with_year,
    SUM(CASE WHEN exam_year_id = 0 THEN 1 ELSE 0 END) as without_year
FROM candidates
UNION ALL
SELECT 'registrations',
    COUNT(*), 
    SUM(CASE WHEN exam_year_id > 0 THEN 1 ELSE 0 END),
    SUM(CASE WHEN exam_year_id = 0 THEN 1 ELSE 0 END)
FROM registrations
UNION ALL
SELECT 'marks',
    COUNT(*), 
    SUM(CASE WHEN exam_year_id > 0 THEN 1 ELSE 0 END),
    SUM(CASE WHEN exam_year_id = 0 THEN 1 ELSE 0 END)
FROM marks;

-- ============================================================================
-- PHASE 8: ADD FOREIGN KEY CONSTRAINTS (After validation passes)
-- ============================================================================

ALTER TABLE candidates
ADD CONSTRAINT fk_candidates_exam_year
FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT;

ALTER TABLE registrations
ADD CONSTRAINT fk_registrations_exam_year
FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT;

ALTER TABLE subject_registrations
ADD CONSTRAINT fk_subject_registrations_exam_year
FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT;

ALTER TABLE marks
ADD CONSTRAINT fk_marks_exam_year
FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT;

ALTER TABLE results
ADD CONSTRAINT fk_results_exam_year
FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT;

ALTER TABLE summaries
ADD CONSTRAINT fk_summaries_exam_year
FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT;

ALTER TABLE reports
ADD CONSTRAINT fk_reports_exam_year
FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT;

ALTER TABLE uploads
ADD CONSTRAINT fk_uploads_exam_year
FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT;

-- ============================================================================
-- PHASE 9: ADDITIONAL INDEXES FOR QUERY OPTIMIZATION
-- ============================================================================

-- Composite indexes for common queries
ALTER TABLE candidates ADD INDEX idx_exam_year_school (exam_year_id, school_id);
ALTER TABLE registrations ADD INDEX idx_exam_year_candidate (exam_year_id, candidate_id);
ALTER TABLE subject_registrations ADD INDEX idx_exam_year_subject (exam_year_id, subject_id);
ALTER TABLE marks ADD INDEX idx_exam_year_candidate_subject (exam_year_id, candidate_id, subject_id);
ALTER TABLE results ADD INDEX idx_exam_year_candidate (exam_year_id, candidate_id);
ALTER TABLE reports ADD INDEX idx_exam_year_school (exam_year_id, school_id);
ALTER TABLE uploads ADD INDEX idx_exam_year_status (exam_year_id, status);

-- ============================================================================
-- PHASE 10: ROLLBACK PROCEDURE (If needed)
-- ============================================================================

-- If migration fails and you need to rollback:
/*
ALTER TABLE candidates DROP FOREIGN KEY fk_candidates_exam_year;
ALTER TABLE candidates DROP COLUMN exam_year_id;
ALTER TABLE candidates DROP INDEX idx_exam_year_id;
ALTER TABLE candidates DROP INDEX idx_exam_year_school;

-- Repeat for all other tables...

DROP TRIGGER lock_year_after_publication;
DROP TRIGGER prevent_insert_candidates_locked_year;
-- ... (drop all triggers)

DROP PROCEDURE deactivate_all_other_years;
DROP PROCEDURE publish_exam_year_results;
DROP PROCEDURE get_active_exam_year;

DROP TABLE exam_years;
*/

-- ============================================================================
-- PHASE 11: VERIFICATION QUERIES (Run after migration)
-- ============================================================================

-- Verify exam_years table created
SELECT COUNT(*) as active_years FROM exam_years WHERE is_active = TRUE;

-- Verify legacy year
SELECT * FROM exam_years;

-- Verify data distribution
SELECT 
    ey.id,
    ey.year_label,
    ey.is_active,
    ey.is_locked,
    COUNT(DISTINCT c.id) as candidate_count,
    COUNT(DISTINCT m.id) as mark_count,
    COUNT(DISTINCT r.id) as result_count
FROM exam_years ey
LEFT JOIN candidates c ON ey.id = c.exam_year_id
LEFT JOIN marks m ON ey.id = m.exam_year_id
LEFT JOIN results r ON ey.id = r.exam_year_id
GROUP BY ey.id;

-- ============================================================================
-- END OF MIGRATION SCRIPT
-- ============================================================================
