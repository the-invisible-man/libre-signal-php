<?php

namespace LibreSignal\Workers;

use LibreSignal\Workers\Resources\Worker;

class Simulation
{
    protected array $workers = [];

    public function addWorker(string $workerId, string $position, int $compensation): bool
    {
        if ($this->getWorker($workerId)) {
            return false;
        }

        $worker = new Worker($workerId, $position, $compensation);

        $this->workers[$workerId] = $worker;

        return true;
    }

    public function register(string $workerId, int $timestamp): string
    {
        $worker = $this->getWorker($workerId);

        if (!$worker) {
            return 'invalid_request';
        }

        $worker->addShiftTimestamp($timestamp);

        return 'registered';
    }

    public function get(string $workerId): string
    {
        $worker = $this->getWorker($workerId);

        return (string)$worker?->getTotalTime();
    }

    public function topNWorkers(int $n, string $position): string
    {
        $result = '';
        $workers = $this->getByPosition($position);

        usort($workers, function (Worker $a, Worker $b) use($position) : int{
            return ($b->getTotalTime($position) <=> $a->getTotalTime($position))
                ?: ($a->getId() <=> $b->getId());
        });

        $workers = array_slice($workers, 0, $n);

        foreach ($workers as $worker) {
            $result .= "{$worker->getId()}({$worker->getTotalTime($position)}), ";
        }

        if (str_ends_with($result, ', ')) {
            $result = substr($result, 0, -2);
        }

        return $result;
    }

    public function promote(string $workerId, string $newPosition, int $newCompensation, int $startAt): string
    {
        $worker = $this->getWorker($workerId);

        if (!$worker) {
            return 'invalid_request';
        }

        if ($worker->hasPendingPromotion() && $worker->getShiftInProgress()) {
            return 'invalid_request';
        }

        $worker->promote($newPosition, $newCompensation, $startAt);

        return 'success';
    }

    public function calcSalary(string $workerId, int $startAt, int $endAt): int
    {
        $worker = $this->getWorker($workerId);

        if (!$worker) {
            return '';
        }

        $total = 0;

        foreach ($worker->getShifts() as $shift) {
            $overlapStart = max($shift->getStartAt(), $startAt);
            $overlapEnd = min($shift->getEndAt(), $endAt);

            if ($overlapStart < $overlapEnd) {
                $timeWorked = $overlapEnd - $overlapStart;
                $total += $timeWorked * $shift->getCompensation();
            }
        }

        return $total;
    }


    protected function getWorker(string $id):? Worker
    {
        return $this->workers[$id] ?? null;
    }

    protected function getByPosition(string $position): array
    {
        $result = [];

        foreach ($this->workers as $worker) {
            if ($worker->getPosition() === $position) {
                $result[] = $worker;
            }
        }

        return $result;
    }
}
