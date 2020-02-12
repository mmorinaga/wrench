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
use Cake\Http\Response;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use LogicException;
use App\Http\TestRequestHandler;
use Wrench\Middleware\MaintenanceMiddleware;

/**
 * Class OutputTest
 *
 * @package Wrench\Test\TestCase\Mode
 */
class OutputTest extends TestCase
{
    /**
     * @inheritDoc
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Configure::write('Wrench.enable', false);
    }

    /**
     * Test the Output filter mode without params
     * @return void
     */
    public function testOutputModeNoParams()
    {
        Configure::write('Wrench.enable', true);
        $request = ServerRequestFactory::fromGlobals([
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $middleware = new MaintenanceMiddleware([
            'mode' => [
                'className' => 'Wrench\Mode\Output'
            ]
        ]);

        $requestHandler = new TestRequestHandler();

        $middlewareResponse = $middleware->process($request, $requestHandler);

        $this->assertEquals(503, $middlewareResponse->getStatusCode());

        $content = file_get_contents(ROOT . DS . 'maintenance.html');
        $this->assertEquals($middlewareResponse->getBody(), $content);
    }

    /**
     * Test the Output filter mode with additional headers
     * @return void
     */
    public function testMaintenanceModeFilterOutputHeaders()
    {
        Configure::write('Wrench.enable', true);
        $request = ServerRequestFactory::fromGlobals([
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $middleware = new MaintenanceMiddleware([
            'mode' => [
                'className' => 'Wrench\Mode\Output',
                'config' => [
                    'code' => 404,
                    'headers' => ['someHeader' => 'someValue']
                ]
            ]
        ]);

        $requestHandler = new TestRequestHandler();

        $middlewareResponse = $middleware->process($request, $requestHandler);

        $this->assertEquals(404, $middlewareResponse->getStatusCode());

        $content = file_get_contents(ROOT . DS . 'maintenance.html');
        $this->assertEquals($middlewareResponse->getBody(), $content);

        $this->assertEquals('someValue', $middlewareResponse->getHeaderLine('someHeader'));
    }

    /**
     * Test the Output filter mode with a wrong file path : it should throw an
     * exception
     * @return void
     */
    public function testOutputModeCustomParams()
    {
        Configure::write('Wrench.enable', true);
        $request = ServerRequestFactory::fromGlobals([
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $middleware = new MaintenanceMiddleware([
            'mode' => [
                'className' => 'Wrench\Mode\Output',
                'config' => [
                    'path' => ROOT . DS . 'wonky.html'
                ]
            ]
        ]);

        $requestHandler = new TestRequestHandler();

        $this->expectException(LogicException::class);

        $middleware->process($request, $requestHandler);
    }

    /**
     * Test the Output filter mode without params when using the "whitelist" option. Meaning the maintenance mode should
     * not be shown if the client IP is whitelisted.
     *
     * @return void
     */
    public function testOutputModeWhitelist()
    {
        Configure::write('Wrench.enable', true);
        $request = ServerRequestFactory::fromGlobals([
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '127.0.0.1'
        ]);
        $middleware = new MaintenanceMiddleware([
            'whitelist' => ['127.0.0.1'],
            'mode' => [
                'className' => 'Wrench\Mode\Output'
            ]
        ]);

        $requestHandler = new TestRequestHandler();

        $middlewareResponse = $middleware->process($request, $requestHandler);

        $this->assertEquals(200, $middlewareResponse->getStatusCode());

        $this->assertEquals($middlewareResponse->getBody(), '');
    }
}
