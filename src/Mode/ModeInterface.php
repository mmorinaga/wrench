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

namespace Wrench\Mode;

use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Interface ModeInterface
 *
 * @package Wrench\Mode
 */
interface ModeInterface
{
    /**
     * Main method that will be called if the MaintenanceModeFilter has to be used
     * This method should return the response that will be sent in order to warn the
     * user that the current request can not be processed because the app is undergoing
     * maintenance
     *
     * Maintenance modes should implement this method to return the proper response to the user.
     *
     * @param \Psr\Http\Message\ServerRequestInterface|\Cake\Http\ServerRequest $request
     *
     * @return \Psr\Http\Message\ResponseInterface|\Cake\Http\Response
     */
    public function process(ServerRequest $request): ResponseInterface;
}
