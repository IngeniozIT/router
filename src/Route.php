<?php

declare(strict_types=1);

namespace IngeniozIT\Router;

use IngeniozIT\Router\Route\RouteElement;

use function array_reduce;
use function strtoupper;

final readonly class Route
{
    public const GET = 0b0000001;

    public const POST = 0b0000010;

    public const PUT = 0b0000100;

    public const PATCH = 0b0001000;

    public const DELETE = 0b0010000;

    public const HEAD = 0b0100000;

    public const OPTIONS = 0b1000000;

    public const ANY = 0b1111111;

    public const METHODS = [
        'GET' => self::GET,
        'POST' => self::POST,
        'PUT' => self::PUT,
        'PATCH' => self::PATCH,
        'DELETE' => self::DELETE,
        'HEAD' => self::HEAD,
        'OPTIONS' => self::OPTIONS,
    ];

    /**
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function get(string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): RouteElement
    {
        return new RouteElement(self::GET, $path, $callback, $where, $with, $name);
    }

    /**
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function post(string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): RouteElement
    {
        return new RouteElement(self::POST, $path, $callback, $where, $with, $name);
    }

    /**
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function put(string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): RouteElement
    {
        return new RouteElement(self::PUT, $path, $callback, $where, $with, $name);
    }

    /**
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function patch(string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): RouteElement
    {
        return new RouteElement(self::PATCH, $path, $callback, $where, $with, $name);
    }

    /**
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function delete(string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): RouteElement
    {
        return new RouteElement(self::DELETE, $path, $callback, $where, $with, $name);
    }

    /**
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function head(string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): RouteElement
    {
        return new RouteElement(self::HEAD, $path, $callback, $where, $with, $name);
    }

    /**
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function options(string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): RouteElement
    {
        return new RouteElement(self::OPTIONS, $path, $callback, $where, $with, $name);
    }

    /**
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function any(string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): RouteElement
    {
        return new RouteElement(self::ANY, $path, $callback, $where, $with, $name);
    }

    /**
     * @param string[] $methods
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function some(array $methods, string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): RouteElement
    {
        $method = array_reduce($methods, static fn($carry, $methodString): int => $carry | self::METHODS[strtoupper($methodString)], 0);

        return new RouteElement($method, $path, $callback, $where, $with, $name);
    }
}
