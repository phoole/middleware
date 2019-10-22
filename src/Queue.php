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
 * Runnable queue of middlewares
 *
 * ```php
 * // init the queue with default handler(callable or RequestHandlerInterface)
 * $queue = new Queue(function($request) { return new Response(404)}; );
 *
 * // add middlewares
 * $queue->add(
 *     new SessionMiddleware(), // object
 *     function($request, $handler) { // callable
 *         $response = $handler->handle($request);
 *         $response->withStatusCode(301);
 *         return $response;
 *     },
 * );
 *
 * // run the queue
 * $queue->handle($request);
 * ```
 *
 * @package Phoole\Middleware
 */
class Queue implements RequestHandlerInterface, MiddlewareInterface
{
    /**
     * queue of the middleware or callables
     *
     * @var  MiddlewareInterface[]|callable[]
     */
    protected $middlewares = [];

    /**
     * default handler
     *
     * @var  RequestHandlerInterface|callable
     */
    protected $defaultHandler;

    /**
     * Constructor
     *
     * @param  RequestHandlerInterface|callable|null $defaultHandler
     */
    public function __construct($defaultHandler = null)
    {
        $this->defaultHandler = $defaultHandler;
    }

    /**
     * add middleware[s] to the queue
     *
     * @param  MiddlewareInterface|callable ...$middlewares
     * @return $this
     */
    public function add(...$middlewares): Queue
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
        $handler = $this->fixHandler($this->defaultHandler);
        foreach (array_reverse($this->middlewares) as $middleware) {
            $handler = new Handler($handler, $middleware);
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

    /**
     * convert to standard RequestHandlerInterface
     *
     * @var    RequestHandlerInterface|callable|null $handler
     * @return RequestHandlerInterface
     * @throws \LogicException
     */
    protected function fixHandler($handler): RequestHandlerInterface
    {
        if ($handler instanceof RequestHandlerInterface) {
            return $handler;
        }

        if (is_callable($handler)) {
            return new Handler($handler);
        }

        throw new \LogicException('unknown type of default handler');
    }
}
