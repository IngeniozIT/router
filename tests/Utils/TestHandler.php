<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Tests\Utils;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\{
    ResponseFactoryInterface,
    StreamFactoryInterface,
    ServerRequestInterface,
    ResponseInterface,
};

final readonly class TestHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createResponse()->withBody(
            $this->streamFactory->createStream('TEST'),
        );
    }
}
