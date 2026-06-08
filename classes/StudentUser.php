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

    public function authenticate($username, $password)
    {
        $username = trim($username);

        if ($username === '' || $password === '') {
            return false;
        }

        $stmt = $this->conn->prepare(
            'SELECT su.id, su.student_id, su.username, su.password, su.must_change_password
             FROM student_users su
             INNER JOIN students s ON su.student_id = s.id
             WHERE su.username = ?
             LIMIT 1'
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $studentUser = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$studentUser) {
            return $this->authenticateFromStudentPhone($username, $password);
        }

        if (!password_verify($password, $studentUser['password'])) {
            return false;
        }

        return [
            'id' => $studentUser['id'],
            'student_id' => $studentUser['student_id'],
            'username' => $studentUser['username'],
            'must_change_password' => $studentUser['must_change_password'],
        ];
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

    private function authenticateFromStudentPhone($studentCode, $password)
    {
        $stmt = $this->conn->prepare('SELECT id, student_code, phone FROM students WHERE student_code = ? LIMIT 1');
        $stmt->bind_param('s', $studentCode);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$student || trim((string) $student['phone']) === '' || trim((string) $student['phone']) !== trim((string) $password)) {
            return false;
        }

        $this->createDefaultUser((int) $student['id'], $student['student_code'], $student['phone']);

        return $this->authenticate($studentCode, $password);
    }
}
