<?php

declare(strict_types=1);

namespace Phoole\Tests;

use Phoole\Middleware\Queue;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MiddleOne implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $res = $handler->handle($request);
        echo "Middle1";
        return $res;
    }
}

class MiddleTwo implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        echo "Middle2";
        return $handler->handle($request);
    }
}

class QueueTest extends TestCase
{
    private $obj;
    private $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->obj = new Queue(
            new class implements RequestHandlerInterface 
            {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    echo "_X_";
                    return new Response(404);
                }
            }
        );
        $this->ref = new \ReflectionClass(get_class($this->obj));
    }

    protected function tearDown(): void
    {
        $this->obj = $this->ref = null;
        parent::tearDown();
    }

    protected function invokeMethod($methodName, array $parameters = array())
    {
        $method = $this->ref->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->obj, $parameters);
    }

    /**
     * @covers Phoole\Middleware\Queue::add()
     */
    public function testAdd()
    {
        $this->expectOutputString('Middle2_X_Middle1');
        $this->obj->add(new MiddleOne());
        $this->obj->add(new MiddleTwo());
        $res = $this->obj->handle(new ServerRequest('GET', 'http://bingo.com/get'));
        $this->assertEquals(404, $res->getStatusCode());
    }

    /**
     * @covers Phoole\Middleware\Queue::process()
     */
    public function testProcess()
    {
        $this->expectOutputString('Middle2_X_Middle1');

        // queue as a middleware
        $queue = new Queue();
        $queue->add(new MiddleTwo());
        $this->obj->add($queue);

        $this->obj->add(new MiddleOne());
        
        $res = $this->obj->handle(new ServerRequest('GET', 'http://bingo.com/get'));
        $this->assertEquals(404, $res->getStatusCode());
    }

    /**
     * @covers Phoole\Middleware\Queue::handle()
     */
    public function testHandle()
    {
        $this->expectExceptionMessage('default handler not set');
        $obj = new Queue();
        $obj->add(new MiddleOne());
        $obj->add(new MiddleTwo());
        $res = $obj->handle(new ServerRequest('GET', 'http://bingo.com/get'));
    }
}