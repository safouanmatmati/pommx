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

namespace PommX\Repository\Layer;

use PommProject\Foundation\Session\Session;
use PommProject\ModelManager\Model\FlexibleEntityInterface;

use PommX\Repository\AbstractRepository;
use PommX\Repository\Layer\GlobalTransaction;

use PommX\Entity\AbstractEntity;

use PommX\Tools\Exception\ExceptionManagerAwareTrait;
use PommX\Tools\Exception\ExceptionManagerAwareInterface;

abstract class AbstractLayer extends GlobalTransaction implements ExceptionManagerAwareInterface
{
    use ExceptionManagerAwareTrait;

    private $repository_class;

    private $initialized;

    public function __construct(string $repository_class)
    {
        $this->repository_class = $repository_class;
    }

    /**
     * getClientType
     *
     * @see ClientInterface
     */
    public function getClientType()
    {
        return 'repository_layer';
    }

    public function getRepository(): AbstractRepository
    {
        return $this->getSession()->getRepository($this->repository_class);
    }

    /**
     * getClientIdentifier
     *
     * @see ClientInterface
     */
    public function getClientIdentifier()
    {
        return $this->repository_class;
    }

    private function isInitialized(): bool
    {
        return (bool) $this->initialized;
    }

    /**
     *
     * @see ClientInterface
     */
    public function initialize(Session $session)
    {
        if (true == $this->isInitialized()) {
            return ;
        }
        $this->initialized = true;

        parent::initialize($session);

        if (false == $session->hasClient($this->getClientType(), $this->getClientIdentifier())) {
            $session->registerClient($this);
        }

        try {
            $this->getRepository();
        } catch (\Exception $e) {
            $message = sprintf(
                'Failed to initialize layer.'.PHP_EOL
                .'Associated repository "%s" failed to be retrieved.',
                $this->repository_class
            );

            $this->getExceptionManager()->throw(self::class, __LINE__, $message, null, $e);
        }
    }

    public function flush($entities): self
    {
        $entities = is_array($entities) ? $entities : (empty($entities) ? null : [$entities]);

        if (true == empty($entities)) {
            return $this;
        }

        $insert = $update = $delete = [];

        foreach ($entities as $key => $entity) {
            switch (true) {
            case ($entity->status() === $entity::STATUS_MODIFIED || $entity->status() === $entity::STATUS_NONE):
                $insert[$key] = $entity;
                break;
            case  ($entity->isStatus($entity::STATUS_EXIST, $entity::STATUS_MODIFIED)):
                $update[$key] = $entity;
                break;
            case  ($entity->isStatus($entity::STATUS_EXIST, $entity::STATUS_TO_DELETE)):
                $delete[$key] = $entity;
                break;
            }
        }

        $transaction_identifier = $this->initDefaultTransaction();

        try {
            // Delete statements
            if (false == empty($delete)) {
                $this->getRepository()->delete($delete);
            }

            // Insert statements
            if (false == empty($insert)) {
                $this->getRepository()->insert($insert);
            }

            // Update statements
            if (false == empty($update)) {
                $this->getRepository()->update($update);
            }

            $this->commitDefaultTransaction($transaction_identifier);
        } catch (\Exception $e) {
            $this->rollbackTransaction();

            throw $e;
        }

        return $this;
    }
}
