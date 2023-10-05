<?php

declare(strict_types=1);

namespace IngeniozIT\Router;

use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\{RequestHandlerInterface, MiddlewareInterface};

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
    public function matches(ServerRequestInterface $request): false|array
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
