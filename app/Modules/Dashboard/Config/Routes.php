<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('', ['namespace' => 'Modules\Dashboard\Controllers'], static function (RouteCollection $routes): void {
    $routes->get('/', 'DashboardController::index', ['as' => 'dashboard']);
    $routes->get('viac', 'DashboardController::more', ['as' => 'more']);

    // Placeholders for phase 1 modules so the bottom navigation already works.
    $routes->get('kalendar', 'DashboardController::comingSoon/Kalendár', ['as' => 'calendar']);
    $routes->get('peniaze', 'DashboardController::comingSoon/Peniaze', ['as' => 'finance']);
    $routes->get('dokumenty', 'DashboardController::comingSoon/Dokumenty', ['as' => 'documents']);
});
