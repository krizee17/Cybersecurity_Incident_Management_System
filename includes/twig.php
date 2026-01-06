<?php
/**
 * Twig Template Engine Configuration
 */

// Check if Composer autoload exists
$autoload_path = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload_path)) {
    die("Error: Twig is not installed. Please run 'composer install' to install dependencies.");
}

require_once $autoload_path;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Extension\DebugExtension;

// Define template directory
$loader = new FilesystemLoader(__DIR__ . '/../templates');

// Ensure cache directory exists
$cache_dir = __DIR__ . '/../cache/twig';
if (!file_exists($cache_dir)) {
    mkdir($cache_dir, 0755, true);
}

// Create Twig environment
$twig = new Environment($loader, [
    'cache' => $cache_dir,
    'debug' => true, // Set to false in production
    'auto_reload' => true, // Set to false in production
]);

// Add debug extension if in debug mode
if (true) { // Change to false in production
    $twig->addExtension(new DebugExtension());
}

// Add custom functions/filters for security
$twig->addFunction(new \Twig\TwigFunction('escape', function ($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}));

// Add function to generate CSRF token
$twig->addFunction(new \Twig\TwigFunction('csrf_token', function () {
    require_once __DIR__ . '/security.php';
    return generateCSRFToken();
}));

// Add function to get active page for navigation
$twig->addFunction(new \Twig\TwigFunction('is_active', function ($current, $page) {
    return $current === $page ? 'active' : '';
}));

// Add filter for date formatting
$twig->addFilter(new \Twig\TwigFilter('date_format', function ($date, $format = 'Y-m-d H:i:s') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}));

// Add filter for datetime-local format
$twig->addFilter(new \Twig\TwigFilter('datetime_local', function ($date) {
    if (empty($date)) return '';
    return date('Y-m-d\TH:i', strtotime($date));
}));

return $twig;

