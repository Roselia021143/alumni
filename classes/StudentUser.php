<?php

class StudentUser
{
    private $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function createDefaultUser($studentId, $studentCode, $phone)
    {
        $studentCode = trim($studentCode);
        $phone = trim($phone);

        if ($studentId <= 0 || $studentCode === '' || $phone === '') {
            return false;
        }

        if ($this->exists($studentCode)) {
            return false;
        }

        $passwordHash = password_hash($phone, PASSWORD_BCRYPT);
        $mustChangePassword = 1;

        $stmt = $this->conn->prepare(
            'INSERT INTO student_users (student_id, username, password, must_change_password)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('issi', $studentId, $studentCode, $passwordHash, $mustChangePassword);
        $stmt->execute();
        $stmt->close();

        return true;
    }

    public function exists($username)
    {
        $stmt = $this->conn->prepare('SELECT id FROM student_users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }
}
