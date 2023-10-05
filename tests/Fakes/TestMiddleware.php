<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Tests\Fakes;

use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Psr\Http\Message\{
    ResponseFactoryInterface,
    StreamFactoryInterface,
    ServerRequestInterface,
    ResponseInterface,
};

final readonly class TestMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->responseFactory->createResponse()->withBody(
            $this->streamFactory->createStream('TEST'),
        );
    }
}
