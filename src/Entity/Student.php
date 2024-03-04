<?php

namespace App\Entity;

use App\Repository\StudentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentRepository::class)]
class Student
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 10, unique: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?User $register = null;

    #[ORM\ManyToMany(targetEntity: Trip::class, inversedBy: 'students')]
    private Collection $participate;

    #[ORM\OneToMany(targetEntity: Trip::class, mappedBy: 'student')]
    private Collection $drive;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?City $live = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Car $possess = null;

    public function __construct()
    {
        $this->participate = new ArrayCollection();
        $this->drive = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getRegister(): ?User
    {
        return $this->register;
    }

    public function setRegister(?User $register): static
    {
        $this->register = $register;

        return $this;
    }

    /**
     * @return Collection<int, Trip>
     */
    public function getParticipate(): Collection
    {
        return $this->participate;
    }

    public function addParticipate(Trip $participate): static
    {
        if (!$this->participate->contains($participate)) {
            $this->participate->add($participate);
        }

        return $this;
    }

    public function removeParticipate(Trip $participate): static
    {
        $this->participate->removeElement($participate);

        return $this;
    }

    /**
     * @return Collection<int, Trip>
     */
    public function getDrive(): Collection
    {
        return $this->drive;
    }

    public function addDrive(Trip $drive): static
    {
        if (!$this->drive->contains($drive)) {
            $this->drive->add($drive);
            $drive->setStudent($this);
        }

        return $this;
    }

    public function removeDrive(Trip $drive): static
    {
        if ($this->drive->removeElement($drive)) {
            // set the owning side to null (unless already changed)
            if ($drive->getStudent() === $this) {
                $drive->setStudent(null);
            }
        }

        return $this;
    }

    public function getLive(): ?City
    {
        return $this->live;
    }

    public function setLive(?City $live): static
    {
        $this->live = $live;

        return $this;
    }

    public function getPossess(): ?Car
    {
        return $this->possess;
    }

    public function setPossess(?Car $possess): static
    {
        $this->possess = $possess;

        return $this;
    }
}
