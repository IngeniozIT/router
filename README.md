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

Here is the whole process of using this router :
- Create your routes
- Instantiate the router
- Handle the request:

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

/** @var Psr\Container\ContainerInterface $container */
$container = new Container();
$router = new Router($routes, $container);

// Handle the request

/** @var Psr\Http\Message\ServerRequestInterface $request */
$request = new ServerRequest();
/** @var Psr\Http\Message\ResponseInterface $response */
$response = $router->handle($request);
```

### Basic routing

The simplest route consists of a path and a handler.

The path is a string, and the handler is a callable that will be executed when the route is matched. The handler must return a PSR-7 ResponseInterface.

```php
Route::get('/hello', fn() => new Response('Hello, world!'));
```

### Organizing routes

Route groups are used to contain routes definitions.  
They also allows you to visually organize your routes according to your application's logic.

This is useful when you want to apply the same conditions, middlewares, or attributes to several routes at once (as we will see later).

```php
new RouteGroup([
    Route::get('/hello', fn() => new Response('Hello, world!')),
    Route::get('/bye', fn() => new Response('Goodbye, world!')),
]);
```

Route groups can be nested to create a hierarchy of routes that will inherit everything from their parent groups.

```php
new RouteGroup([
    Route::get('/', fn() => new Response('Welcome !')),
    new RouteGroup([
        Route::get('/hello', fn() => new Response('Hello, world!')),
        Route::get('/hello-again', fn() => new Response('Hello again, world!')),
    ]),
    Route::get('/bye', fn() => new Response('Goodbye, world!')),
]);
```

### HTTP methods

You can specify the HTTP method that the route should match:

```php
Route::get('/hello', MyHandler::class);
Route::post('/hello', MyHandler::class);
Route::put('/hello', MyHandler::class);
Route::patch('/hello', MyHandler::class);
Route::delete('/hello', MyHandler::class);
Route::options('/hello', MyHandler::class);
```

If you want a route to match multiple HTTP methods, you can use the `some` method:

```php
Route::some(['GET', 'POST'], '/hello', MyHandler::class);
```

You can also use the `any` method to match all HTTP methods:

```php
Route::any('/hello', MyHandler::class);
```

### Path parameters

#### Basic usage

You can define route parameters by using the `{}` syntax in the route path.

```php
Route::get('/hello/{name}', MyHandler::class);
```

The matched parameters will be available in the request attributes.

```php
class MyHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $name = $request->getAttribute('name');
        return new Response("Hello, $name!");
    }
}

Route::get('/hello/{name}', MyHandler::class);
```

#### Custom parameter patterns

By default, the parameters are matched by the `[^/]+` regex (any characters that are not a `/`).

You can specify a custom pattern by using the `where` parameter:

```php
// This route will only match if the name contains only letters
Route::get('/hello/{name}', MyHandler::class, where: ['name' => '[a-zA-Z]+']);
```

#### Custom parameter patterns in a group

Parameters patterns can also be defined globally for all routes inside a group:

```php
$routes = new RouteGroup(
    [
        Route::get('/hello/{name}', MyHandler::class),
        Route::get('/bye/{name}', MyOtherHandler::class),
    ],
    where: ['name' => '[a-zA-Z]+'],
);
```

### Route handlers

#### Closures

The simplest way to define a route handler is to use a closure.  
The closure must return a PSR-7 ResponseInterface.

```php
Route::get('/hello', fn() => new Response('Hello, world!'));
```

Closures can take in parameters: the request and a request handler (the router itself).

```php
Route::get('/hello', function (ServerRequestInterface $request) {
    return new Response('Hello, world!');
});

Route::get('/hello', function (ServerRequestInterface $request, RequestHandlerInterface $router) {
    return new Response('Hello, world!');
});
```

#### RequestHandlerInterface

A route handler can be a callable, but it can also be a PSR RequestHandlerInterface.

```php
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\ServerRequestInterface;
use Psr\Http\Server\ResponseInterface;

class MyHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new Response('Hello, world!');
    }
}

Route::get('/hello', new MyHandler());
```

#### MiddlewareInterface

Sometimes, you might want a handler to be able to "refuse" to handle the request, and pass it to the next handler in the chain.

This is done by using a PSR MiddlewareInterface as a route handler :

```php
use Psr\Http\Server\MiddlewareInterface;

class MyHandler implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (resourceDoesNotExist()) {
            // We don't want this handler to continue processing the request,
            // so we pass the responsability to the next handler
            return $handler->handle($request);
        }

        /* ... */
    }
}

$routes = new RouteGroup([
    // This handler will be called first
    Route::get('/{ressource}', fn() => new MyHandler()),
    // This handler will be called next
    Route::get('/{ressource}', fn() => new Response('Hello, world!')),
]);
```

#### Dependency injection

Instead of using a closure or a class instance, your handler can be a class name. The router will then resolve the class using the PSR container you injected into the router.

```php
Route::get('/hello', MyHandler::class);
```

*The router will resolve this handler by calling `get(MyHandler::class)` on the container. This means that you can use any value that the container can resolve into a valid route handler.*

### Additional attributes

You can add additional attributes to a route by using the `with` method.  
Just like path parameters, these attributes will be available in the request attributes.

```php
class MyHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $name = $request->getAttribute('name');
        return new Response("Hello, $name!");
    }
}

// Notice there is no name parameter in the route path
Route::get('/hello', MyHandler::class, with: ['name' => 'world']);
```

Attributes can also be defined globally for all the routes inside a group:

```php
$routes = new RouteGroup(
    [
        Route::get('/hello', MyHandler::class),
        Route::get('/bye', MyOtherHandler::class),
    ],
    with: ['name' => 'world'],
);
```

### Middlewares

### Conditions

### Naming routes

@todo continue working on the documentation
