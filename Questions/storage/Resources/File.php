<?php

namespace LibreSignal\Storage\Resources;

class File
{
    protected ?User $user = null;

    public function __construct(
        protected string $name,
        protected int $size,
    ) {}

    public function getSize(): int
    {
        return $this->size;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
