<?php

/*
 * This file is part of the Pommx package.
 *
 * (c) Safouan MATMATI <safouan.matmati@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pommx\Console\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use PommProject\Foundation\Pomm;
use PommProject\Foundation\Session\Session;

use Pommx\Console\Command\Configuration;

interface PommxAwareCommandInterface
{
    /**
     * [__construct description]
     * @param Pomm $pomm [description]
     */
    public function __construct(Pomm $pomm);

    /**
     * Define Pomm command console that this class is supposed to replace.
     * @param string $class [description]
     */
    public function setReplacedCommandClass(string $class): PommxAwareCommandInterface;

    /**
     * Return Pomm command console class name supposed to be replaced by this class;
     * @return null|string
     */
    public function getReplacedCommandClass(): ?string;

    /**
     * getSession
     *
     * Return a session.
     *
     * @access protected
     * @return Session
     */
    public function getSession();

    /**
     * Returns useless options list.
     * This options are not used anymore, but still required by parents class processes.
     *
     * @return array
     */
    public function getOptionsToDisable(): array;

    /**
     * Add required (but unused anymore) options.
     */
    public function restoreDisabledOptions(): PommxAwareCommandInterface;

    /**
     * [preExecute description]
     * @return PommxAwareCommandInterface [description]
     */
    public function preExecute(): PommxAwareCommandInterface;

    /**
     * Removes unused options.
     * Change 'schema' argument as required.
     */
    public function adapte(): PommxAwareCommandInterface;

    /**
     * Returns configuration depending on $config_name & $type.
     *
     * @param  string $config_name [description]
     * @param  string $type        [description]
     * @return Configuration              [description]
     */
    public function getConfiguration(string $config_name, string $type): Configuration;

    /**
     * Set default configuration.
     *
     * @return PommxAwareCommandInterface                 [description]
     */
    public function setConfigurations(array $configurations): PommxAwareCommandInterface;
}
