<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use IngeniozIT\Router\{
    RouteGroup,
    Router,
    Route,
};
use Psr\Http\Message\ServerRequestInterface;
use Closure;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class RouterConditionTest extends TestCase
{
    use PsrTrait;

    private function router(RouteGroup $routeGroup, ?Closure $fallback = null): Router
    {
        return new Router($routeGroup, self::container(), $fallback);
    }

    /**
     * @dataProvider providerConditions
     */
    public function testCanHaveConditions(Closure $condition, string $expectedResponse): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                        Route::get(path: '/', callback: static fn(): ResponseInterface => self::response('TEST2')),
                    ],
            conditions: [$condition],
        );
        $request = self::serverRequest('GET', '/');

        $response = $this->router($routeGroup, static fn(): ResponseInterface => self::response('TEST'))->handle($request);

        self::assertEquals($expectedResponse, (string) $response->getBody());
    }

    /**
     * @return array<string, array{condition: Closure, expectedResponse: string}>
     */
    public static function providerConditions(): array
    {
        return [
            'valid condition executes routes' => [
                'condition' => static fn(): array => [],
                'expectedResponse' => 'TEST2',
            ],
            'invalid condition executes fallback' => [
                'condition' => static fn(): bool => false,
                'expectedResponse' => 'TEST',
            ],
        ];
    }

    public function testAddsConditionsParametersToRequest(): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                        Route::get(
                            path: '/',
                            callback: static fn(ServerRequestInterface $request): ResponseInterface =>
                            self::response(var_export($request->getAttribute('foo'), true))
                        ),
                    ],
            conditions: [
                        static fn(): array => ['foo' => 'bar'],
                    ],
        );
        $request = self::serverRequest('GET', '/');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals("'bar'", (string) $response->getBody());
    }
}
