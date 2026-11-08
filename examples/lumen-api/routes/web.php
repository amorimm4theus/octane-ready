<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/health', 'HealthController@show');

$router->get('/products', 'ProductController@index');
$router->get('/products/{id:[0-9]+}', 'ProductController@show');

$router->post('/orders', 'OrderController@store');
$router->get('/orders/{id:[0-9]+}', 'OrderController@show');
