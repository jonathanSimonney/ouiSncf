<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\HoraireRepository")
 */
class Horaire
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Destination", inversedBy="horaires_departure")
     * @ORM\JoinColumn(nullable=false)
     */
    private $from_id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Destination", inversedBy="horaires_arrival")
     * @ORM\JoinColumn(nullable=false)
     */
    private $to_id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $depart_at;

    /**
     * @ORM\Column(type="datetime")
     */
    private $arrive_at;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Place", mappedBy="horaire_id", orphanRemoval=true)
     */
    private $places;

    public function __construct()
    {
        $this->places = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFromId(): ?Destination
    {
        return $this->from_id;
    }

    public function setFromId(?Destination $from_id): self
    {
        $this->from_id = $from_id;

        return $this;
    }

    public function getToId(): ?Destination
    {
        return $this->to_id;
    }

    public function setToId(?Destination $to_id): self
    {
        $this->to_id = $to_id;

        return $this;
    }

    public function getDepartAt(): ?\DateTimeInterface
    {
        return $this->depart_at;
    }

    public function setDepartAt(\DateTimeInterface $depart_at): self
    {
        $this->depart_at = $depart_at;

        return $this;
    }

    public function getArriveAt(): ?\DateTimeInterface
    {
        return $this->arrive_at;
    }

    public function setArriveAt(\DateTimeInterface $arrive_at): self
    {
        $this->arrive_at = $arrive_at;

        return $this;
    }

    /**
     * @return Collection|Place[]
     */
    public function getPlaces(): Collection
    {
        return $this->places;
    }

    public function addPlace(Place $place): self
    {
        if (!$this->places->contains($place)) {
            $this->places[] = $place;
            $place->setHoraireId($this);
        }

        return $this;
    }

    public function removePlace(Place $place): self
    {
        if ($this->places->contains($place)) {
            $this->places->removeElement($place);
            // set the owning side to null (unless already changed)
            if ($place->getHoraireId() === $this) {
                $place->setHoraireId(null);
            }
        }

        return $this;
    }
}
