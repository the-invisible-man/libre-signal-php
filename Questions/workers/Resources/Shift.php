<?php

namespace LibreSignal\Workers\Resources;

class Shift
{
    public function __construct(
        protected int $startAt,
        protected string $position,
        protected int $compensation,
        protected ?int $endAt = null,
    ) {
    }

    public function setEndAt(int $timestamp): self
    {
        $this->endAt = $timestamp;
        return $this;
    }

    public function getStartAt(): int
    {
        return $this->startAt;
    }

    public function getEndAt(): ?int
    {
        return $this->endAt;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function getCompensation(): int
    {
        return $this->compensation;
    }

    public function isComplete(): bool
    {
        return ! is_null($this->endAt);
    }
}
