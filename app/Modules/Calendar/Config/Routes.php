<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('kalendar', ['namespace' => 'Modules\Calendar\Controllers'], static function (RouteCollection $routes): void {
    $routes->get('/', 'CalendarController::index', ['as' => 'calendar', 'filter' => 'permission:calendar.view']);
    $routes->get('mesiac/(:num)/(:num)', 'CalendarController::month/$1/$2', ['as' => 'calendar.month', 'filter' => 'permission:calendar.view']);
    $routes->get('nova', 'CalendarController::new', ['as' => 'events.new', 'filter' => 'permission:calendar.manage']);
    $routes->post('/', 'CalendarController::create', ['as' => 'events.create', 'filter' => 'permission:calendar.manage']);
    $routes->get('udalost/(:num)', 'CalendarController::show/$1', ['as' => 'events.show', 'filter' => 'permission:calendar.view']);
    $routes->get('udalost/(:num)/upravit', 'CalendarController::edit/$1', ['as' => 'events.edit', 'filter' => 'permission:calendar.manage']);
    $routes->post('udalost/(:num)', 'CalendarController::update/$1', ['as' => 'events.update', 'filter' => 'permission:calendar.manage']);
    $routes->post('udalost/(:num)/zmazat', 'CalendarController::delete/$1', ['as' => 'events.delete', 'filter' => 'permission:calendar.manage']);

    $routes->get('zdroje', 'SourceController::index', ['as' => 'calendar.sources', 'filter' => 'permission:calendar.manage']);
    $routes->post('zdroje', 'SourceController::create', ['as' => 'calendar.sources.create', 'filter' => 'permission:calendar.manage']);
    $routes->post('zdroje/(:num)/sync', 'SourceController::sync/$1', ['as' => 'calendar.sources.sync', 'filter' => 'permission:calendar.manage']);
    $routes->post('zdroje/(:num)/zmazat', 'SourceController::delete/$1', ['as' => 'calendar.sources.delete', 'filter' => 'permission:calendar.manage']);
});
