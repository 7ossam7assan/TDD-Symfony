<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=CompanyRepository::class)
 * @UniqueEntity("swift_code")
 * @ORM\Table(name="companies")
 */
class Company implements \JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(
     *      min = 3,
     *      max = 255,
     *      minMessage = "company name must be at least {{ limit }} characters long",
     *      maxMessage = "company name cannot be longer than {{ limit }} characters"
     * )
     */
    private $name;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=11)
     * @Assert\Length(
     *      min = 8,
     *      max = 11,
     *      minMessage = "swift code must be at least {{ limit }} characters long",
     *      maxMessage = "swift code cannot be longer than {{ limit }} characters"
     * )
     */
    private $swift_code;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(
     *      min = 3,
     *      max = 255,
     *      minMessage = "bank name must be at least {{ limit }} characters long",
     *      maxMessage = "bank name cannot be longer than {{ limit }} characters"
     * )
     */
    private $bank_name;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $debtor_limit;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=30)
     */
    private $debtor_currency;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSWIFTCode(): ?string
    {
        return $this->swift_code;
    }

    public function setSWIFTCode(string $swift_code): self
    {
        $this->swift_code = $swift_code;

        return $this;
    }

    public function getBankName(): ?string
    {
        return $this->bank_name;
    }

    public function setBankName(string $bank_name): self
    {
        $this->bank_name = $bank_name;

        return $this;
    }

    public function getDebtorLimit(): ?string
    {
        return $this->debtor_limit;
    }

    public function setDebtorLimit(string $debtor_limit): self
    {
        $this->debtor_limit = $debtor_limit;

        return $this;
    }

    public function getDebtorCurrency(): ?string
    {
        return $this->debtor_currency;
    }

    public function setDebtorCurrency(string $debtor_currency): self
    {
        $this->debtor_currency = $debtor_currency;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'swift_code' => $this->swift_code,
            'bank_name' => $this->bank_name,
            'debtor_limit' => $this->debtor_limit,
            'debtor_currency' => $this->debtor_currency
        ];
    }
}
