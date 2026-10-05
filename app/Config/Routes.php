<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Shield auth routes (login, logout, ...). Registration is disabled: users are created by an admin.
service('auth')->routes($routes, ['except' => ['register']]);

// Health check for uptime monitoring (no auth, see Filters::$globals).
$routes->get('up', static fn () => response()->setJSON(['status' => 'ok', 'time' => date(DATE_ATOM)]));

// PWA offline fallback page (cached by the service worker).
$routes->get('offline', static fn () => view('offline'));

// Module routes live in app/Modules/<Name>/Config/Routes.php and are auto-discovered
// through the PSR-4 namespaces registered in Config\Autoload.
