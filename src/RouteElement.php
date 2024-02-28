<?php

declare(strict_types=1);

namespace IngeniozIT\Router;

use IngeniozIT\Router\Exception\InvalidRouteParameter;
use IngeniozIT\Router\Exception\MissingRouteParameters;
use Psr\Http\Message\ServerRequestInterface;

final readonly class RouteElement
{
    public string $path;
    public bool $hasParameters;
    /** @var array<string, string> */
    public array $where;
    /** @var string[] */
    public array $parameters;
    public ?string $regex;

    /**
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public function __construct(
        public int $method,
        string $path,
        public mixed $callback,
        array $where = [],
        public array $with = [],
        public ?string $name = null,
    ) {
        $this->hasParameters = str_contains($path, '{');
        [$this->parameters, $this->where, $this->path] = $this->extractPatterns($where, $path);
        $this->regex = $this->buildRegex();
    }

    /**
     * @param array<string, string> $where
     * @return array{0: string[], 1: array<string, string>, 2: string}
     */
    private function extractPatterns(array $where, string $path): array
    {
        $parameters = [];
        if (preg_match_all('#{(\w+)(?::([^}]+))?}#', $path, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $parameters[] = $match[1];
                if (isset($match[2])) {
                    $path = str_replace($match[0], '{' . $match[1] . '}', $path);
                    $where[$match[1]] = $match[2];
                }
            }
        }
        return [$parameters, $where, $path];
    }

    private function buildRegex(): ?string
    {
        if (!$this->hasParameters) {
            return null;
        }

        $regex = $this->path;
        foreach ($this->parameters as $parameter) {
            $regex = str_replace(
                '{' . $parameter . '}',
                '(?<' . $parameter . '>' . $this->parameterPattern($parameter) . ')',
                $regex,
            );
        }

        return $regex;
    }

    /**
     * @return false|array<string, string>
     */
    public function match(ServerRequestInterface $request): false|array
    {
        if (!$this->httpMethodMatches($request->getMethod())) {
            return false;
        }

        return $this->pathMatches($request->getUri()->getPath());
    }

    private function httpMethodMatches(string $method): bool
    {
        return ($this->method & Route::METHODS[$method]) !== 0;
    }

    /**
     * @return false|array<string, string>
     */
    private function pathMatches(string $path): false|array
    {
        if (!$this->hasParameters) {
            return $path === $this->path ? [] : false;
        }

        if (!preg_match('#^' . $this->regex . '$#', $path, $matches)) {
            return false;
        }

        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param array<string, scalar> $parameters
     */
    public function buildPath(array $parameters): string
    {
        if (!$this->hasParameters) {
            return $this->path;
        }

        $this->validatePathParameters($parameters);

        $path = $this->path;
        $queryParameters = [];
        foreach ($parameters as $parameter => $value) {
            if (in_array($parameter, $this->parameters)) {
                $path = str_replace('{' . $parameter . '}', (string)$value, $path);
                continue;
            }
            $queryParameters[$parameter] = $value;
        }

        return $path . ($queryParameters ? '?' . http_build_query($queryParameters) : '');
    }

    /**
     * @param array<string, scalar> $parameters
     */
    private function validatePathParameters(array $parameters): void
    {
        $pathParameters = array_intersect(array_keys($parameters), $this->parameters);
        if (count($pathParameters) !== count($this->parameters)) {
            $missingParameters = array_diff($this->parameters, $pathParameters);
            throw new MissingRouteParameters($this->name ?? '', $missingParameters);
        }

        foreach ($this->parameters as $parameter) {
            if (!preg_match('#^' . $this->parameterPattern($parameter) . '$#', (string)$parameters[$parameter])) {
                throw new InvalidRouteParameter($this->name ?? '', $parameter, $this->parameterPattern($parameter));
            }
        }
    }

    private function parameterPattern(string $parameterName): string
    {
        return $this->where[$parameterName] ?? '[^/]+';
    }
}
