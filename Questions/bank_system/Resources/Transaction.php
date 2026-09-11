<?php

namespace LibreSignal\BankSystem\Resources;

class Transaction
{
    protected ?string $paymentId = null;

    protected string $accountId;

    protected int $amount;

    protected int $timestamp;

    protected string $type;

    protected ?Transaction $refTransaction = null;

    public const TYPE = [
        'DEPOSIT' => 'DEPOSIT',
        'TRANSFER' => 'TRANSFER',
        'PAYMENT' => 'PAYMENT',
        'CASHBACK' => 'CASHBACK',
    ];

    protected const PAYMENT_CLEARING_WINDOW = 86400000;

    public function __construct(string $accountId, int $amount, int $timestamp, string $type)
    {
        $this->accountId = $accountId;
        $this->amount = $amount;
        $this->timestamp = $timestamp;
        $this->type = $type;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getRefTransaction(): ?Transaction
    {
        return $this->refTransaction;
    }

    public function hasIssuedCashback(): bool
    {
        return $this->getRefTransaction() !== null;
    }

    public function setRefTransaction(Transaction $transaction): self
    {
        $this->refTransaction = $transaction;
        return $this;
    }

    public function isClearedPayment(int $currentTimestamp): bool
    {
        $timeElapsed = $currentTimestamp - $this->timestamp;

        return $this->isPayment() && ($timeElapsed >= self::PAYMENT_CLEARING_WINDOW);
    }

    public function isPayment(): bool
    {
        return $this->type === self::TYPE['PAYMENT'];
    }

    public function isCashback(): bool
    {
        return $this->type === self::TYPE['CASHBACK'];
    }

    public function setPaymentId(string $paymentId): self
    {
        $this->paymentId = $paymentId;
        return $this;
    }

    public function getPaymentId():? string
    {
        return $this->paymentId;
    }

    public function setAccountId(string $id): self
    {
        $this->accountId = $id;
        return $this;
    }
}
