<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('osoby', ['namespace' => 'Modules\Household\Controllers'], static function (RouteCollection $routes): void {
    $routes->get('/', 'PersonController::index', ['as' => 'persons', 'filter' => 'permission:household.view']);
    $routes->get('nova', 'PersonController::new', ['as' => 'persons.new', 'filter' => 'permission:household.manage']);
    $routes->post('/', 'PersonController::create', ['as' => 'persons.create', 'filter' => 'permission:household.manage']);
    $routes->get('(:num)/upravit', 'PersonController::edit/$1', ['as' => 'persons.edit', 'filter' => 'permission:household.manage']);
    $routes->post('(:num)', 'PersonController::update/$1', ['as' => 'persons.update', 'filter' => 'permission:household.manage']);
    $routes->post('(:num)/zmazat', 'PersonController::delete/$1', ['as' => 'persons.delete', 'filter' => 'permission:household.manage']);
});
