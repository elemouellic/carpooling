<?php

namespace App\Entity;

use App\Repository\TripRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TripRepository::class)]
class Trip
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $kmDistance = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $travelDate = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $placesOffered = null;

    #[ORM\ManyToMany(targetEntity: Student::class, mappedBy: 'participate')]
    private Collection $students;

    #[ORM\ManyToOne(inversedBy: 'drive')]
    private ?Student $student = null;

    #[ORM\ManyToOne(inversedBy: 'trips')]
    private ?City $startingTrip = null;

    #[ORM\ManyToOne(inversedBy: 'tripsArrival')]
    private ?City $arrivalTrip = null;

    public function __construct()
    {
        $this->students = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKmDistance(): ?float
    {
        return $this->kmDistance;
    }

    public function setKmDistance(float $kmDistance): static
    {
        $this->kmDistance = $kmDistance;

        return $this;
    }

    public function getTravelDate(): ?\DateTimeInterface
    {
        return $this->travelDate;
    }

    public function setTravelDate(\DateTimeInterface $travelDate): static
    {
        $this->travelDate = $travelDate;

        return $this;
    }

    public function getPlacesOffered(): ?int
    {
        return $this->placesOffered;
    }

    public function setPlacesOffered(int $placesOffered): static
    {
        $this->placesOffered = $placesOffered;

        return $this;
    }

    /**
     * @return Collection<int, Student>
     */
    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(Student $student): static
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
            $student->addParticipate($this);
        }

        return $this;
    }

    public function removeStudent(Student $student): static
    {
        if ($this->students->removeElement($student)) {
            $student->removeParticipate($this);
        }

        return $this;
    }

    public function getStudent(): ?Student
    {
        return $this->student;
    }

    public function setStudent(?Student $student): static
    {
        $this->student = $student;

        return $this;
    }

    public function getStartingTrip(): ?City
    {
        return $this->startingTrip;
    }

    public function setStartingTrip(?City $startingTrip): static
    {
        $this->startingTrip = $startingTrip;

        return $this;
    }

    public function getArrivalTrip(): ?City
    {
        return $this->arrivalTrip;
    }

    public function setArrivalTrip(?City $arrivalTrip): static
    {
        $this->arrivalTrip = $arrivalTrip;

        return $this;
    }
}
