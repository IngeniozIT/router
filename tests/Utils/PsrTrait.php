<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Tests\Utils;

use IngeniozIT\Edict\Container;
use IngeniozIT\Http\Message\{ResponseFactory, ServerRequestFactory, StreamFactory, UploadedFileFactory, UriFactory,};
use Psr\Http\Message\{ResponseFactoryInterface,
    ResponseInterface,
    ServerRequestFactoryInterface,
    ServerRequestInterface,
    StreamFactoryInterface,
    UploadedFileFactoryInterface,
    UriFactoryInterface,};

use function IngeniozIT\Edict\value;

trait PsrTrait
{
    protected static function responseFactory(): ResponseFactoryInterface
    {
        return new ResponseFactory(self::streamFactory());
    }

    protected static function streamFactory(): StreamFactoryInterface
    {
        return new StreamFactory();
    }

    protected static function uriFactory(): UriFactoryInterface
    {
        return new UriFactory();
    }

    protected static function uploadedFileFactory(): UploadedFileFactoryInterface
    {
        return new UploadedFileFactory(
            self::streamFactory(),
        );
    }

    protected static function serverRequestFactory(): ServerRequestFactoryInterface
    {
        return new ServerRequestFactory(
            self::streamFactory(),
            self::uriFactory(),
            self::uploadedFileFactory(),
        );
    }

    protected static function serverRequest(string $method, string $uri): ServerRequestInterface
    {
        return self::serverRequestFactory()->createServerRequest($method, $uri);
    }

    protected static function response(string $content): ResponseInterface
    {
        return self::responseFactory()->createResponse()->withBody(
            self::streamFactory()->createStream($content),
        );
    }

    protected static function container(): Container
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
