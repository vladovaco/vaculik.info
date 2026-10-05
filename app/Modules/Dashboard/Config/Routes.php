<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('', ['namespace' => 'Modules\Dashboard\Controllers'], static function (RouteCollection $routes): void {
    $routes->get('/', 'DashboardController::index', ['as' => 'dashboard']);
    $routes->get('viac', 'DashboardController::more', ['as' => 'more']);
});
