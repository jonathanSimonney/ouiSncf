<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DestinationRepository")
 */
class Destination
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="string")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Horaire", mappedBy="from", orphanRemoval=true)
     */
    private $horaires_departure;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Horaire", mappedBy="to", orphanRemoval=true)
     */
    private $horaires_arrival;

    public function __construct()
    {
        $this->horaires = new ArrayCollection();
        $this->horaires_arrival = new ArrayCollection();
    }

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

    /**
     * @return Collection|Horaire[]
     */
    public function getHoraires(): Collection
    {
        return $this->horaires;
    }

    public function addHoraireDeparture(Horaire $horaire): self
    {
        if (!$this->horaires->contains($horaire)) {
            $this->horaires[] = $horaire;
            $horaire->setFromId($this);
        }

        return $this;
    }

    public function removeHoraireDeparture(Horaire $horaire): self
    {
        if ($this->horaires->contains($horaire)) {
            $this->horaires->removeElement($horaire);
            // set the owning side to null (unless already changed)
            if ($horaire->getFrom() === $this) {
                $horaire->setFrom(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Horaire[]
     */
    public function getHorairesArrival(): Collection
    {
        return $this->horaires_arrival;
    }

    public function addHorairesArrival(Horaire $horairesArrival): self
    {
        if (!$this->horaires_arrival->contains($horairesArrival)) {
            $this->horaires_arrival[] = $horairesArrival;
            $horairesArrival->setTo($this);
        }

        return $this;
    }

    public function removeHorairesArrival(Horaire $horairesArrival): self
    {
        if ($this->horaires_arrival->contains($horairesArrival)) {
            $this->horaires_arrival->removeElement($horairesArrival);
            // set the owning side to null (unless already changed)
            if ($horairesArrival->getTo() === $this) {
                $horairesArrival->setTo(null);
            }
        }

        return $this;
    }
}
