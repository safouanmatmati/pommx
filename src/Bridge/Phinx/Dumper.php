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

namespace Pommx\Bridge\Phinx;

class Dumper
{
    /**
     * [private description]
     *
     * @var string
     */
    private $pre_dump = '';

    /**
     * [private description]
     *
     * @var string
     */
    private $post_dump = '';

    /**
     * [private description]
     *
     * @var array
     */
    private $queue = [];

    /**
     * Add a message before the dump.
     *
     * @param string $pre_dump [description]
     */
    public function setPreDump(string $pre_dump): void
    {
        $this->pre_dump = $pre_dump;
    }

    /**
     * Add a message after the dump.
     *
     * @param string $post_dump [description]
     */
    public function setPostDump(string $post_dump): void
    {
        $this->post_dump = $post_dump;
    }

    /**
     * Dump all messages in queue.
     *
     * @return self [description]
     */
    public function dump(): self
    {
        if (false == empty($this->queue)) {
            if (false == empty($this->pre_dump)) {
                $this->display($this->pre_dump);
            }

            foreach ($this->queue as $message) {
                $this->display($message);
            }

            if (false == empty($this->post_dump)) {
                $this->display($this->post_dump);
            }
        }

        $this->clear();

        return $this;
    }

    /**
     * Filter and add message to queue.
     *
     * @param string|array      $messages [description]
     * @param string|array|null $filters  [description]
     */
    public function addToQueue($messages, $filters = null): int
    {
        if (false == is_array($messages)) {
            $messages = [$messages];
        }

        $list = [];
        $getMessages = function ($messages) use (&$getMessages, &$list, $filters) {
            return array_map(
                function ($message) use (&$getMessages, &$list, $filters) {
                    if (true == is_array($message)) {
                        $getMessages($message, $filters);
                        return;
                    }

                    if (true == is_array($filters)) {
                        foreach ($filters as $filter) {
                            if (false !== strpos($message, $filter)) {
                                $list[] = $message;
                                return;
                            }
                        }
                    }

                    if (false == is_null($filters)) {
                        if (false === strpos($message, $filters)) {
                            return;
                        }
                    }

                    $list[] = $message;
                },
                $messages
            );
        };
        $getMessages($messages);

        $this->queue = array_merge($this->queue, $list);

        return count($list);
    }

    /**
     * Clear queue.
     *
     * @return [type] [description]
     */
    public function clear()
    {
        $this->queue = [];
    }

    /**
     * Display message.
     *
     * @param  string $message [description]
     * @return self          [description]
     */
    private function display(string $message): self
    {
        dump($message);
        return $this;
    }
}
