<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * @ORM\Table(name="fos_user")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */

class User extends BaseUser
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    public function __construct()
    {
        parent::__construct();
    }
  
}
