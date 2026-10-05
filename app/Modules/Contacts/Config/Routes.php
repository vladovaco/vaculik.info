<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('kontakty', ['namespace' => 'Modules\Contacts\Controllers'], static function (RouteCollection $routes): void {
    $routes->get('/', 'ContactController::index', ['as' => 'contacts', 'filter' => 'permission:contacts.view']);
    $routes->get('novy', 'ContactController::new', ['as' => 'contacts.new', 'filter' => 'permission:contacts.manage']);
    $routes->post('/', 'ContactController::create', ['as' => 'contacts.create', 'filter' => 'permission:contacts.manage']);
    $routes->get('(:num)', 'ContactController::show/$1', ['as' => 'contacts.show', 'filter' => 'permission:contacts.view']);
    $routes->get('(:num)/upravit', 'ContactController::edit/$1', ['as' => 'contacts.edit', 'filter' => 'permission:contacts.manage']);
    $routes->post('(:num)', 'ContactController::update/$1', ['as' => 'contacts.update', 'filter' => 'permission:contacts.manage']);
    $routes->post('(:num)/zmazat', 'ContactController::delete/$1', ['as' => 'contacts.delete', 'filter' => 'permission:contacts.manage']);
});
