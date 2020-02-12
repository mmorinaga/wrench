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

namespace Wrench\Middleware;

use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Wrench\Mode\Exception\MissingModeException;
use Wrench\Mode\Mode;

/**
 * Middleware responsible of intercepting request to
 * deal with the application being under maintenance
 */
class MaintenanceMiddleware implements MiddlewareInterface
{
    use InstanceConfigTrait;

    /**
     * Configuration of the mode for this instance of the middleware
     *
     * @var \Wrench\Mode\Mode
     */
    protected $_mode;

    /**
     * Default config
     *
     * @var array
     */
    protected $_defaultConfig = [
        'when' => null,
        'for' => null,
        'priority' => 1,
        'mode' => [
            'className' => 'Wrench\Mode\Redirect',
            'config' => []
        ],
        'whitelist' => []
    ];

    /**
     * {@inheritDoc}
     *
     * @throws \Wrench\Mode\Exception\MissingModeException When the specified mode can not be loaded
     */
    public function __construct($config = [])
    {
        $this->setConfig($config);
        $mode = $this->_config['mode'];

        if (is_array($mode)) {
            $className = $this->_config['mode']['className'];
            if (empty($className)) {
                throw new MissingModeException(['mode' => '']);
            }

            $config = $this->_config['mode']['config'];
            $middlewareConfig = !empty($config) ? $config : [];
            $this->setMode($className, $middlewareConfig);

            return;
        }

        if ($mode instanceof Mode) {
            $this->setMode($mode);
        }
    }

    /**
     * Serve the maintenance mode if it is enabled and properly configured.
     * If the Configure parameter `Wrench.enable` is set to `false`, the maintenance mode will not be served.
     * If it is set to `true` but the IP of the client is in the (optional) whitelist, the maintenance mode will not be
     * served. The gives the opportunity for an application maintainer to see the application running normally in case
     * the maintenance mode is enabled.
     *
     * @param \Psr\Http\Message\ServerRequestInterface|\Cake\Http\ServerRequest $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The response.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $clientIp = $this->getClientIp($request);
        if (!Configure::read('Wrench.enable') || $this->isWhitelisted($clientIp)) {
            return $handler->handle($request);
        }

        $response = $this->getMode()->process($request, $handler);

        if ($response instanceof ResponseInterface) {
            return $response;
        }

        return $handler->handle($request);
    }

    /**
     * Sets the mode instance. If a string is passed it will be treated
     * as a class name and will be instantiated.
     *
     * @param \Wrench\Mode\Mode|string $mode The mode instance to use.
     * @param array $config Either config for a new driver or null.
     * @return \Wrench\Mode\Mode
     *
     * @throws \Wrench\Mode\Exception\MissingModeException When the specified mode can not be loaded
     */
    public function setMode($mode, $config = [])
    {
        if (is_string($mode)) {
            if (!class_exists($mode)) {
                throw new MissingModeException(['mode' => $mode]);
            }

            $mode = new $mode($config);
        }

        return $this->_mode = $mode;
    }

    /**
     * Return the current mode instance.
     *
     * @throws \Wrench\Mode\Exception\MissingModeException When the specified mode can not be loaded
     */
    public function getMode()
    {
        return $this->_mode;
    }

    /**
     * Checks the whitelist against the current session IP.
     *
     * @param string $ip IP the client is using to connect to the app.
     * @return bool True if the IP should bypass the maintenance mode.
     */
    protected function isWhitelisted($ip)
    {
        foreach ($this->_config['whitelist'] as $bypassIp) {
            if ($ip === $bypassIp) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the client IP.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request used.
     * @return string IP of the client.
     */
    protected function getClientIp($request)
    {
        if (method_exists($request, 'clientIp')) {
            return $request->clientIp();
        }

        // @codeCoverageIgnoreStart
        if ($request instanceof ServerRequestInterface) {
            $ip = '';
            $serverParams = $request->getServerParams();
            if (!empty($serverParams['HTTP_CLIENT_IP'])) {
                $ip = $serverParams['HTTP_CLIENT_IP'];
            } elseif (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
                $ip = $serverParams['HTTP_X_FORWARDED_FOR'];
            } elseif (!empty($serverParams['REMOTE_ADDR'])) {
                $ip = $serverParams['REMOTE_ADDR'];
            }

            return $ip;
        }
        // @codeCoverageIgnoreEnd

        return '';
    }
}
