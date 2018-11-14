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


namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use PommProject\Foundation\Pomm;

use PommX\EntityManager\EntityManager;

use App\Db\Schema\Application\Entity\Country;
use Symfony\Component\Serializer\SerializerInterface;

class DefaultController extends AbstractController
{
    /**
     * [protected description]
     *
     * @var Pomm
     */
    protected $pomm;

    /**
     * [protected description]
     *
     * @var EntityManager
     */
    protected $entity_manager;

    public function __construct(Pomm $pomm)
    {
         $this->pomm = $pomm;

         $this->entity_manager = $this->pomm->getDefaultSession()->getEntityManager();
    }
}
