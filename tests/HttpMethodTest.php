<?php

namespace IngeniozIT\Router\Tests;

use IngeniozIT\Router\Route;
use IngeniozIT\Router\RouteElement;
use IngeniozIT\Router\RouteGroup;
use IngeniozIT\Router\Tests\Utils\RouterCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

final class HttpMethodTest extends RouterCase
{
    #[DataProvider('providerMethodsAndRoutes')]
    public function testRoutesMatchRequestsBasedOnMethod(string $method, callable $routeCallable): void
    {
        /** @var RouteElement $route */
        $route = $routeCallable('/', static fn(): ResponseInterface => self::response('OK'));
        $request = self::serverRequest($method, '/');

        $response = $this->router(new RouteGroup(routes: [$route]))->handle($request);

        self::assertSame('OK', (string) $response->getBody());
    }

    /**
     * @return array<string, array{0: string, 1: callable}>
     */
    public static function providerMethodsAndRoutes(): array
    {
        return [
            'GET' => ['GET', Route::get(...)],
            'POST' => ['POST', Route::post(...)],
            'PUT' => ['PUT', Route::put(...)],
            'PATCH' => ['PATCH', Route::patch(...)],
            'DELETE' => ['DELETE', Route::delete(...)],
            'HEAD' => ['HEAD', Route::head(...)],
            'OPTIONS' => ['OPTIONS', Route::options(...)],
        ];
    }

    #[DataProvider('providerRouteMethods')]
    public function testRoutesCanMatchAnyMethod(string $method): void
    {
        $route = Route::any('/', static fn(): ResponseInterface => self::response('OK'));
        $request = self::serverRequest($method, '/');

        $response = $this->router(new RouteGroup(routes: [$route]))->handle($request);

        self::assertSame('OK', (string) $response->getBody());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function providerRouteMethods(): array
    {
        return [
            'GET' => ['GET'],
            'POST' => ['POST'],
            'PUT' => ['PUT'],
            'PATCH' => ['PATCH'],
            'DELETE' => ['DELETE'],
            'HEAD' => ['HEAD'],
            'OPTIONS' => ['OPTIONS'],
        ];
    }

    public function testRoutesCanMatchSomeMethods(): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                Route::some(['POST', 'PUT'], '/', static fn(): ResponseInterface => self::response('OK')),
                Route::any('/', static fn(): ResponseInterface => self::response('KO')),
            ],
        );
        $getRequest = self::serverRequest('GET', '/');
        $postRequest = self::serverRequest('POST', '/');
        $putRequest = self::serverRequest('PUT', '/');

        $getResult = $this->router($routeGroup)->handle($getRequest);
        $postResult = $this->router($routeGroup)->handle($postRequest);
        $putResult = $this->router($routeGroup)->handle($putRequest);

        self::assertSame('KO', (string) $getResult->getBody());
        self::assertSame('OK', (string) $postResult->getBody());
        self::assertSame('OK', (string) $putResult->getBody());
    }

    public function testMethodNameCanBeLowercase(): void
    {
        $route = Route::some(['delete'], '/', static fn(): ResponseInterface => self::response('OK'));
        $request = self::serverRequest('DELETE', '/');

        $result = $this->router(new RouteGroup(routes: [$route]))->handle($request);

        self::assertSame('OK', (string) $result->getBody());
    }
}
