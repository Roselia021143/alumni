<?php

class Admin
{
    private $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function authenticate($username, $password)
    {
        $username = trim($username);

        if ($username === '' || $password === '') {
            return false;
        }

        $stmt = $this->conn->prepare('SELECT id, username, password FROM admins WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if (!$admin) {
            return false;
        }

        if (!password_verify($password, $admin['password'])) {
            return false;
        }

        return [
            'id' => $admin['id'],
            'username' => $admin['username'],
        ];
    }
}
