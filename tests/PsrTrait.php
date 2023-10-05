<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Tests;

use Psr\Http\Message\{
    ResponseFactoryInterface,
    ResponseInterface,
    ServerRequestFactoryInterface,
    ServerRequestInterface,
    StreamFactoryInterface,
    UploadedFileFactoryInterface,
    UriFactoryInterface,
};
use Psr\Container\ContainerInterface;
use IngeniozIT\Http\Message\{
    ResponseFactory,
    ServerRequestFactory,
    StreamFactory,
    UploadedFileFactory,
    UriFactory,
};
use IngeniozIT\Edict\Container;

use function IngeniozIT\Edict\value;

trait PsrTrait
{
    private static function responseFactory(): ResponseFactoryInterface
    {
        return new ResponseFactory(self::streamFactory());
    }

    private static function streamFactory(): StreamFactoryInterface
    {
        return new StreamFactory();
    }

    private static function uriFactory(): UriFactoryInterface
    {
        return new UriFactory();
    }

    private static function uploadedFileFactory(): UploadedFileFactoryInterface
    {
        return new UploadedFileFactory(
            self::streamFactory(),
        );
    }

    private static function serverRequestFactory(): ServerRequestFactoryInterface
    {
        return new ServerRequestFactory(
            self::streamFactory(),
            self::uriFactory(),
            self::uploadedFileFactory(),
        );
    }

    private static function serverRequest(string $method, string $uri): ServerRequestInterface
    {
        return self::serverRequestFactory()->createServerRequest($method, $uri);
    }

    private static function response(string $content): ResponseInterface
    {
        return self::responseFactory()->createResponse()->withBody(
            self::streamFactory()->createStream($content),
        );
    }

    private static function container(): ContainerInterface
    {
        $container = new Container();

        $container->setMany([
            ResponseFactoryInterface::class => value(self::responseFactory()),
            StreamFactoryInterface::class => value(self::streamFactory()),
            UriFactoryInterface::class => value(self::uriFactory()),
            UploadedFileFactoryInterface::class => value(self::uploadedFileFactory()),
            ServerRequestFactoryInterface::class => value(self::serverRequestFactory()),
        ]);

        return $container;
    }
}
