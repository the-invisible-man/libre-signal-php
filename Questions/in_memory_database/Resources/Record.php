<?php

namespace LibreSignal\InMemoryDatabase\Resources;

class Record
{
    public function __construct(
        protected string $value,
        protected ?int   $timestamp = null,
        protected ?int   $ttl = null
    ){}

    public function getValue(): string
    {
        return $this->value;
    }

    public function getTimestamp():? int
    {
        return $this->timestamp;
    }

    public function getTtl():? int
    {
        return $this->ttl;
    }

    public function isExpired(int $currentTimestamp): bool
    {
        if (is_null($this->getTimestamp()) || is_null($this->getTtl())) {
            return false;
        }

        return $currentTimestamp >= ($this->getTimestamp() + $this->getTtl());
    }
}
