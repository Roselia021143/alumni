USE alumni_db;

ALTER TABLE student_users
    ADD COLUMN IF NOT EXISTS email VARCHAR(150) NULL AFTER username;

CREATE UNIQUE INDEX IF NOT EXISTS uq_student_users_email
    ON student_users (email);
