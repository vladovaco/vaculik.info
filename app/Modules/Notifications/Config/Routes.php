<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('upozornenia', ['namespace' => 'Modules\Notifications\Controllers'], static function (RouteCollection $routes): void {
    $routes->get('/', 'NotificationController::index', ['as' => 'notifications']);
    $routes->post('precitane', 'NotificationController::readAll', ['as' => 'notifications.readAll']);
    $routes->get('(:num)', 'NotificationController::open/$1', ['as' => 'notifications.open']);
    $routes->get('nastavenia', 'NotificationController::settings', ['as' => 'notifications.settings']);
    $routes->post('nastavenia', 'NotificationController::saveSettings', ['as' => 'notifications.settings.save']);
    $routes->post('push', 'NotificationController::subscribe', ['as' => 'push.subscribe']);
    $routes->post('push/zrusit', 'NotificationController::unsubscribe', ['as' => 'push.unsubscribe']);
    $routes->post('test', 'NotificationController::test', ['as' => 'notifications.test']);
});
