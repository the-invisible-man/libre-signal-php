<?php

declare(strict_types=1);

namespace LibreSignal\BankSystem;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

// Swap the `use` below to run the tests against the reference solution instead:
// use LibreSignal\BankSystem\SimulationSolution as Simulation;

/**
 * Testing suite for the Bank System simulation.
 * ============================================================
 * Run one level at a time from the project root:
 *
 *   vendor/bin/phpunit --testsuite bank_system --group level1
 *
 * Ported to PHP from the Python suite by Eric Zheng (Jan 2026).
 */
final class BankSystemTest extends TestCase
{
    private const MILLISECONDS_IN_1_DAY = 24 * 60 * 60 * 1000;

    // ========== Level 1 ==========

    #[Group('level1')]
    public function testCreateAccount(): void
    {
        $simulation = new Simulation();
        $this->assertTrue($simulation->createAccount(1, 'acc1'));
        $this->assertFalse($simulation->createAccount(2, 'acc1'));
        $this->assertTrue($simulation->createAccount(3, 'acc2'));
    }

    #[Group('level1')]
    public function testDeposit(): void
    {
        $simulation = new Simulation();
        $simulation->createAccount(1, 'acc1');
        $this->assertSame(500, $simulation->deposit(2, 'acc1', 500));
        $this->assertSame(800, $simulation->deposit(3, 'acc1', 300));
        $this->assertNull($simulation->deposit(4, 'non_existent', 100));
    }

    #[Group('level1')]
    public function testTransfer(): void
    {
        $simulation = new Simulation();
        $this->assertTrue($simulation->createAccount(1, 'acc1'));
        $this->assertTrue($simulation->createAccount(2, 'acc2'));
        $this->assertSame(1000, $simulation->deposit(3, 'acc1', 1000));
        $this->assertSame(700, $simulation->transfer(4, 'acc1', 'acc2', 300));
        // Insufficient funds
        $this->assertNull($simulation->transfer(5, 'acc1', 'acc2', 800));
        // Non-existent account
        $this->assertNull($simulation->transfer(6, 'acc1', 'non_existent', 100));
        // Transfer to self
        $this->assertNull($simulation->transfer(7, 'acc1', 'acc1', 100));
    }

    #[Group('level1')]
    public function testExample1(): void
    {
        $simulation = new Simulation();
        $this->assertTrue($simulation->createAccount(1, 'account1'));
        $this->assertFalse($simulation->createAccount(2, 'account1'));
        $this->assertTrue($simulation->createAccount(3, 'account2'));
        $this->assertNull($simulation->deposit(4, 'non_existent', 100));
        $this->assertSame(2700, $simulation->deposit(5, 'account1', 2700));
        $this->assertNull($simulation->transfer(6, 'account1', 'account2', 2701));
        $this->assertSame(2500, $simulation->transfer(7, 'account1', 'account2', 200));
    }

    #[Group('level1')]
    public function testExample2(): void
    {
        $simulation = new Simulation();
        $this->assertTrue($simulation->createAccount(1, 'A'));
        $this->assertTrue($simulation->createAccount(2, 'B'));
        $this->assertSame(500, $simulation->deposit(3, 'A', 500));
        $this->assertSame(200, $simulation->transfer(4, 'A', 'B', 300));
        $this->assertSame(500, $simulation->deposit(5, 'B', 200));
        $this->assertNull($simulation->transfer(6, 'B', 'A', 600));
        $this->assertSame(100, $simulation->transfer(7, 'B', 'A', 400));
    }

    #[Group('level1')]
    public function testExample3(): void
    {
        $simulation = new Simulation();
        $this->assertTrue($simulation->createAccount(1, 'X'));
        $this->assertSame(1000, $simulation->deposit(2, 'X', 1000));
        $this->assertTrue($simulation->createAccount(3, 'Y'));
        $this->assertSame(500, $simulation->transfer(4, 'X', 'Y', 500));
        $this->assertNull($simulation->transfer(5, 'Y', 'X', 600));
        $this->assertSame(800, $simulation->deposit(6, 'Y', 300));
        $this->assertSame(400, $simulation->transfer(7, 'Y', 'X', 400));
    }

    // ========== Level 2 ==========

    #[Group('level2')]
    public function testTopSpendersEmpty(): void
    {
        $simulation = new Simulation();
        $this->assertSame([], $simulation->topSpenders(1, 0));
        $this->assertSame([], $simulation->topSpenders(2, 5));
    }

    #[Group('level2')]
    public function testTopSpendersSingleAccountLessThanN(): void
    {
        $simulation = new Simulation();
        $simulation->createAccount(1, 'acc1');
        $simulation->deposit(2, 'acc1', 1000);
        $simulation->createAccount(3, 'acc2');
        $simulation->transfer(4, 'acc1', 'acc2', 500);
        $this->assertSame(['acc1(500)'], $simulation->topSpenders(5, 1));
    }

    #[Group('level2')]
    public function testTopSpendersTie(): void
    {
        $simulation = new Simulation();
        $simulation->createAccount(1, 'acc1');
        $simulation->createAccount(2, 'acc2');
        $simulation->createAccount(3, 'acc3');
        $simulation->deposit(4, 'acc1', 1000);
        $simulation->deposit(5, 'acc2', 1500);
        $simulation->deposit(6, 'acc3', 1200);
        $simulation->transfer(8, 'acc2', 'acc3', 500); // acc2 outgoing: 500
        $simulation->transfer(7, 'acc1', 'acc2', 500); // acc1 outgoing: 500
        $simulation->transfer(9, 'acc3', 'acc1', 300); // acc3 outgoing: 300
        $this->assertSame(['acc1(500)', 'acc2(500)', 'acc3(300)'], $simulation->topSpenders(10, 3));
    }

    // ========== Level 3 ==========

    #[Group('level3')]
    public function testPayNoAccountId(): void
    {
        $simulation = new Simulation();
        $this->assertNull($simulation->pay(1, 'non_existent', 100));
        $this->assertNull($simulation->getPaymentStatus(2, 'non_existent', 'payment1'));
    }

    #[Group('level3')]
    public function testPayInsufficientFunds(): void
    {
        $simulation = new Simulation();
        $simulation->createAccount(1, 'acc1');
        $simulation->deposit(2, 'acc1', 100);
        $this->assertNull($simulation->pay(3, 'acc1', 200));
    }

    #[Group('level3')]
    public function testPayTopSpenders(): void
    {
        $simulation = new Simulation();
        $simulation->createAccount(1, 'acc1');
        $simulation->deposit(2, 'acc1', 1000);
        $this->assertSame('payment1', $simulation->pay(3, 'acc1', 500));
        $this->assertSame('payment2', $simulation->pay(4, 'acc1', 300));
        $simulation->createAccount(5, 'acc2');
        $simulation->deposit(6, 'acc2', 800);
        $simulation->transfer(7, 'acc2', 'acc1', 200);
        $this->assertSame(['acc1(800)', 'acc2(200)'], $simulation->topSpenders(5, 2));
    }

    #[Group('level3')]
    public function testPaymentStatusNonExistentAccount(): void
    {
        $simulation = new Simulation();
        $this->assertNull($simulation->getPaymentStatus(1, 'non_existent', 'payment1'));
    }

    #[Group('level3')]
    public function testPaymentStatusNonExistentPayment(): void
    {
        $simulation = new Simulation();
        $simulation->createAccount(1, 'acc1');
        $this->assertNull($simulation->getPaymentStatus(2, 'acc1', 'payment1'));
    }

    #[Group('level3')]
    public function testPaymentStatusInconsistentAccountIdAndPaymentId(): void
    {
        $simulation = new Simulation();
        $simulation->createAccount(1, 'acc1');
        $simulation->deposit(2, 'acc1', 1000);
        $paymentId = $simulation->pay(3, 'acc1', 500);
        $this->assertSame('payment1', $paymentId);
        // Create a different account
        $simulation->createAccount(4, 'acc2');
        // Querying payment status with wrong account id
        $this->assertNull($simulation->getPaymentStatus(4, 'acc2', $paymentId));
    }

    #[Group('level3')]
    public function testPayCashbackAndStatus(): void
    {
        $simulation = new Simulation();
        $simulation->createAccount(1, 'acc1');
        $simulation->deposit(2, 'acc1', 1000);
        $paymentId = $simulation->pay(3, 'acc1', 500);
        $this->assertSame('payment1', $paymentId);
        // Before cashback time
        $this->assertSame('IN_PROGRESS', $simulation->getPaymentStatus(4, 'acc1', $paymentId));
        $this->assertSame('IN_PROGRESS', $simulation->getPaymentStatus(26 * 3600, 'acc1', $paymentId));
        // After cashback time (exactly 24 hours after the payment)
        $this->assertSame('CASHBACK_RECEIVED', $simulation->getPaymentStatus(self::MILLISECONDS_IN_1_DAY + 3, 'acc1', $paymentId));
        // Check balance after cashback: deposit 0 to read the current balance
        $this->assertSame(1000 - 500 + 10, $simulation->deposit(28 * 60 * 60 * 1000, 'acc1', 0)); // 2% of 500 is 10
    }

    // ========== Level 4 ==========

    #[Group('level4')]
    public function testAccountId1NotExist(): void
    {
        $simulation = new Simulation();
        $simulation->createAccount(1, 'acc2');
        $this->assertFalse($simulation->mergeAccounts(2, 'acc1', 'acc2'));
    }

    #[Group('level4')]
    public function testAccountId2NotExist(): void
    {
        $simulation = new Simulation();
        $simulation->createAccount(1, 'acc1');
        $this->assertFalse($simulation->mergeAccounts(2, 'acc1', 'acc2'));
    }

    #[Group('level4')]
    public function testMergeCashback(): void
    {
        $simulation = new Simulation();
        $simulation->createAccount(1, 'acc1');
        $simulation->deposit(2, 'acc1', 1000);
        $paymentId = $simulation->pay(3, 'acc1', 500);
        $this->assertNotNull($paymentId);
        $simulation->createAccount(4, 'acc2');
        $simulation->mergeAccounts(5, 'acc2', 'acc1');
        $this->assertSame('IN_PROGRESS', $simulation->getPaymentStatus(6, 'acc2', $paymentId));
        $this->assertSame('CASHBACK_RECEIVED', $simulation->getPaymentStatus(self::MILLISECONDS_IN_1_DAY + 3, 'acc2', $paymentId));
        $this->assertSame(510, $simulation->deposit(self::MILLISECONDS_IN_1_DAY + 5, 'acc2', 0));
    }

    #[Group('level4')]
    public function testMergeTopSpender(): void
    {
        $simulation = new Simulation();
        $simulation->createAccount(1, 'acc1');
        $simulation->deposit(2, 'acc1', 1000);
        $simulation->pay(3, 'acc1', 500);
        $simulation->createAccount(4, 'acc2');
        $simulation->deposit(5, 'acc2', 2000);
        $simulation->pay(6, 'acc2', 800);
        $simulation->mergeAccounts(7, 'acc1', 'acc2');
        // acc1 now has acc2's outgoing too
        $this->assertSame(['acc1(1300)'], $simulation->topSpenders(8, 1));
    }

    #[Group('level4')]
    public function testCashback(): void
    {
        $simulation = new Simulation();
        $simulation->createAccount(1, 'acc1');
        $simulation->deposit(2, 'acc1', 1000);
        $simulation->pay(3, 'acc1', 300);
        $this->assertSame(700, $simulation->getBalance(4, 'acc1', 3));
        $this->assertSame(700, $simulation->getBalance(self::MILLISECONDS_IN_1_DAY + 5, 'acc1', self::MILLISECONDS_IN_1_DAY + 2));
        $this->assertSame(706, $simulation->getBalance(self::MILLISECONDS_IN_1_DAY + 5, 'acc1', self::MILLISECONDS_IN_1_DAY + 3));
    }
}
