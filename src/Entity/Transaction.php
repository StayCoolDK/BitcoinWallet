<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


//This is actually not used. It was primarily thought of a backup solution to linking sent transactions to individual accounts.
//However the username can be stored with the transaction in the underlying wallet, using the comment field of SendToAddress.

/**
 * @ORM\Entity(repositoryClass="App\Repository\TransactionRepository")
 */
class Transaction
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $TXID;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="transactions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=8)
     */
    private $amount; 

    public function getId()
    {
        return $this->id;
    }

    public function getTXID(): ?string
    {
        return $this->TXID;
    }

    public function setTXID(string $TXID): self
    {
        $this->TXID = $TXID;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getAmount(): ?decimal
    {
        return $this->amount;
    }

    public function setAmount(decimal $amount): self
    {
        $this->amount = $amount;

        return $this;
    }
}
