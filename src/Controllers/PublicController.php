<?php

namespace SellNow\Controllers;

class PublicController
{
    private $twig;
    private $db;

    public function __construct($twig, $db)
    {
        $this->twig = $twig;
        $this->db = $db;
    }

    public function profile($username)
    {
        // Find user by username using prepared statement
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :u");
        $stmt->execute(['u' => $username]);
        $user = $stmt->fetch(\PDO::FETCH_OBJ);

        if (!$user) {
            http_response_code(404);
            echo "User not found";
            return;
        }

        // Fixed: Use prepared statement to prevent SQL injection
        $pStmt = $this->db->prepare("SELECT * FROM products WHERE user_id = ? AND is_active = 1");
        $pStmt->execute([$user->id]);
        $products = $pStmt->fetchAll(\PDO::FETCH_ASSOC);

        echo $this->twig->render('public/profile.html.twig', [
            'seller' => $user,
            'products' => $products
        ]);
    }
}
