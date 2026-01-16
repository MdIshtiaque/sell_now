<?php
/**
 * SellNow - Digital Marketplace
 * REFACTORED: From switch statement to Router pattern
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SellNow\Config\Database;
use SellNow\Http\Router;
use SellNow\Http\Request;
use SellNow\Http\Response;
use SellNow\Controllers\AuthController;
use SellNow\Controllers\ProductController;
use SellNow\Controllers\CartController;
use SellNow\Controllers\CheckoutController;
use SellNow\Controllers\PublicController;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

session_start();

// Twig Setup
$loader = new FilesystemLoader(__DIR__ . '/../templates');
$twig = new Environment($loader, ['debug' => true]);
$twig->addGlobal('session', $_SESSION);

// Database Connection
$db = Database::getInstance()->getConnection();

// HTTP Request
$request = new Request();

// Controller Factory (Simple DI)
$controllers = [
    'auth' => fn() => new AuthController($twig, $db),
    'product' => fn() => new ProductController($twig, $db),
    'cart' => fn() => new CartController($twig, $db),
    'checkout' => fn() => new CheckoutController($twig, $db),
    'public' => fn() => new PublicController($twig, $db),
];

// Router Setup
$router = new Router();

// Home
$router->get('/', function() use ($twig) {
    echo $twig->render('layouts/base.html.twig', [
        'content' => "<h1>Welcome to SellNow</h1><p>Your digital marketplace.</p><a href='/login' class='btn btn-primary'>Login</a> <a href='/register' class='btn btn-outline-secondary'>Register</a>"
    ]);
});

// Auth Routes
$router->get('/login', fn() => $controllers['auth']()->loginForm());
$router->post('/login', fn() => $controllers['auth']()->login());
$router->get('/register', fn() => $controllers['auth']()->registerForm());
$router->post('/register', fn() => $controllers['auth']()->register());
$router->get('/logout', function() {
    session_destroy();
    Response::redirect('/');
});
$router->get('/dashboard', fn() => $controllers['auth']()->dashboard());

// Product Routes
$router->get('/products/add', fn() => $controllers['product']()->create());
$router->post('/products/add', fn() => $controllers['product']()->store());

// Cart Routes
$router->get('/cart', fn() => $controllers['cart']()->index());
$router->post('/cart/add', fn() => $controllers['cart']()->add());
$router->get('/cart/clear', fn() => $controllers['cart']()->clear());

// Checkout Routes
$router->get('/checkout', fn() => $controllers['checkout']()->index());
$router->post('/checkout/process', fn() => $controllers['checkout']()->process());
$router->get('/payment', fn() => $controllers['checkout']()->payment());
$router->post('/checkout/success', fn() => $controllers['checkout']()->success());

// Dynamic Profile: /{username}
$router->get('/{username}', function(string $username) use ($controllers) {
    if (str_contains($username, '.')) {
        Response::notFound();
        return;
    }
    $controllers['public']()->profile($username);
});

// 404 Handler
$router->setNotFoundHandler(function() {
    http_response_code(404);
    echo '<div style="text-align:center;padding:50px;font-family:sans-serif;">';
    echo '<h1>404 - Page Not Found</h1>';
    echo '<a href="/">Go Home</a>';
    echo '</div>';
});

// Dispatch
$router->dispatch($request);
