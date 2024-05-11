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
namespace Wrench\Command;

use Bake\Command\SimpleBakeCommand;

/**
 * Bake task responsible of generating Maintenance Mode skeleton
 */
class MaintenanceModeCommand extends SimpleBakeCommand
{

    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->pathFragment = 'Maintenance/Mode/';
    }

    /**
     * {@inheritDoc}
     */
    public function name(): string
    {
        return 'maintenance_mode';
    }

    /**
     * {@inheritDoc}
     */
    public function fileName($name): string
    {
        return $name . '.php';
    }

    /**
     * {@inheritDoc}
     */
    public function template(): string
    {
        return 'Wrench.mode';
    }
}
