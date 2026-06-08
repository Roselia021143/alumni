<?php

class Session
{
    public static function start()
    {
        self::startSession();
        self::enforceLifetime();
    }

    public static function login($admin)
    {
        self::adminLogin($admin);
    }

    public static function adminLogin($admin)
    {
        self::start();
        session_regenerate_id(true);

        $_SESSION['role'] = 'admin';
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['last_activity'] = time();
    }

    public static function studentLogin($studentUser)
    {
        self::start();
        session_regenerate_id(true);

        $_SESSION['role'] = 'student';
        $_SESSION['student_user_id'] = $studentUser['id'];
        $_SESSION['student_id'] = $studentUser['student_id'];
        $_SESSION['student_username'] = $studentUser['username'];
        $_SESSION['last_activity'] = time();
    }

    public static function logout()
    {
        self::startSession();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public static function isLoggedIn()
    {
        return self::isAdminLoggedIn();
    }

    public static function isAdminLoggedIn()
    {
        self::start();
        return isset($_SESSION['admin_id']) && self::role() === 'admin';
    }

    public static function isStudentLoggedIn()
    {
        self::start();
        return isset($_SESSION['student_id']) && self::role() === 'student';
    }

    public static function requireLogin()
    {
        self::requireAdminLogin();
    }

    public static function requireAdminLogin()
    {
        if (!self::isAdminLoggedIn()) {
            self::flash('error', 'กรุณาเข้าสู่ระบบผู้ดูแลก่อนใช้งาน');
            header('Location: login.php');
            exit;
        }
    }

    public static function requireStudentLogin()
    {
        if (!self::isStudentLoggedIn()) {
            self::flash('error', 'กรุณาเข้าสู่ระบบนักศึกษาก่อนใช้งาน');
            header('Location: login.php');
            exit;
        }
    }

    public static function role()
    {
        self::start();
        return isset($_SESSION['role']) ? $_SESSION['role'] : '';
    }

    public static function adminUsername()
    {
        self::start();
        return isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : '';
    }

    public static function studentId()
    {
        self::start();
        return isset($_SESSION['student_id']) ? (int) $_SESSION['student_id'] : 0;
    }

    public static function studentUsername()
    {
        self::start();
        return isset($_SESSION['student_username']) ? $_SESSION['student_username'] : '';
    }

    public static function flash($key, $message = null)
    {
        self::start();

        if ($message !== null) {
            $_SESSION['flash'][$key] = $message;
            return null;
        }

        if (!isset($_SESSION['flash'][$key])) {
            return null;
        }

        $value = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);

        return $value;
    }

    private static function enforceLifetime()
    {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return;
        }

        if ((time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
            self::logout();
            self::start();
            self::flash('error', 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบอีกครั้ง');
            header('Location: /alumni/index.php');
            exit;
        }

        $_SESSION['last_activity'] = time();
    }

    private static function startSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
    }
}
