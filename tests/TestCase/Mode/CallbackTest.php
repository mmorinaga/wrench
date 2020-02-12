<?php
declare(strict_types=1);
/**
 * Copyright (c) Yves Piquel (http://www.havokinspiration.fr)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Yves Piquel (http://www.havokinspiration.fr)
 * @link          http://github.com/HavokInspiration/wrench
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Wrench\Test\TestCase\Mode;

use Cake\Core\Configure;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use App\Http\TestRequestHandler;
use Wrench\Middleware\MaintenanceMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

/**
 * Class CallbackTest
 *
 * @package Wrench\Test\TestCase\Mode
 */
class CallbackTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function tearDown() : void
    {
        parent::tearDown();
        Configure::write('Wrench.enable', false);
    }

    /**
     * Test the Callback filter mode
     *
     * @return void
     */
    public function testMaintenanceModeCallback()
    {
        Configure::write('Wrench.enable', true);
        $request = ServerRequestFactory::fromGlobals([
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $middleware = new MaintenanceMiddleware([
            'mode' => $this->_getModeConfig(),
        ]);
        $requestHandler = new TestRequestHandler();
        $middlewareResponse = $middleware->process($request, $requestHandler);

        $this->assertEquals('Some content from a callback', (string)$middlewareResponse->getBody());
        $this->assertEquals(503, $middlewareResponse->getStatusCode());
        $this->assertEquals('someValue', $middlewareResponse->getHeaderLine('someHeader'));
    }

    /**
     * Test the Callback filter mode with a wrong callable
     *
     * @return void
     */
    public function testMaintenanceModeCallbackException()
    {
        Configure::write('Wrench.enable', true);
        $request = ServerRequestFactory::fromGlobals([
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $middleware = new MaintenanceMiddleware([
            'mode' => [
                'className' => 'Wrench\Mode\Callback',
                'config' => [
                    'callback' => 'wonkycallable',
                ],
            ],
        ]);
        $requestHandler = new TestRequestHandler();

        $this->expectException(InvalidArgumentException::class);

        $middleware->process($request, $requestHandler);
    }

    /**
     * Test the Callback filter mode when using the "whitelist" option. Meaning the maintenance mode should not be shown
     * if the client IP is whitelisted.
     *
     * @return void
     */
    public function testMaintenanceModeCallbackWhitelist()
    {
        Configure::write('Wrench.enable', true);
        $request = ServerRequestFactory::fromGlobals([
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $middleware = new MaintenanceMiddleware([
            'whitelist' => ['127.0.0.1'],
            'mode' => $this->_getModeConfig(),
        ]);
        $requestHandler = new TestRequestHandler();

        $middlewareResponse = $middleware->process($request, $requestHandler);

        $this->assertEquals('', (string)$middlewareResponse->getBody());
        $this->assertEquals(200, $middlewareResponse->getStatusCode());
    }

    /**
     * Returns config for callback mode
     *
     * @return array
     */
    protected function _getModeConfig() : array
    {
        return [
            'className' => 'Wrench\Mode\Callback',
            'config' => [
                'callback' => function () {
                    $string = 'Some content from a callback';

                    $stream = new Stream(fopen('php://memory', 'r+'));
                    $stream->write($string);

                    return (new Response())
                        ->withBody($stream)
                        ->withStatus(503)->withHeader('someHeader', 'someValue');
                },
            ],
        ];
    }
}
