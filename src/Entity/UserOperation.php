<?php

namespace App\Entity;

class UserOperation
{
    protected \DateTimeInterface $date;

    protected int $uid;

    protected string $clientType;

    protected string $opType;

    protected float $amount;
    
    protected string $currency;

    protected int $precision;

    public function getDate(): \DateTime
    {
        return $this->date;
    }
   
    public function getUid(): int
    {
        return $this->uid;
    }

    public function getClientType(): string
    {
        return $this->clientType;
    }

    public function getOpType(): string
    {
        return $this->opType;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }

    public function setUid(int $uid): self 
    {
        $this->uid = $uid;

        return $this;
    }

    public function setDate(string $date): self 
    {
        $this->date = \DateTime::createFromFormat('Y-m-d', $date);

        return $this;
    }

    public function setOpType(string $opType): self
    {
        $this->opType = $opType;

        return $this;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function setClientType(string $clientType): self
    {
        $this->clientType = $clientType;

        return $this;
    }

    public function setPrecision(int $precision): self
    {
        $this->precision = $precision;

        return $this;
    }
}