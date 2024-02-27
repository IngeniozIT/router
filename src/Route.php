<?php

declare(strict_types=1);

namespace IngeniozIT\Router;

use Psr\Http\Message\ServerRequestInterface;

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
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function get(string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): self
    {
        return new self(self::GET, $path, $callback, $where, $with, $name);
    }

    /**
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function post(string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): self
    {
        return new self(self::POST, $path, $callback, $where, $with, $name);
    }

    /**
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function put(string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): self
    {
        return new self(self::PUT, $path, $callback, $where, $with, $name);
    }

    /**
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function patch(string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): self
    {
        return new self(self::PATCH, $path, $callback, $where, $with, $name);
    }

    /**
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function delete(string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): self
    {
        return new self(self::DELETE, $path, $callback, $where, $with, $name);
    }

    /**
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function head(string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): self
    {
        return new self(self::HEAD, $path, $callback, $where, $with, $name);
    }

    /**
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function options(string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): self
    {
        return new self(self::OPTIONS, $path, $callback, $where, $with, $name);
    }

    /**
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function any(string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): self
    {
        return new self(self::ANY, $path, $callback, $where, $with, $name);
    }

    /**
     * @param string[] $methods
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public static function some(array $methods, string $path, mixed $callback, array $where = [], array $with = [], ?string $name = null): self
    {
        $method = 0;
        foreach ($methods as $methodString) {
            $method |= self::METHODS[strtoupper($methodString)];
        }

        return new self($method, $path, $callback, $where, $with, $name);
    }

    /**
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public function __construct(
        public int $method,
        public string $path,
        public mixed $callback,
        public array $where = [],
        public array $with = [],
        public ?string $name = null,
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

        $path = $request->getUri()->getPath();
        $parameters = $this->extractParametersFromPath($this->path);

        if ($parameters === []) {
            return $path === $this->path ? [] : false;
        }

        $extractedParameters = $this->extractParametersValue($parameters, $path);
        return $extractedParameters === [] ? false : $extractedParameters;
    }

    private function httpMethodMatches(string $method): bool
    {
        return ($this->method & self::METHODS[$method]) !== 0;
    }

    /**
     * @return string[][]
     */
    private function extractParametersFromPath(string $path): array
    {
        preg_match_all('/{([^:]+)(?::(.+))?}/U', $path, $matches, PREG_SET_ORDER);
        return $matches;
    }

    /**
     * @param string[][] $parameters
     * @return array<string, string>
     */
    private function extractParametersValue(array $parameters, string $path): array
    {
        preg_match($this->buildRegex($parameters), $path, $parameters);
        return array_filter($parameters, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param string[][] $parameters
     */
    private function buildRegex(array $parameters): string
    {
        $regex = '#' . preg_quote($this->path, '#') . '#';
        foreach ($parameters as $parameter) {
            $regex = str_replace(
                preg_quote($parameter[0], '#'),
                '(?<' . $parameter[1] . '>' . ($parameter[2] ?? $this->where[$parameter[1]] ?? '[^/]+') . ')',
                $regex
            );
        }

        return $regex;
    }
}
