<?php

/*
 * This file is part of the Weasyo package.
 *
 * (c) Safouan MATMATI <safouan.matmati@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AbstractTestCase extends KernelTestCase
{
    protected $custom_container;
    protected $pomm;

    /**
     * {@inheritDoc}
     */
    public static function setUpBeforeClass()
    {
        echo PHP_EOL.static::class.' ';
        static::bootKernel();
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->pomm = static::$container->get('pomm');
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        // Replace parent one to avoid kernel shutdown
    }
}
