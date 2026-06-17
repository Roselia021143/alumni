<?php

class SystemSetting
{
    private $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function get($key, $default = '')
    {
        $stmt = $this->conn->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1');
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ? $row['setting_value'] : $default;
    }

    public function currentAcademicYear()
    {
        return (int) $this->get('current_academic_year', date('Y') + 543);
    }
}
