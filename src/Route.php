<?php

declare(strict_types=1);

namespace IngeniozIT\Router;

use Psr\Http\Server\{RequestHandlerInterface, MiddlewareInterface};
use Psr\Http\Message\ServerRequestInterface;
use Closure;

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

    private const METHODS = [
        'GET' => self::GET,
        'POST' => self::POST,
        'PUT' => self::PUT,
        'PATCH' => self::PATCH,
        'DELETE' => self::DELETE,
        'HEAD' => self::HEAD,
        'OPTIONS' => self::OPTIONS,
    ];

    /**
     * @param array<string, string> $patterns
     */
    public static function get(string $path, Closure|string|RequestHandlerInterface|MiddlewareInterface $callback, ?string $name = null, array $patterns = []): self
    {
        return new self(self::GET, $path, $callback, $name, $patterns);
    }

    /**
     * @param array<string, string> $patterns
     */
    public static function post(string $path, Closure|string|RequestHandlerInterface|MiddlewareInterface $callback, ?string $name = null, array $patterns = []): self
    {
        return new self(self::POST, $path, $callback, $name, $patterns);
    }

    /**
     * @param array<string, string> $patterns
     */
    public static function put(string $path, Closure|string|RequestHandlerInterface|MiddlewareInterface $callback, ?string $name = null, array $patterns = []): self
    {
        return new self(self::PUT, $path, $callback, $name, $patterns);
    }

    /**
     * @param array<string, string> $patterns
     */
    public static function patch(string $path, Closure|string|RequestHandlerInterface|MiddlewareInterface $callback, ?string $name = null, array $patterns = []): self
    {
        return new self(self::PATCH, $path, $callback, $name, $patterns);
    }

    /**
     * @param array<string, string> $patterns
     */
    public static function delete(string $path, Closure|string|RequestHandlerInterface|MiddlewareInterface $callback, ?string $name = null, array $patterns = []): self
    {
        return new self(self::DELETE, $path, $callback, $name, $patterns);
    }

    /**
     * @param array<string, string> $patterns
     */
    public static function head(string $path, Closure|string|RequestHandlerInterface|MiddlewareInterface $callback, ?string $name = null, array $patterns = []): self
    {
        return new self(self::HEAD, $path, $callback, $name, $patterns);
    }

    /**
     * @param array<string, string> $patterns
     */
    public static function options(string $path, Closure|string|RequestHandlerInterface|MiddlewareInterface $callback, ?string $name = null, array $patterns = []): self
    {
        return new self(self::OPTIONS, $path, $callback, $name, $patterns);
    }

    /**
     * @param array<string, string> $patterns
     */
    public static function any(string $path, Closure|string|RequestHandlerInterface|MiddlewareInterface $callback, ?string $name = null, array $patterns = []): self
    {
        return new self(self::ANY, $path, $callback, $name, $patterns);
    }

    /**
     * @param string[] $methods
     * @param array<string, string> $patterns
     */
    public static function some(array $methods, string $path, Closure|string|RequestHandlerInterface|MiddlewareInterface $callback, ?string $name = null, array $patterns = []): self
    {
        $method = 0;
        foreach ($methods as $methodString) {
            $method |= self::METHODS[$methodString];
        }

        return new self($method, $path, $callback, $name, $patterns);
    }

    /**
     * @param array<string, string> $patterns
     */
    public function __construct(
        public int $method,
        public string $path,
        public Closure|string|RequestHandlerInterface|MiddlewareInterface $callback,
        public ?string $name = null,
        public array $patterns = [],
    ) {
    }

    /**
     * @return false|array<string, string>
     */
    public function match(ServerRequestInterface $request): false|array
    {
        if (!$this->httpMethodMatches($request->getMethod())) {
            return false;
        }

        return $request->getUri()->getPath() === $this->path ? [] : false;
    }

    private function httpMethodMatches(string $method): bool
    {
        return ($this->method & self::METHODS[$method]) !== 0;
    }
}
