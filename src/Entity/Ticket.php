<?php

namespace App\Entity;

class Ticket
{
    private ?string $statut = null;
    private ?string $CGV_DECH = null;
    private ?string $client = null;
    private ?\DateTimeInterface $date_jour = null;
    private ?string $TECH = null;
    private ?string $numero_client = null;
    private ?string $details = null;
    private ?string $materiel = null;
    private ?string $prestations = null;
    private ?string $accepte = null;
    private ?string $resultat = null;
    private ?string $tarif = null;
    private ?string $prevenu = null;

    // Getters and Setters

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getCGVDECH(): ?string
    {
        return $this->CGV_DECH;
    }

    public function setCGVDECH(string $CGV_DECH): self
    {
        $this->CGV_DECH = $CGV_DECH;
        return $this;
    }

    public function getClient(): ?string
    {
        return $this->client;
    }

    public function setClient(string $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getDateJour(): ?\DateTimeInterface
    {
        return $this->date_jour;
    }

    public function setDateJour(\DateTimeInterface $date_jour): self
    {
        $this->date_jour = $date_jour;
        return $this;
    }

    public function getTECH(): ?string
    {
        return $this->TECH;
    }

    public function setTECH(string $TECH): self
    {
        $this->TECH = $TECH;
        return $this;
    }

    public function getNumeroClient(): ?string
    {
        return $this->numero_client;
    }

    public function setNumeroClient(string $numero_client): self
    {
        $this->numero_client = $numero_client;
        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(string $details): self
    {
        $this->details = $details;
        return $this;
    }

    public function getMateriel(): ?string
    {
        return $this->materiel;
    }

    public function setMateriel(string $materiel): self
    {
        $this->materiel = $materiel;
        return $this;
    }

    public function getPrestations(): ?string
    {
        return $this->prestations;
    }

    public function setPrestations(string $prestations): self
    {
        $this->prestations = $prestations;
        return $this;
    }

    public function getAccepte(): ?string
    {
        return $this->accepte;
    }

    public function setAccepte(string $accepte): self
    {
        $this->accepte = $accepte;
        return $this;
    }

    public function getResultat(): ?string
    {
        return $this->resultat;
    }

    public function setResultat(string $resultat): self
    {
        $this->resultat = $resultat;
        return $this;
    }

    public function getTarif(): ?string
    {
        return $this->tarif;
    }

    public function setTarif(string $tarif): self
    {
        $this->tarif = $tarif;
        return $this;
    }

    public function getPrevenu(): ?string
    {
        return $this->prevenu;
    }

    public function setPrevenu(string $prevenu): self
    {
        $this->prevenu = $prevenu;
        return $this;
    }
}
