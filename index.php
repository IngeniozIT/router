<?php

require 'vendor/autoload.php';

use IngeniozIT\Router\Route;
use IngeniozIT\Router\RouteGroup;

$routes = new RouteGroup(
    path: '/api/distributor/v1',
    middlewares: [
        SetCorsHeadersMiddleware::class,
        DistributorErrorMiddleware::class,
        MaintenanceMiddleware::class,
        ApiCheckConfigurationEnabledMiddleware::class,
    ],
    routes: [
        new RouteGroup(
            path: '/oauth2',
            middlewares: [
                CheckApiKeyAuthenticationModeMiddleware::class,
                DistributorScopesMiddleware::class,
            ],
            routes: [
                Route::post('/token', DistributorOAuth2Controller::class),
                Route::get(
                    '/public-keys/latest',
                    PublicKeyController::class,
                    name: 'bar',
                    where: ['foo' => 'bar'],
                    with: ['foo' => 'bar'],
                ),
            ],
            name: 'foo',
            where: ['bar' => 'baz'],
            with: ['bar' => 'baz'],
            conditions: [
                IsLoggedAsAdmin::class,
            ],
        ),
    ],
);


class AdminIndexController
{

}

class AdminUserController
{

}

class IsLoggedAsAdmin
{

}

class AdminMiddleware
{

}

print_r($routes);