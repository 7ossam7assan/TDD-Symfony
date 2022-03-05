<?php

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InvoiceRepository::class)
 * @ORM\Table(name="invoices")
 */
class Invoice implements \JsonSerializable
{

    public const PENDING = "pending";
    public const PENDING_REVIEW = "pending_review";
    public const REJECTED = "rejected";
    public const PAID = "paid";

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $document;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $debtor;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $creditor;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $status;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocument(): ?string
    {
        // todo get this document from server url later(out of scope)
        return $this->document;
    }

    public function setDocument(string $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function getDebtor(): ?Company
    {
        return $this->debtor;
    }

    public function setDebtor(?Company $debtor): self
    {
        $this->debtor = $debtor;

        return $this;
    }

    public function getCreditor(): ?Company
    {
        return $this->creditor;
    }

    public function setCreditor(?Company $creditor): self
    {
        $this->creditor = $creditor;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status = self::PENDING): self
    {
        $this->status = $status;

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'debtor' => $this->debtor,
            'creditor' => $this->creditor,
            'price' => $this->price,
            'document' => $this->getDocument(),
            'status' => $this->getStatus(),
        ];
    }

    public function __toString(): string
    {
        return $this->getDebtor() . " has invoice " . $this->status . " with " . $this->price;
    }
}
