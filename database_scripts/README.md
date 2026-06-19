# ลำดับการนำเข้า Schema บน phpMyAdmin

เลือกฐานข้อมูลปลายทาง (เช่น `citservice_db`) ใน phpMyAdmin ก่อน แล้ว Import ไฟล์ตามลำดับนี้:

1. `01_admins.sql`
2. `02_students.sql`
3. `03_student_users.sql`
4. `04_system_settings.sql`
5. `05_student_line_requests.sql`

เหตุผลที่ต้องเรียงลำดับ:

- `student_users.student_id` อ้างอิง `students.id`
- `student_line_requests.requester_student_id` อ้างอิง `students.id`
- `students.parent_student_id` เป็น Foreign Key อ้างอิงตาราง `students` เดียวกัน

ไฟล์ทั้งหมดไม่ระบุชื่อฐานข้อมูลด้วยคำสั่ง `USE` เพื่อให้ใช้ได้ทั้งฐานข้อมูล local (`alumni_db`) และฐานข้อมูลมหาวิทยาลัย (`citservice_db`) โดยต้องเลือกฐานข้อมูลให้ถูกต้องก่อน Import

