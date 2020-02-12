<?php

namespace App\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;

/**
 * Class TestRequestHandler
 *
 * @package TestApp\Http
 */
class TestRequestHandler implements RequestHandlerInterface
{
    public $callable;

    public $request;

    /**
     * TestRequestHandler constructor.
     *
     * @param callable|null $callable
     */
    public function __construct(?callable $callable = null)
    {
        $this->callable = $callable ?: function ($request) {
            return new Response();
        };
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        return ($this->callable)($request);
    }
}
