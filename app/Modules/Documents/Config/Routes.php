<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('dokumenty', ['namespace' => 'Modules\Documents\Controllers'], static function (RouteCollection $routes): void {
    $routes->get('/', 'DocumentController::index', ['as' => 'documents', 'filter' => 'permission:documents.view']);
    $routes->get('karticky', 'DocumentController::cards', ['as' => 'documents.cards', 'filter' => 'permission:health.view']);
    $routes->get('novy', 'DocumentController::new', ['as' => 'documents.new', 'filter' => 'permission:documents.manage']);
    $routes->post('/', 'DocumentController::create', ['as' => 'documents.create', 'filter' => 'permission:documents.manage']);
    $routes->get('(:num)', 'DocumentController::show/$1', ['as' => 'documents.show', 'filter' => 'permission:documents.view']);
    $routes->get('(:num)/upravit', 'DocumentController::edit/$1', ['as' => 'documents.edit', 'filter' => 'permission:documents.manage']);
    $routes->post('(:num)', 'DocumentController::update/$1', ['as' => 'documents.update', 'filter' => 'permission:documents.manage']);
    $routes->post('(:num)/zmazat', 'DocumentController::delete/$1', ['as' => 'documents.delete', 'filter' => 'permission:documents.manage']);
    $routes->get('(:num)/subor/(:num)', 'DocumentController::file/$1/$2', ['as' => 'documents.file', 'filter' => 'permission:documents.view']);
    $routes->get('(:num)/subor/(:num)/nahlad', 'DocumentController::thumb/$1/$2', ['as' => 'documents.thumb', 'filter' => 'permission:documents.view']);
    $routes->post('(:num)/subor/(:num)/zmazat', 'DocumentController::deleteFile/$1/$2', ['as' => 'documents.file.delete', 'filter' => 'permission:documents.manage']);
});
