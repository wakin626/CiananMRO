<?php

require_once __DIR__ . '/../config/Database.php';

class AuthController
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function showLogin(string $authError = ''): void
    {
        require __DIR__ . '/../views/auth/login.php';
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->showLogin();
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $this->showLogin('Please enter both username and password.');
            return;
        }

        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user === false || $user['password'] !== $password) {
            $this->showLogin('Invalid username or password.');
            return;
        }

        session_regenerate_id(true);
        $_SESSION['user_id']   = (int) $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];

        $this->startFreshSession();

        header('Location: /CIANANMRO/public/index.php?page=' . $this->landingPage($user['role']));
        exit;
    }

    // Fresh sessions start with all sidebar accordions collapsed so the user
    // explores the UI themselves; fresh_login suppresses the auto-expand of
    // the active group for one page load only.
    private function startFreshSession(): void
    {
        $expiry = time() + 31536000;
        setcookie('mi_group_open', '0', $expiry, '/CIANANMRO');
        setcookie('sr_group_open', '0', $expiry, '/CIANANMRO');
        setcookie('fresh_login', '1', time() + 30, '/CIANANMRO');
    }

    private function landingPage(string $role): string
    {
        return in_array($role, ['cianan_user', 'production_operator'], true)
            ? 'service_request'
            : 'items';
    }

    public function showRegister(string $authError = ''): void
    {
        require __DIR__ . '/../views/auth/register.php';
    }

    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->showRegister();
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $role = trim($_POST['role'] ?? 'cianan_user');
        if (!in_array($role, ['cianan_user', 'production_operator', 'maintenance_technician'], true)) {
            $role = 'cianan_user';
        }

        if ($username === '' || $password === '' || $fullName === '') {
            $this->showRegister('All fields are required.');
            return;
        }

        $exists = $this->db->prepare('SELECT id FROM users WHERE username = :u LIMIT 1');
        $exists->execute([':u' => $username]);
        if ($exists->fetch() !== false) {
            $this->showRegister('That username is already taken.');
            return;
        }

        $insert = $this->db->prepare(
            "INSERT INTO users (username, password, full_name, role)
             VALUES (:u, :p, :f, :r)"
        );
        $insert->execute([
            ':u' => $username,
            ':p' => $password,
            ':f' => $fullName,
            ':r' => $role,
        ]);

        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        $_SESSION['user_id']   = (int) $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];

        $this->startFreshSession();

        header('Location: /CIANANMRO/public/index.php?page=' . $this->landingPage($user['role']));
        exit;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();

        header('Location: /CIANANMRO/public/index.php?page=login');
        exit;
    }
}
