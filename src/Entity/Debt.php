<?php

namespace App\Entity;

use App\Repository\DebtRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=DebtRepository::class)
 * @UniqueEntity(fields={"debtor"})
 * @ORM\Table(name="debts")
 */
class Debt
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Company::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="debtor_id",nullable=false)
     */
    private $debtor;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $total;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDebtor(): ?Company
    {
        return $this->debtor;
    }

    public function setDebtor(Company $debtor): self
    {
        $this->debtor = $debtor;

        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getDebtor()->getName() . " should pay " . $this->total;
    }
}
