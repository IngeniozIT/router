# Router

A PHP router.

## Documentation

### Configuring the Router

#### Overview

Here is a quick reference sample of how to configure the routes:

```php
$routes = new RouteGroup(
    routes: [
        Route::get(path: '/hello', callback: () => 'Hello, world!', name: 'hello'),
        // Users
        Route::get(path: '/users', callback: ListUsersHandler::class, name: 'users.list'),
        Route::post(path: '/users/{id:[0-9]+}', callback: new CreateUserHandler(), name: 'user.create'),
        // Admin
        new RouteGroup(
            routes: [
                Route::get(path: '/admin', callback: PreviewAllPostsHandler::class, name: 'admin.index'),
                Route::get(path: '/admin/logout', callback: AdminLogoutHandler::class, name: 'admin.logout'),
            ],
            conditions: ['IsAdmin'],
        ),
        // Web
        Route::get(path: '{page}', callback: PageHandler::class, name: 'page'),
        Route::get(path: '{page}', callback: PageNotFoundHandler::class, name: 'page.not_found'),
    ],
    middlewares: [
        ExceptionHandler::class,
        RedirectionHandler::class,
    ],
    patterns: ['page' => '.*'],
);
```

#### Path

The path can contain parameters, which are enclosed in curly braces:

```php
new RouteGroup([
    Route::get(path: '/users/{id}', callback: /* handler */),
]);
```

By default, the parameters match any character except `/`.

To match a parameter with a different pattern, use a regular expression:

```php
new RouteGroup([
    Route::get(path: '/users/{id}', callback: /* handler */, patterns: ['id' => '[0-9]+']),
]);
```

If you have a parameter that is used in multiple routes, you can define it in the `RouteGroup`. It will be used in all the routes of the group:

```php
new RouteGroup(
    routes: [
        Route::get(path: '/users/{id}/posts/{postId}', callback: /* handler */),
        Route::get(path: '/users/{id}/comments/{commentId}', callback: /* handler */),
    ],
    patterns: ['id' => '[0-9]+'],
);
```

#### HTTP Method

The `Route` class provides static methods to create routes to match each HTTP method:

```php
new RouteGroup([
    Route::get(/* ... */),
    Route::post(/* ... */),
    Route::put(/* ... */),
    Route::patch(/* ... */),
    Route::delete(/* ... */),
    Route::head(/* ... */),
    Route::options(/* ... */),
    Route::any(/* ... */), // mathes all HTTP methods
    Route::some(['GET', 'POST'], /* ... */), // matches only GET and POST
]);
```

#### Handlers

The handler can be a callable, a PSR-15 `RequestHandlerInterface`, a PSR-15 `MiddlewareInterface`, or a string.
    
```php
new RouteGroup([
    Route::get(path: '/baz', callback: () => 'Hello, world!'),
    Route::get(path: '/bar', callback: new Handler()),
    Route::get(path: '/foo', callback: Handler::class),
]);
```

If the handler is a string, the container will be used to resolve it.

If the handler is a middleware, calling the next handler will continue the routing:

```php
new RouteGroup([
    Route::get(path: '/', callback: ($request, $handler) => $handler->handle($request)), // Will delegate to the next route
    Route::get(path: '/', callback: () => 'Hello, world!'),
]);
```


#### Name

You can name a route:

```php
new RouteGroup([
    Route::get(path: '/', callback: /* handler */, name: 'home'),
    Route::get(path: '/users', callback: /* handler */, name: 'users'),
]);
```

#### Middlewares

You can add middlewares to a route group:

```php
new RouteGroup(
    route: [
        Route::get(path: '/', callback: /* handler */),
    ],
    middlewares: [
        new MyMiddleware(),
        MyMiddleware::class,
        ($request, $handler) => $handler->handle($request),
    ],
);
```

A middleware can be a PSR-15 `MiddlewareInterface`, a string, or a callable.

If the middleware is a string, the container will be used to resolve it.

If the middleware is a callable, it will be called with the request and the next handler as arguments.

#### Subgroups

You can nest route groups:

```php
new RouteGroup(
    routes: [
        Route::get(path: '/foo', callback: /* handler */),
        new RouteGroup(
            routes: [
                Route::get(path: '/bar', callback: /* handler */),
                Route::get(path: '/baz', callback: /* handler */),
            ],
        ),
    ],
);
```

#### Conditions

You can add conditions to a route group. The conditions are checked before the route group is parsed.

Conditions take the request as argument. They can either return `false` if the request does not match the conditions, or an array of parameters to inject into the request.

```php
new RouteGroup(
    routes: [
        new RouteGroup(
            conditions: [
                // The request must have the header 'X-Is-Admin'
                fn ($request) => $request->hasHeader('X-Is-Admin') ? ['IsAdmin' => true] : false,
            ],
            routes: [
                Route::get(path: '/admin-stuff', callback: /* handler */),
            ],
        ),
        Route::get(path: '/foo', callback: /* handler */),
    ],
);
```

If the request does not match the condition, the route group will be skipped.

If a condition is a string, the container will be used to resolve it.

### Using the Router

#### Creating the router

The `Router` uses a `RouteGroup` to store the routes and a PSR-11 `ContainerInterface` to inject dependencies into the route handlers.

```php
use IngeniozIT\Router\Router;
use IngeniozIT\Router\RouteGroup;

$container = /* PSR ContainerInterface */;
$routeGroup = new RouteGroup([/* routes */]);

$router = new Router($routeGroup, $container);
```

#### Routing a request

The `Router` uses a PSR-7 `ServerRequestInterface` to route the request.

It returns a PSR-7 `ResponseInterface`.

```php
$request = /* PSR ServerRequestInterface */;
$response = $router->handle($request);
```
