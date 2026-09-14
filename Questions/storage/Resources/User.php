<?php

namespace LibreSignal\Storage\Resources;

class User
{
    /**
     * @var File[]
     */
    protected array $files = [];

    protected int $totalUsed = 0;

    public function __construct(
        protected string $id,
        protected int $capacity,
        protected bool $isAdmin = false,
    ) {}

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function addFile(File $file): void
    {
        $this->totalUsed += $file->getSize();

        $file->setUser($this);

        $this->files[$file->getName()] = $file;
    }

    public function hasFile(string $name): bool
    {
        return isset($this->files[$name]);
    }

    public function removeFile(string $name): bool
    {
        if (isset($this->files[$name])) {
            $this->totalUsed -= $this->files[$name]->getSize();
            unset($this->files[$name]);
            return true;
        }

        return false;
    }

    public function getTotalUsed(): int
    {
        return $this->totalUsed;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function increaseCapacityBy(int $amount): void
    {
        $this->capacity += $amount;
    }

    public function hasCapacity(int $size): bool
    {
        return $size <= $this->getRemainingCapacity();
    }

    public function getRemainingCapacity(): int
    {
        return $this->getCapacity() - $this->getTotalUsed();
    }
}
