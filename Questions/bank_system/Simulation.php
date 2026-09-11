<?php

declare(strict_types=1);

namespace LibreSignal\BankSystem;

use LibreSignal\BankSystem\Resources\Transaction;
use LibreSignal\BankSystem\Resources\Account;

/**
 * All your implementation code for the bank system simulation goes here.
 */
class Simulation
{
    /**
     * @var Account[]
     */
    protected array $accounts = [];

    protected array $payments = [];

    public function __construct()
    {
    }

    // ========== Level 1 Operations ==========

    public function createAccount(int $timestamp, string $accountId): bool
    {
        if ($this->getAccount($accountId)) {
            return false;
        }

        $account = new Account($accountId, $timestamp);

        $this->accounts[$accountId] = $account;

        return true;
    }

    public function deposit(int $timestamp, string $accountId, int $amount): ?int
    {
        $this->processCashback($timestamp);

        if (!$account = $this->getAccount($accountId)) {
            return null;
        }

        $transaction = new Transaction($accountId, $amount, $timestamp, Transaction::TYPE['DEPOSIT']);

        $account->addTransaction($transaction);

        return $account->getBalance();
    }

    public function transfer(int $timestamp, string $sourceAccountId, string $targetAccountId, int $amount): ?int
    {
        $this->processCashback($timestamp);

        if ($sourceAccountId === $targetAccountId) {
            return null;
        }

        if (!($source = $this->getAccount($sourceAccountId)) || !($destination = $this->getAccount($targetAccountId))) {
            return null;
        }

        if ($amount > $source->getBalance()) {
            return null;
        }

        $sourceTransaction = new Transaction($sourceAccountId, -$amount, $timestamp, Transaction::TYPE['TRANSFER']);
        $destinationTransaction = new Transaction($targetAccountId, $amount, $timestamp, Transaction::TYPE['TRANSFER']);

        $source->addTransaction($sourceTransaction);
        $destination->addTransaction($destinationTransaction);

        return $source->getBalance();
    }

    protected function getAccount(string $id, bool $force = false):? \LibreSignal\BankSystem\Resources\Account
    {
        if (array_key_exists($id, $this->accounts)) {
            $account = $this->accounts[$id];

            if ($account->isDeleted()) {
                return $force ? $account : null;
            }

            return $account;
        }

        return null;
    }


    // ========== Level 2 Operations ==========

    /** @return list<string> */
    public function topSpenders(int $timestamp, int $n): array
    {
        $accounts = array_values($this->accounts);

        usort($accounts, function (Account $a, Account $b): int {
            return ($b->getTotalSpent() <=> $a->getTotalSpent())
                ?: ($a->getId() <=> $b->getId());
        });

        $accounts = array_slice($accounts, 0, $n);

        return array_map(
            fn(Account $account) => "{$account->getId()}({$account->getTotalSpent()})",
            $accounts
        );
    }

    // ========== Level 3 Operations ==========

    public function pay(int $timestamp, string $accountId, int $amount): ?string
    {
        $this->processCashback($timestamp);

        $account = $this->getAccount($accountId);

        if (!$account) {
            return null;
        }

        if ($amount > $account->getBalance()) {
            return null;
        }

        $transaction = new Transaction($accountId, -$amount, $timestamp, Transaction::TYPE['PAYMENT']);
        $account->addTransaction($transaction);

        $id = "payment".(count($this->payments) + 1);
        $this->payments[$id] = $transaction;
        $transaction->setPaymentId($id);

        return $transaction->getPaymentId();
    }

    public function getPaymentStatus(int $timestamp, string $accountId, string $payment): ?string
    {
        $account = $this->getAccount($accountId);

        if (!$account) {
            return null;
        }

        $payment = $this->getPayment($payment);

        if (!$payment) {
            return null;
        }

        if ($payment->getAccountId() !== $account->getId()) {
            return null;
        }

        if ($payment->isClearedPayment($timestamp)) {
            if (!$payment->hasIssuedCashback()) {
                $this->issueCashback($payment, $timestamp);
            }

            return 'CASHBACK_RECEIVED';
        }

        return'IN_PROGRESS';
    }

    public function processCashback(int $currentTimestamp): void
    {
        foreach ($this->payments as $payment) {
            $this->issueCashback($payment, $currentTimestamp);
        }
    }

    protected function issueCashback(Transaction $payment, int $currentTimestamp): void
    {
        if ($payment->isClearedPayment($currentTimestamp) && !$payment->hasIssuedCashback()) {
            $account = $this->getAccount($payment->getAccountId());

            // round down by dropping decimal values
            $amount = (int)(abs($payment->getAmount()) * 0.02);
            $cashback = new Transaction($account->getId(), $amount, $currentTimestamp, Transaction::TYPE['CASHBACK']);

            $payment->setRefTransaction($cashback);

            $account->addTransaction($cashback, false);
        }
    }

    public function getPayment(string $id):? Transaction
    {
        return $this->payments[$id] ?? null;
    }

    // ========== Level 4 Operations ==========

    public function mergeAccounts(int $timestamp, string $accountId1, string $accountId2): bool
    {
        if ($accountId1 === $accountId2) {
            return false;
        }

        if (!($receiver = $this->getAccount($accountId1)) || !($source = $this->getAccount($accountId2))) {
            return false;
        }

        foreach ($source->getTransactions() as $transaction) {
            $transaction->setAccountId($receiver->getId());
            $receiver->addTransaction($transaction);
        }

        $source->markDeleted($timestamp);

        $this->processCashback($timestamp);

        return true;
    }

    public function getBalance(int $timestamp, string $accountId, int $timeAt): ?int
    {
        $account = $this->getAccount($accountId, true);

        if (!$account) {
            return null;
        }

        if ($account->getCreatedAt() > $timeAt) {
            return null;
        }

        $this->processCashback($timeAt);

        return $account->getBalanceAt($timeAt);
    }
}
