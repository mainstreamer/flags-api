<?php

namespace App\Flags\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Flags\Repository\CapitalRepository")]
class Capital
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $code;

    #[ORM\Column(type: "string", length: 255)]
    private string $name;

    #[ORM\Column(type: "string", length: 255)]
    private string $region;

    #[ORM\Column(type: "string", length: 255)]
    private string $country;

    public function __construct(string $name, string $country, string $code, string $region)
    {
        $this->code = $code;
        $this->name = $name;
        $this->region = $region;
        $this->country = $country;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function getCountry(): string
    {
        return $this->country;
    }
}
