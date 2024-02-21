<?php

namespace App\Entity;

use App\Repository\CarRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarRepository::class)]
class Car
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $carModel = null;

    #[ORM\Column(length: 10)]
    private ?string $matriculation = null;

    #[ORM\ManyToOne(inversedBy: 'cars')]
    private ?Brand $identify = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $numberPlaces = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCarModel(): ?string
    {
        return $this->carModel;
    }

    public function setCarModel(string $carModel): static
    {
        $this->carModel = $carModel;

        return $this;
    }

    public function getMatriculation(): ?string
    {
        return $this->matriculation;
    }

    public function setMatriculation(string $matriculation): static
    {
        $this->matriculation = $matriculation;

        return $this;
    }

    public function getIdentify(): ?Brand
    {
        return $this->identify;
    }

    public function setIdentify(?Brand $identify): static
    {
        $this->identify = $identify;

        return $this;
    }

    public function getNumberPlaces(): ?int
    {
        return $this->numberPlaces;
    }

    public function setNumberPlaces(int $numberPlaces): static
    {
        $this->numberPlaces = $numberPlaces;

        return $this;
    }
}
