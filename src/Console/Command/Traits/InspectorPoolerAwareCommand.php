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

namespace Pommx\Console\Command\Traits;

use PommProject\Cli\Command\PommAwareCommand;
use PommProject\Foundation\Session\Session;

use Pommx\Inspector\InspectorPooler;

trait InspectorPoolerAwareCommand
{
    private $session;

    /**
     * getSession
     *
     * Return a session.
     *
     * @access protected
     * @return Session
     */
    protected function getSession()
    {
        if ($this->session === null) {
            $this->session = $this
                ->getPomm()
                ->getSession($this->session)
                ->registerClientPooler(new InspectorPooler())
                ;
        }

        return $this->session;
    }


    /**
     * setSession
     *
     * When testing, it is useful to provide directly the session to be used.
     *
     * @access public
     * @param  Session          $session
     * @return PommAwareCommand
     */
    public function setSession(Session $session)
    {
        $this->session = $session;

        return $this;
    }
}
