<?php

declare(strict_types=1);

namespace Phoole\Tests;

use Phoole\Middleware\Queue;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
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
        $this->obj = new Queue(new Response(404));
        $this->ref = new \ReflectionClass(get_class($this->obj));
    }

    protected function tearDown(): void
    {
        $this->obj = $this->ref = NULL;
        parent::tearDown();
    }

    protected function invokeMethod($methodName, array $parameters = array())
    {
        $method = $this->ref->getMethod($methodName);
        $method->setAccessible(TRUE);
        return $method->invokeArgs($this->obj, $parameters);
    }

    /**
     * @covers Phoole\Middleware\Queue::add()
     */
    public function testAdd()
    {
        $this->expectOutputString('Middle2Middle3Middle1');
        $this->obj->add(new MiddleOne());
        $this->obj->add(new MiddleTwo());
        $this->obj->add(
            function(ServerRequestInterface $request, RequestHandlerInterface $handler) {
                echo "Middle3";
                return $handler->handle($request);
            }
        );
        $res = $this->obj->handle(new ServerRequest('GET', 'http://bingo.com/get'));
        $this->assertEquals(404, $res->getStatusCode());
    }

    /**
     * @covers Phoole\Middleware\Queue::process()
     */
    public function testProcess()
    {
        $this->expectOutputString('Middle2Middle1');

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
        // no default handler set
        $this->expectExceptionMessage('unknown type of default handler');
        $obj = new Queue();
        $obj->add(new MiddleOne());
        $obj->add(new MiddleTwo());
        $res = $obj->handle(new ServerRequest('GET', 'http://bingo.com/get'));
    }

    /**
     * @covers Phoole\Middleware\Queue::handle()
     */
    public function testHandle2()
    {
        // callable handler
        $obj = new Queue(new Response(404));
        $this->expectOutputString('Middle1');
        $obj->add(new MiddleOne());
        $res = $obj->handle(new ServerRequest('GET', 'http://bingo.com/get'));
    }
}