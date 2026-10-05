<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('peniaze', ['namespace' => 'Modules\Finance\Controllers'], static function (RouteCollection $routes): void {
    $routes->get('/', 'PaymentController::index', ['as' => 'finance', 'filter' => 'permission:finance.manage']);
    $routes->get('nova', 'PaymentController::new', ['as' => 'payments.new', 'filter' => 'permission:finance.manage']);
    $routes->post('/', 'PaymentController::create', ['as' => 'payments.create', 'filter' => 'permission:finance.manage']);
    $routes->get('(:num)', 'PaymentController::show/$1', ['as' => 'payments.show', 'filter' => 'permission:finance.manage']);
    $routes->get('(:num)/qr.svg', 'PaymentController::qr/$1', ['as' => 'payments.qr', 'filter' => 'permission:finance.manage']);
    $routes->get('(:num)/upravit', 'PaymentController::edit/$1', ['as' => 'payments.edit', 'filter' => 'permission:finance.manage']);
    $routes->post('(:num)', 'PaymentController::update/$1', ['as' => 'payments.update', 'filter' => 'permission:finance.manage']);
    $routes->post('(:num)/zaplatit', 'PaymentController::pay/$1', ['as' => 'payments.pay', 'filter' => 'permission:finance.manage']);
    $routes->post('(:num)/nezaplatene', 'PaymentController::unpay/$1', ['as' => 'payments.unpay', 'filter' => 'permission:finance.manage']);
    $routes->post('(:num)/zmazat', 'PaymentController::delete/$1', ['as' => 'payments.delete', 'filter' => 'permission:finance.manage']);
});
