<?php

namespace App\Entity;

class Devis
{
    private ?string $client;
    private ?\DateTimeInterface $dateJour;
    private ?string $description;
    private ?string $quantite;
    private ?string $unite;
    private ?string $prixUnitHT;
    private ?string $totalHT;
    private ?string $tva;
    private ?string $TTC;

    // Getters and Setters

    public function getClient(): ?string
    {
        return $this->client;
    }

    public function setClient(?string $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getDateJour(): ?\DateTimeInterface
    {
        return $this->dateJour;
    }

    public function setDateJour(?\DateTimeInterface $dateJour): self
    {
        $this->dateJour = $dateJour;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getQuantite(): ?string
    {
        return $this->quantite;
    }

    public function setQuantite(?string $quantite): self
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(?string $unite): self
    {
        $this->unite = $unite;

        return $this;
    }

    public function getPrixUnitHT(): ?string
    {
        return $this->prixUnitHT;
    }

    public function setPrixUnitHT(?string $prixUnitHT): self
    {
        $this->prixUnitHT = $prixUnitHT;

        return $this;
    }

    public function getTotalHT(): ?string
    {
        return $this->totalHT;
    }

    public function setTotalHT(?string $totalHT): self
    {
        $this->totalHT = $totalHT;

        return $this;
    }

    public function getTva(): ?string
    {
        return $this->tva;
    }

    public function setTva(?string $tva): self
    {
        $this->tva = $tva;

        return $this;
    }

    public function getTTC(): ?string
    {
        return $this->TTC;
    }

    public function setTTC(?string $TTC): self
    {
        $this->TTC = $TTC;

        return $this;
    }
}
