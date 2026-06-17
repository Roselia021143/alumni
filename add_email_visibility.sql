USE alumni_db;

ALTER TABLE students
    ADD COLUMN IF NOT EXISTS email_visible TINYINT(1) NOT NULL DEFAULT 0
    AFTER phone_visible;
