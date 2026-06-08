ALTER TABLE students
ADD COLUMN profile_image VARCHAR(255) NULL AFTER line_id_contact;

-- ตัวอย่างอัปเดตรูปภาพ
-- UPDATE students
-- SET profile_image = 'assets/uploads/students/66113532012.jpg'
-- WHERE student_code = '66113532012';
