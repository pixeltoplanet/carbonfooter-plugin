<?php
// Test bootstrap for CarbonFooter plugin

require_once __DIR__ . '/../vendor/autoload.php';

// Define WordPress ABSPATH so guarded plugin files don't exit during tests
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

// Explicitly include Brain Monkey classes (belt-and-suspenders for some CI environments)
if (!class_exists('Brain\\Monkey\\Functions')) {
    $bmFunctions = __DIR__ . '/../vendor/brain/monkey/src/Functions.php';
    if (file_exists($bmFunctions)) {
        require_once $bmFunctions;
    }
}
if (!class_exists('Brain\\Monkey')) {
    $bmMonkey = __DIR__ . '/../vendor/brain/monkey/src/Monkey.php';
    if (file_exists($bmMonkey)) {
        require_once $bmMonkey;
    }
}
