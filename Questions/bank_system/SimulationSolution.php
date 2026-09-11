<?php

declare(strict_types=1);

namespace LibreSignal\BankSystem;

/**
 * A solution for the bank system simulation problem.
 * ============================================================
 * This implementation includes account creation, deposits, transfers,
 * top spenders tracking, payments with cashback, account merging, and
 * historical balance retrieval.
 *
 * Ported to PHP from the Python reference solution by Eric Zheng (Jan 2026).
 */
final class Account
{
    public int $balance = 0;

    /** Total outgoing transactions (transfers out, payments). */
    public int $outgoing = 0;

    /** @var array<string, string> payment_id => status */
    public array $payments = [];

    /**
     * Balance history: list of [timestamp, balance].
     * Records balance AFTER each operation.
     *
     * @var list<array{0:int,1:int}>
     */
    public array $balanceHistory;

    public function __construct(public readonly string $accountId, public int $createdAt)
    {
        $this->balanceHistory = [[$createdAt, 0]];
    }

    /** Record current balance at this timestamp. */
    public function recordBalance(int $timestamp): void
    {
        $this->balanceHistory[] = [$timestamp, $this->balance];
    }

    public function deposit(int $amount): int
    {
        $this->balance += $amount;
        return $this->balance;
    }

    /** Withdraw amount if sufficient funds. Returns true if successful. */
    public function withdraw(int $amount): bool
    {
        if ($this->balance < $amount) {
            return false;
        }
        $this->balance -= $amount;
        $this->outgoing += $amount;
        return true;
    }

    /** Get balance at a specific timestamp. Returns null if account didn't exist. */
    public function getBalanceAt(int $timeAt): ?int
    {
        if ($timeAt < $this->createdAt) {
            return null;
        }
        // Find the latest balance at or before timeAt.
        // You could use binary search for efficiency.
        $result = null;
        foreach ($this->balanceHistory as [$ts, $balance]) {
            if ($ts <= $timeAt) {
                $result = $balance;
            } else {
                break;
            }
        }
        return $result;
    }
}

class SimulationSolution
{
    /** 24 hours in milliseconds. */
    private const CASHBACK_DELAY = 24 * 60 * 60 * 1000;

    /** @var array<string, Account> */
    private array $accounts = [];

    /** Global counter for payment IDs. */
    private int $paymentCounter = 0;

    /**
     * Pending cashbacks, ordered by due timestamp: list of
     * [timestamp, accountId, amount, paymentId].
     *
     * @var list<array{0:int,1:string,2:int,3:string}>
     */
    private array $pendingCashbacks = [];

    public function createAccount(int $timestamp, string $accountId): bool
    {
        $this->processCashbacks($timestamp);
        if (isset($this->accounts[$accountId])) {
            return false;
        }
        $this->accounts[$accountId] = new Account($accountId, $timestamp);
        return true;
    }

    public function deposit(int $timestamp, string $accountId, int $amount): ?int
    {
        $this->processCashbacks($timestamp);
        if (!isset($this->accounts[$accountId])) {
            return null;
        }
        $account = $this->accounts[$accountId];
        $result = $account->deposit($amount);
        $account->recordBalance($timestamp);
        return $result;
    }

    public function transfer(int $timestamp, string $sourceAccountId, string $targetAccountId, int $amount): ?int
    {
        $this->processCashbacks($timestamp);
        if (!isset($this->accounts[$sourceAccountId]) || !isset($this->accounts[$targetAccountId])) {
            return null;
        }
        if ($sourceAccountId === $targetAccountId) {
            return null;
        }

        $source = $this->accounts[$sourceAccountId];
        $target = $this->accounts[$targetAccountId];

        if (!$source->withdraw($amount)) {
            return null;
        }

        $target->deposit($amount);
        $source->recordBalance($timestamp);
        $target->recordBalance($timestamp);
        return $source->balance;
    }

    /** @return list<string> */
    public function topSpenders(int $timestamp, int $n): array
    {
        $this->processCashbacks($timestamp);
        $accounts = array_values($this->accounts);
        // Sort by outgoing (descending), then by account id (ascending) for ties.
        // strcmp is used on purpose: `<=>` would compare numeric-looking ids as numbers.
        usort($accounts, fn (Account $a, Account $b) =>
            [$b->outgoing, strcmp($a->accountId, $b->accountId)] <=> [$a->outgoing, 0]);
        return array_map(
            fn (Account $acc) => "{$acc->accountId}({$acc->outgoing})",
            array_slice($accounts, 0, max($n, 0)),
        );
    }

    /** Process all cashbacks that are due at or before the given timestamp. */
    private function processCashbacks(int $timestamp): void
    {
        while ($this->pendingCashbacks !== [] && $this->pendingCashbacks[0][0] <= $timestamp) {
            [$cbTimestamp, $accountId, $amount, $paymentId] = array_shift($this->pendingCashbacks);
            if (isset($this->accounts[$accountId])) {
                $account = $this->accounts[$accountId];
                $account->deposit($amount);
                $account->payments[$paymentId] = 'CASHBACK_RECEIVED';
                $account->recordBalance($cbTimestamp);
            }
        }
    }

    public function pay(int $timestamp, string $accountId, int $amount): ?string
    {
        $this->processCashbacks($timestamp);
        if (!isset($this->accounts[$accountId])) {
            return null;
        }

        $account = $this->accounts[$accountId];
        // Outgoing is accounted for in withdraw().
        if (!$account->withdraw($amount)) {
            return null;
        }

        $this->paymentCounter++;
        $paymentId = "payment{$this->paymentCounter}";
        $account->payments[$paymentId] = 'IN_PROGRESS';
        $account->recordBalance($timestamp);

        // Schedule cashback (2% rounded down) for 24 hours later.
        $cashbackAmount = intdiv($amount * 2, 100);
        $this->pendingCashbacks[] = [$timestamp + self::CASHBACK_DELAY, $accountId, $cashbackAmount, $paymentId];

        return $paymentId;
    }

    public function getPaymentStatus(int $timestamp, string $accountId, string $payment): ?string
    {
        $this->processCashbacks($timestamp);
        if (!isset($this->accounts[$accountId])) {
            return null;
        }
        return $this->accounts[$accountId]->payments[$payment] ?? null;
    }

    public function mergeAccounts(int $timestamp, string $accountId1, string $accountId2): bool
    {
        $this->processCashbacks($timestamp);
        if ($accountId1 === $accountId2) {
            return false;
        }
        if (!isset($this->accounts[$accountId1]) || !isset($this->accounts[$accountId2])) {
            return false;
        }

        $account1 = $this->accounts[$accountId1];
        $account2 = $this->accounts[$accountId2];

        $account1->balance += $account2->balance;
        $account1->outgoing += $account2->outgoing;

        // account1 inherits account2's payment statuses.
        $account1->payments = array_merge($account1->payments, $account2->payments);

        // Merge balance history: combine and sort by timestamp (usort is stable in PHP 8+).
        $account1->balanceHistory = array_merge($account1->balanceHistory, $account2->balanceHistory);
        usort($account1->balanceHistory, fn (array $a, array $b) => $a[0] <=> $b[0]);

        $account1->createdAt = min($account1->createdAt, $account2->createdAt);
        $account1->recordBalance($timestamp);

        // Redirect pending cashbacks from account2 to account1.
        foreach ($this->pendingCashbacks as $i => $cb) {
            if ($cb[1] === $accountId2) {
                $this->pendingCashbacks[$i][1] = $accountId1;
            }
        }

        unset($this->accounts[$accountId2]);
        return true;
    }

    public function getBalance(int $timestamp, string $accountId, int $timeAt): ?int
    {
        $this->processCashbacks($timestamp);
        if (!isset($this->accounts[$accountId])) {
            return null;
        }
        return $this->accounts[$accountId]->getBalanceAt($timeAt);
    }
}
