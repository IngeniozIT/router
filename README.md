# Router

A PHP Router.

## Disclaimer

In order to ensure that this package is easy to integrate into your app, it is built around the **PHP Standard Recommendations** : it takes in a [PSR-7 Server Request](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) and returns a [PSR-7 Response](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface). It also uses a [PSR-11 Container](https://www.php-fig.org/psr/psr-11/) (such as [EDICT](https://github.com/IngeniozIT/psr-container-edict)) to resolve the route handlers.

It is inspired by routers from well-known frameworks *(did anyone say Laravel ?)* aswell as some home-made routers used internally by some major companies.

It is build with quality in mind : readability, immutability, no global states, 100% code coverage, 100% mutation testing score, and validation from various static analysis tools at the highest level.

## About

| Info                | Value                                                                                                                                                                                                                                                                                                                                                                                     |
|---------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Latest release      | [![Packagist Version](https://img.shields.io/packagist/v/ingenioz-it/router)](https://packagist.org/packages/ingenioz-it/router)                                                                                                                                                                                                                                                          |
| Requires            | ![PHP from Packagist](https://img.shields.io/packagist/php-v/ingenioz-it/router.svg)                                                                                                                                                                                                                                                                                                      |
| License             | ![Packagist](https://img.shields.io/packagist/l/ingenioz-it/router)                                                                                                                                                                                                                                                                                                                       |
| Unit tests          | [![tests](https://github.com/IngeniozIT/router/actions/workflows/1-tests.yml/badge.svg)](https://github.com/IngeniozIT/router/actions/workflows/1-tests.yml)                                                                                                                                                                                                                              |
| Code coverage       | [![Code Coverage](https://codecov.io/gh/IngeniozIT/router/branch/master/graph/badge.svg)](https://codecov.io/gh/IngeniozIT/router)                                                                                                                                                                                                                                                        |
| Code quality        | [![code-quality](https://github.com/IngeniozIT/router/actions/workflows/2-code-quality.yml/badge.svg)](https://github.com/IngeniozIT/router/actions/workflows/2-code-quality.yml)                                                                                                                                                                                                         |
| Quality tested with | [phpunit](https://github.com/sebastianbergmann/phpunit), [phan](https://github.com/phan/phan), [psalm](https://github.com/vimeo/psalm), [phpcs](https://github.com/squizlabs/PHP_CodeSniffer), [phpstan](https://github.com/phpstan/phpstan), [phpmd](https://github.com/phpmd/phpmd), [infection](https://github.com/infection/infection), [rector](https://github.com/rectorphp/rector) |

## Installation

```bash
composer require ingenioz-it/router
```

## Documentation

### Overview

Create your routes, instantiate the router and handle the request:

```php
use IngeniozIT\Router\RouteGroup;
use IngeniozIT\Router\Route;
use IngeniozIT\Router\Router;

// Create your routes
$routes = new RouteGroup([
    Route::get('/hello', fn() => new Response('Hello, world!')),
    Route::get('/bye', fn() => new Response('Goodbye, world!')),
]);

// Instantiate the router
$container = new Container();
$router = new Router($routes, $container);

// Handle the request
$request = new ServerRequest();
$response = $router->handle($request);
```

@todo continue working on the documentation (create a wiki ?)