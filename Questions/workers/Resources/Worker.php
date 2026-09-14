<?php

namespace LibreSignal\Workers\Resources;

class Worker
{
    /**
     * @var Shift[]
     */
    protected array $shifts = [];

    protected int $totalTime = 0;

    protected ?string $futurePosition = null;

    protected ?int $futureComp = null;

    protected ?int $futurePositionStartAt = null;

    protected array $totalTimePerPosition = [];

    public function __construct(
        protected string $id,
        protected string $position,
        protected int  $compensation
    ) {}

    public function getPosition(): string
    {
        return $this->position;
    }

    public function addShiftTimestamp(int $timestamp): void
    {
        $shift = $this->getShiftInProgress();

        if ($shift) {
            $shift->setEndAt($timestamp);
            $this->updateTotalTime($shift);
        } else {
            $this->updatePosition($timestamp);

            $this->shifts[] = new Shift(
                $timestamp,
                $this->position,
                $this->compensation
            );
        }
    }


    public function promote(string $position, int $compensation, int $startAt): void
    {
        $this->futurePosition = $position;
        $this->futureComp = $compensation;
        $this->futurePositionStartAt = $startAt;
    }

    protected function updatePosition(int $timestamp): void
    {
        if ($this->futurePositionStartAt !== null && $timestamp >= $this->futurePositionStartAt) {
            $this->position = $this->futurePosition;
            $this->compensation = $this->futureComp;

            $this->futurePosition = null;
            $this->futureComp = null;
            $this->futurePositionStartAt = null;
        }
    }

    public function hasPendingPromotion(): bool
    {
        return $this->futurePosition !== null;
    }

    protected function updateTotalTime(Shift $shift): void
    {
        $position = $shift->getPosition();

        if (!array_key_exists($position, $this->totalTimePerPosition)) {
            $this->totalTimePerPosition[$position] = 0;
        }

        $this->totalTimePerPosition[$position] += $shift->getEndAt() - $shift->getStartAt();

        $this->totalTime += $shift->getEndAt() - $shift->getStartAt();
    }

    public function getTotalTime(?string $position = null): int
    {
        if ($position !== null) {
            return $this->totalTimePerPosition[$position] ?? 0;
        }

        return $this->totalTime;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getShifts(): array
    {
        return $this->shifts;
    }

    public function getShiftInProgress(): ?Shift
    {
        $total = count($this->shifts);

        if ($total === 0) {
            return null;
        }

        $lastShift = $this->shifts[$total-1];

        return $lastShift->isComplete() ? null : $lastShift;
    }

    public function getCompletedShifts(): \Generator
    {
        foreach ($this->getShifts() as $shift) {
            if ($shift->isComplete()) {
                yield $shift;
            }
        }
    }

    protected function totalForPosition(string $position): int
    {
        return $this->totalTimePerPosition[$position];
    }
}
