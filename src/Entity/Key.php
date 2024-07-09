<?php

namespace App\Entity;

use App\Repository\KeyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KeyRepository::class)]
class Key
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $uid = null;

    /**
     * @var Collection<int, Position>
     */
    #[ORM\OneToMany(targetEntity: Position::class, mappedBy: 'key', orphanRemoval: true, cascade:['persist'])]
    private Collection $positions;

    // Key.php
    #[ORM\OneToOne(mappedBy: 'key', cascade: ['persist', 'remove'])]
    private ?Puzzle $puzzle = null;

    public function __construct()
    {
        $this->uid = uniqid();
        $this->positions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(string $uid): static
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * @return Collection<int, Position>
     */
    public function getPositions(): Collection
    {
        return $this->positions;
    }

    public function addPosition(Position $position): static
    {
        if (!$this->positions->contains($position)) {
            $this->positions->add($position);
            $position->setKey($this);
        }

        return $this;
    }

    public function removePosition(Position $position): static
    {
        if ($this->positions->removeElement($position)) {
            // set the owning side to null (unless already changed)
            if ($position->getKey() === $this) {
                $position->setKey(null);
            }
        }

        return $this;
    }

    public function getPuzzle(): ?Puzzle
    {
        return $this->puzzle;
    }

    public function setPuzzle(Puzzle $puzzle): static
    {
        // set the owning side of the relation if necessary
        if ($puzzle->getKey() !== $this) {
            $puzzle->setKey($this);
        }

        $this->puzzle = $puzzle;

        return $this;
    }
}
