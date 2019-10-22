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
 * Handler
 * 
 * wrapper for middleware/callable/handler
 *
 * @package Phoole\Middleware
 */
class Handler implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface|callable
     */
    private $middleware;

    /**
     * @var RequestHandlerInterface|callable
     */
    private $handler;

    /**
     * @param RequestHandlerInterface|callable $handler
     * @param MiddlewareInterface|callable $middleware
     * 
     */
    public function __construct($handler, $middleware = null)
    {
        $this->handler = $handler;
        $this->middleware = $middleware;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // nulled middleware
        if (is_null($this->middleware)) {
            return ($this->handler)($request);
        }

        // middleware interface
        if ($this->middleware instanceof MiddlewareInterface) {
            return $this->middleware->process($request, $this->handler);
        }
        
        // callable middleware
        return ($this->middleware)($request, $this->handler);
    }
}
