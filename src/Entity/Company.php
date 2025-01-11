<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'company')]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 10)]
    private string $nip;

    #[ORM\Column(type: 'string', length: 100)]
    private string $address;

    #[ORM\Column(type: 'string', length: 50)]
    private string $city;

    #[ORM\Column(type: 'string', length: 10)]
    private string $postalCode;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        if ($name === null || trim($name) === '')
            throw new \InvalidArgumentException('Name cannot be null or empty.');

        $this->name = $name;
        return $this;
    }

    public function getNip(): string
    {
        return $this->nip;
    }

    public function setNip(?string $nip): self
    {
        if ($nip === null || trim($nip) === '')
            throw new \InvalidArgumentException('Nip cannot be null or empty.');

        $this->nip = $nip;
        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        if ($address === null || trim($address) === '')
            throw new \InvalidArgumentException('Address cannot be null or empty.');

        $this->address = $address;
        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        if ($city === null || trim($city) === '')
            throw new \InvalidArgumentException('City cannot be null or empty.');

        $this->city = $city;
        return $this;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): self
    {
        if ($postalCode === null || trim($postalCode) === '')
            throw new \InvalidArgumentException('PostalCode cannot be null or empty.');

        $this->postalCode = $postalCode;
        return $this;
    }
}
