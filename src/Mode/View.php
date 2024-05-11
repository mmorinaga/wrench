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
use Cake\View\ViewVarsTrait;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Stream;

/**
 * `View` Maintenance Mode.
 *
 * When used, it will render the defined View and use it as the body of the
 * response to return
 */
class View extends Mode
{
    use ViewVarsTrait;

    /**
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $request;

    /**
     * Default config
     *
     * - `code` : The status code to be sent along with the response.
     * - `view` : Array of parameters to pass to the View class constructor. Only the following options are supported :
     *      - `className` : Fully qualified class name of the View class to use. Default to AppView
     *      - `templatePath` : Path to the template you wish to display (relative to your ``src/Template`` directory).
     *         You can use plugin dot notation.
     *      - `template` : Template name to use. Default to "template".
     *      - `plugin` : Theme where to find the layout and template
     *      - `theme` : Same thing than plugin
     *      - `layout` : Layout name to use. Default to "default"
     *      - `layoutPath` : Path to the layout you wish to display (relative to your ``src/Template/Layout``directory).
     *         You can use plugin dot notation. Default to "Layout"
     *      All other options are not supported (they might work though)
     * - `headers` : Additional headers to be set with the response
     *
     * @var array
     */
    protected $_defaultConfig = [
        'code' => 503,
        'view' => [
            'className' => 'App\View\AppView',
            'templatePath' => null,
            'template' => 'maintenance',
            'plugin' => null,
            'theme' => null,
            'layout' => null,
            'layoutPath' => null,
        ],

        'headers' => [],
    ];

    /**
     * {@inheritDoc}
     *
     * Will render the view and use the content as the body of the response.
     * It will also set the specified HTTP code and optional additional headers.
     */
    public function process(ServerRequest $request) : ResponseInterface
    {
        $this->request = $request;

        // Set default view class
        if (empty($this->_config['view']['className'])) {
            $this->_config['view']['className'] = 'App\View\AppView';
        }

        // Set view and view builder options
        $viewBuilder = $this->viewBuilder();
        $viewOptions = $this->_config['view'] ?: [];
        $viewBuilderOptions = [];

        foreach ($viewOptions as $option => $value) {
            $method = 'set' . ucfirst($option);
            if (method_exists($viewBuilder, $method)) {
                $viewBuilder->{$method}($value);
            } else {
                $viewBuilderOptions[$option] = $value;
            }
        }
        if (!empty($viewBuilderOptions)) {
            $viewBuilder->setOptions($viewBuilderOptions);
        }

        // Build view
        $view = $viewBuilder->build($request);

        $stream = new Stream(fopen('php://memory', 'r+'));
        $stream->write($view->render());

        return $this->addHeaders(
            $view->getResponse()->withBody($stream)->withStatus($this->_config['code'])
        );
    }
}
