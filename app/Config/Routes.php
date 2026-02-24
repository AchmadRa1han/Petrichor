<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'PlaylistController::index');
$routes->get('get-weather', 'PlaylistController::getWeather');
$routes->get('login', 'PlaylistController::login');
$routes->get('callback', 'PlaylistController::callback');
$routes->get('logout', 'PlaylistController::logout');
$routes->post('create-playlist', 'PlaylistController::createPlaylist');


