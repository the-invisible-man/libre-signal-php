<?php

namespace LibreSignal\BankSystem\Resources;

class Account
{
    protected string $id;

    protected int $created_at;

    protected int $balance;

    /**
     * @var Transaction[]
     */
    protected array $transactions = [];

    protected int $total_spent = 0;

    protected int $total_deposited = 0;

    protected ?int $deleted_at = null;

    public function __construct(string $id, int $createdAt)
    {
        $this->id = $id;
        $this->created_at = $createdAt;
        $this->balance = 0;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function addTransaction(Transaction $transaction): void
    {
        $this->transactions[] = $transaction;

        // Piggyback off this so we can recalculate account totals
        $this->updateTotals($transaction);
    }


    protected function updateTotals(Transaction $transaction): void
    {
        $this->balance += $transaction->getAmount();

        if ($transaction->getAmount() > 0 && !$transaction->isCashback()) {
            $this->total_deposited += $transaction->getAmount();
        } elseif ($transaction->getAmount() < 0) {
            $this->total_spent += -$transaction->getAmount();
        }
    }

    public function getTotalDeposited(): int
    {
        return $this->total_deposited;
    }

    public function getTotalSpent(): int
    {
        return $this->total_spent;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function getBalanceAt(int $timestamp): int
    {
        $result = 0;

        foreach ($this->transactions as $transaction) {
            if ($transaction->getTimestamp() <= $timestamp) {
                $result += $transaction->getAmount();
            }
        }

        return $result;
    }

    public function markDeleted(int $deletedAt): void
    {
        $this->deleted_at = $deletedAt;
    }

    public function getCreatedAt(): int
    {
        return $this->created_at;
    }

    public function getTransactions(): array
    {
        return $this->transactions;
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }
}
