<?php

namespace App\Entity;

use App\Repository\PuzzleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PuzzleRepository::class)]
class Puzzle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $width = null;

    #[ORM\Column]
    private ?int $height = null;

    #[ORM\Column]
    private ?int $pieceWidth = null;

    #[ORM\Column]
    private ?int $pieceHeight = null;

    #[ORM\Column]
    private ?int $precision = null;

    #[ORM\Column]
    private ?int $piecesNumber = null;

    #[ORM\Column]
    private ?int $spaceBetweenPieces = null;

    #[ORM\Column(length: 10)]
    private ?string $puzzleBar = null;

    #[ORM\OneToOne(inversedBy: 'puzzle', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'key_id', referencedColumnName: 'id')]
    private ?Key $key = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getPieceWidth(): ?int
    {
        return $this->pieceWidth;
    }

    public function setPieceWidth(int $pieceWidth): static
    {
        $this->pieceWidth = $pieceWidth;

        return $this;
    }

    public function getPieceHeight(): ?int
    {
        return $this->pieceHeight;
    }

    public function setPieceHeight(int $pieceHeight): static
    {
        $this->pieceHeight = $pieceHeight;

        return $this;
    }

    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    public function setPrecision(int $precision): static
    {
        $this->precision = $precision;

        return $this;
    }

    public function getPiecesNumber(): ?int
    {
        return $this->piecesNumber;
    }

    public function setPiecesNumber(int $piecesNumber): static
    {
        $this->piecesNumber = $piecesNumber;

        return $this;
    }

    public function getSpaceBetweenPieces(): ?int
    {
        return $this->spaceBetweenPieces;
    }

    public function setSpaceBetweenPieces(int $spaceBetweenPieces): static
    {
        $this->spaceBetweenPieces = $spaceBetweenPieces;

        return $this;
    }

    public function getPuzzleBar(): ?string
    {
        return $this->puzzleBar;
    }

    public function setPuzzleBar(string $puzzleBar): static
    {
        $this->puzzleBar = $puzzleBar;

        return $this;
    }

    public function getKey(): ?key
    {
        return $this->key;
    }

    public function setKey(key $key): static
    {
        $this->key = $key;

        return $this;
    }
}
