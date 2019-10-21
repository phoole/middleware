<?php

/**
 * Phoole (PHP7.2+)
 *
 * @category  Library
 * @package   Phoole\Middleware
 * @copyright Copyright (c) 2019 Hong Zhang
 */
declare(strict_types=1);

namespace Phoole\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Queue
 *
 * Queue of middlewares
 *
 * @package Phoole\Middleware
 */
class Queue implements RequestHandlerInterface, MiddlewareInterface
{
    /**
     * @var  MiddlewareInterface[]
     */
    protected $middlewares = [];

    /**
     * @var  RequestHandlerInterface
     */
    protected $defaultHandler;

    /**
     * Constructor
     *
     * @param  RequestHandlerInterface $defaultHandler
     */
    public function __construct(RequestHandlerInterface $defaultHandler = null)
    {
        $this->defaultHandler = $defaultHandler;
    }

    /**
     * add middleware[s] to the queue
     *
     * @param  MiddlewareInterface ...$middlewares
     * @return $this
     */
    public function add(MiddlewareInterface ...$middlewares): Queue
    {
        foreach ($middlewares as $m) {
            $this->middlewares[] = $m;
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \LogicException if default handler not set
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (is_null($this->defaultHandler)) {
            throw new \LogicException('default handler not set');
        }

        $handler = $this->defaultHandler;
        foreach (array_reverse($this->middlewares) as $middleware) {
            $handler = new Handler($middleware, $handler);
        }
        return $handler->handle($request);
    }

    /**
     * {@inheritDoc}
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $this->defaultHandler = $handler;
        return $this->handle($request);
    }
}
