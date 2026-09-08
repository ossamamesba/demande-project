<?php

namespace App\Entity;

use App\Repository\ProjetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjetRepository::class)]
class Projet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $numeroProjet = null;

    #[ORM\Column(length: 100)]
    private ?string $creePar = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lienProjet = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomProjet = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $sao = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $codeSap = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateEnCours = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(length: 30)]
    private ?string $statut = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroProjet(): ?string
    {
        return $this->numeroProjet;
    }

    public function setNumeroProjet(string $numeroProjet): static
    {
        $this->numeroProjet = $numeroProjet;

        return $this;
    }

    public function getCreePar(): ?string
    {
        return $this->creePar;
    }

    public function setCreePar(string $creePar): static
    {
        $this->creePar = $creePar;

        return $this;
    }

    public function getLienProjet(): ?string
    {
        return $this->lienProjet;
    }

    public function setLienProjet(?string $lienProjet): static
    {
        $this->lienProjet = $lienProjet;

        return $this;
    }

    public function getNomProjet(): ?string
    {
        return $this->nomProjet;
    }

    public function setNomProjet(?string $nomProjet): static
    {
        $this->nomProjet = $nomProjet;

        return $this;
    }

    public function getSao(): ?string
    {
        return $this->sao;
    }

    public function setSao(?string $sao): static
    {
        $this->sao = $sao;

        return $this;
    }

    public function getCodeSap(): ?string
    {
        return $this->codeSap;
    }

    public function setCodeSap(?string $codeSap): static
    {
        $this->codeSap = $codeSap;

        return $this;
    }

     public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDateEnCours(): ?\DateTimeImmutable
    {
        return $this->dateEnCours;
    }

    public function setDateEnCours(?\DateTimeImmutable $dateEnCours): static
    {
        $this->dateEnCours = $dateEnCours;

        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}