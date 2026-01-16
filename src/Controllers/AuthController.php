<?php

namespace SellNow\Controllers;

class AuthController
{

    // Imperfect: Manual dependency injection via constructor every time
    private $twig;
    private $db;

    public function __construct($twig, $db)
    {
        $this->twig = $twig;
        $this->db = $db;
    }

    public function loginForm()
    {
        if (isset($_SESSION['user_id'])) {
            header("Location: /dashboard");
            exit;
        }
        echo $this->twig->render('auth/login.html.twig');
    }

    public function login()
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Use password_verify() to securely check hashed password
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: /dashboard");
            exit;
        } else {
            header("Location: /login?error=Invalid credentials");
            exit;
        }
    }

    public function registerForm()
    {
        echo $this->twig->render('auth/register.html.twig');
    }

    public function register()
    {
        if (empty($_POST['email']) || empty($_POST['password']))
            die("Fill all fields");

        // Hash password securely using bcrypt
        $hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (email, username, Full_Name, password) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute([
                $_POST['email'],
                $_POST['username'],
                $_POST['fullname'],
                $hashedPassword
            ]);
        } catch (\Exception $e) {
            die("Error registering: " . $e->getMessage());
        }

        header("Location: /login?msg=Registered successfully");
        exit;
    }

    public function dashboard()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }

        // Fetch user's products
        $stmt = $this->db->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY product_id DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        echo $this->twig->render('dashboard.html.twig', [
            'username' => $_SESSION['username'],
            'products' => $products
        ]);
    }
}
