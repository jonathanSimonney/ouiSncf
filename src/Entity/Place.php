<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PlaceRepository")
 */
class Place
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Horaire", inversedBy="places")
     * @ORM\JoinColumn(nullable=false)
     */
    private $horaire_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $class;

    /**
     * @ORM\Column(type="boolean")
     */
    private $reserved;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHoraireId(): ?Horaire
    {
        return $this->horaire_id;
    }

    public function setHoraireId(?Horaire $horaire_id): self
    {
        $this->horaire_id = $horaire_id;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function getReserved(): ?bool
    {
        return $this->reserved;
    }

    public function setReserved(bool $reserved): self
    {
        $this->reserved = $reserved;

        return $this;
    }
}
