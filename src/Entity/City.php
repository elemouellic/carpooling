<?php

namespace App\Entity;

use App\Repository\CityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CityRepository::class)]
class City
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 5)]
    private ?string $zipCode = null;

    #[ORM\OneToMany(targetEntity: Trip::class, mappedBy: 'startingTrip')]
    private Collection $trips;

    #[ORM\OneToMany(targetEntity: Trip::class, mappedBy: 'arrivalTrip')]
    private Collection $tripsArrival;

    #[ORM\OneToMany(targetEntity: Student::class, mappedBy: 'live')]
    private Collection $students;

    public function __construct()
    {
        $this->trips = new ArrayCollection();
        $this->tripsArrival = new ArrayCollection();
        $this->students = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * @return Collection<int, Trip>
     */
    public function getTrips(): Collection
    {
        return $this->trips;
    }

    public function addTrip(Trip $trip): static
    {
        if (!$this->trips->contains($trip)) {
            $this->trips->add($trip);
            $trip->setStartingTrip($this);
        }

        return $this;
    }

    public function removeTrip(Trip $trip): static
    {
        if ($this->trips->removeElement($trip)) {
            // set the owning side to null (unless already changed)
            if ($trip->getStartingTrip() === $this) {
                $trip->setStartingTrip(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Trip>
     */
    public function getTripsArrival(): Collection
    {
        return $this->tripsArrival;
    }

    public function addTripsArrival(Trip $tripsArrival): static
    {
        if (!$this->tripsArrival->contains($tripsArrival)) {
            $this->tripsArrival->add($tripsArrival);
            $tripsArrival->setArrivalTrip($this);
        }

        return $this;
    }

    public function removeTripsArrival(Trip $tripsArrival): static
    {
        if ($this->tripsArrival->removeElement($tripsArrival)) {
            // set the owning side to null (unless already changed)
            if ($tripsArrival->getArrivalTrip() === $this) {
                $tripsArrival->setArrivalTrip(null);
            }
        }

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
            $student->setLive($this);
        }

        return $this;
    }

    public function removeStudent(Student $student): static
    {
        if ($this->students->removeElement($student)) {
            // set the owning side to null (unless already changed)
            if ($student->getLive() === $this) {
                $student->setLive(null);
            }
        }

        return $this;
    }
}
