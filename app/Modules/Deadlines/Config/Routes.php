<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('terminy', 'DeadlineController::index', ['namespace' => 'Modules\Deadlines\Controllers', 'as' => 'deadlines', 'filter' => 'permission:deadlines.view']);
