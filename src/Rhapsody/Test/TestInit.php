<?php
/*
 * This file bootstraps the test environment.
 */
namespace Rhapsody\Test;

error_reporting(E_ALL | E_STRICT);

// register silently failing autoloader
// spl_autoload_register(function($class)
// {
// var_dump($path);
// exit;
//     if (0 === strpos($class, 'Rhapsody\\')) {
//         $path = __DIR__.'/../../'.strtr($class, '\\', '/').'.php';
//         if (is_file($path) && is_readable($path)) {
//             require_once $path;

//             return true;
//         }
//     }
// });

require_once __DIR__ . "/../../../vendor/autoload.php";

