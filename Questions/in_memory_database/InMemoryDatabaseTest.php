<?php

declare(strict_types=1);

namespace LibreSignal\InMemoryDatabase;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

// Swap the `use` below to run the tests against the reference solution instead:
// use LibreSignal\InMemoryDatabase\InMemoryDatabaseSolution as InMemoryDatabase;

/**
 * Testing suite for the In-Memory Database.
 * ============================================================
 * Run one level at a time from the project root:
 *
 *   vendor/bin/phpunit --testsuite in_memory_database --group level1
 */
final class InMemoryDatabaseTest extends TestCase
{
    // ========== Level 1 ==========

    #[Group('level1')]
    public function testSetAndGet(): void
    {
        $db = new InMemoryDatabase();
        $this->assertSame('', $db->set('user1', 'name', 'Alice'));
        $this->assertSame('', $db->set('user1', 'age', '30'));
        $this->assertSame('Alice', $db->get('user1', 'name'));
        $this->assertSame('30', $db->get('user1', 'age'));
    }

    #[Group('level1')]
    public function testSetOverwrite(): void
    {
        $db = new InMemoryDatabase();
        $this->assertSame('', $db->set('user1', 'name', 'Alice'));
        $this->assertSame('', $db->set('user1', 'name', 'Bob'));
        $this->assertSame('Bob', $db->get('user1', 'name'));
    }

    #[Group('level1')]
    public function testGetNonExistent(): void
    {
        $db = new InMemoryDatabase();
        $this->assertSame('', $db->get('user1', 'field'));
        $this->assertSame('', $db->set('user1', 'name', 'Alice'));
        $this->assertSame('', $db->get('user1', 'non_existent'));
    }

    #[Group('level1')]
    public function testDelete(): void
    {
        $db = new InMemoryDatabase();
        $this->assertSame('', $db->set('user1', 'name', 'Alice'));
        $this->assertSame('true', $db->delete('user1', 'name'));
        $this->assertSame('', $db->get('user1', 'name'));
        $this->assertSame('false', $db->delete('user1', 'name'));
        $this->assertSame('false', $db->delete('non_existent', 'field'));
    }

    // ========== Level 2 ==========

    #[Group('level2')]
    public function testScan(): void
    {
        $db = new InMemoryDatabase();
        $this->assertSame('', $db->set('user1', 'name', 'Alice'));
        $this->assertSame('', $db->set('user1', 'age', '30'));
        $this->assertSame('', $db->set('user1', 'city', 'NY'));
        $this->assertSame('', $db->set('user1', 'abc', '123'));
        $this->assertSame('abc(123), age(30), city(NY), name(Alice)', $db->scan('user1'));
        $this->assertSame('', $db->scan('non_existent'));
    }

    #[Group('level2')]
    public function testScanByPrefix(): void
    {
        $db = new InMemoryDatabase();
        $this->assertSame('', $db->set('user1', 'name', 'Alice'));
        $this->assertSame('', $db->set('user1', 'age', '30'));
        $this->assertSame('', $db->set('user1', 'city', 'NY'));
        $this->assertSame('', $db->set('user1', 'abc', '123'));
        $this->assertSame('abc(123), age(30)', $db->scanByPrefix('user1', 'a'));
        $this->assertSame('name(Alice)', $db->scanByPrefix('user1', 'n'));
        $this->assertSame('', $db->scanByPrefix('user1', 'xyz'));
    }

    // ========== Level 3 ==========

    // setAt and getAt are tested together since they are closely related.
    // The same goes for setAtWithTtl and getAt.
    #[Group('level3')]
    public function testSetAtAndGetAt(): void
    {
        $db = new InMemoryDatabase();
        $this->assertSame('', $db->setAt('user1', 'name', 'Alice', timestamp: 100));
        $this->assertSame('', $db->setAt('user1', 'age', '30', timestamp: 101));
        $this->assertSame('Alice', $db->getAt('user1', 'name', timestamp: 102));
        $this->assertSame('30', $db->getAt('user1', 'age', timestamp: 103));
    }

    #[Group('level3')]
    public function testGetAtNonExistent(): void
    {
        $db = new InMemoryDatabase();
        $this->assertSame('', $db->getAt('user2', 'name', timestamp: 100));
        // getAt returns an empty string for a non-existent field
        $this->assertSame('', $db->getAt('user1', 'non_existent', timestamp: 101));
    }

    #[Group('level3')]
    public function testSetAtWithTtlAndGetAt(): void
    {
        $db = new InMemoryDatabase();
        // The field is available between [100, 110)
        $this->assertSame('', $db->setAtWithTtl('user1', 'name', 'Alice', timestamp: 100, ttl: 10));
        // At timestamp 105, the field should still be available
        $this->assertSame('Alice', $db->getAt('user1', 'name', timestamp: 105));
        // At timestamp 110, the field should have expired
        $this->assertSame('', $db->getAt('user1', 'name', timestamp: 110));
        // At timestamp 115, the field should still be expired
        $this->assertSame('', $db->getAt('user1', 'name', timestamp: 115));
    }

    // setAtWithTtl can overwrite an existing field and reset the expiry time
    #[Group('level3')]
    public function testSetAtWithTtlOverwriteWithoutExpiry(): void
    {
        $db = new InMemoryDatabase();
        // Set a field with TTL
        $this->assertSame('', $db->setAtWithTtl('user1', 'name', 'Alice', timestamp: 100, ttl: 10));
        // Overwrite the same field without TTL
        $this->assertSame('', $db->setAt('user1', 'name', 'Bob', timestamp: 105));
        // The field should now return the new value and will not expire
        $this->assertSame('Bob', $db->getAt('user1', 'name', timestamp: 110));
        $this->assertSame('Bob', $db->getAt('user1', 'name', timestamp: 140));
    }

    #[Group('level3')]
    public function testSetAtWithTtlOverwriteWithExpiry(): void
    {
        $db = new InMemoryDatabase();
        // Set a field with TTL
        $this->assertSame('', $db->setAtWithTtl('user1', 'name', 'Alice', timestamp: 100, ttl: 10));
        // At timestamp 105, the field should still be available
        $this->assertSame('Alice', $db->getAt('user1', 'name', timestamp: 105));
        // Overwrite the same field with a new TTL: [106, 116)
        $this->assertSame('', $db->setAtWithTtl('user1', 'name', 'Bob', timestamp: 106, ttl: 10));
        // The field should now return the new value and expire at timestamp 116
        $this->assertSame('Bob', $db->getAt('user1', 'name', timestamp: 110));
        $this->assertSame('', $db->getAt('user1', 'name', timestamp: 117));
    }

    #[Group('level3')]
    public function testSetAtWithTtlAndGetAll(): void
    {
        $db = new InMemoryDatabase();
        // Field "name" is available between [100, 110)
        $this->assertSame('', $db->setAtWithTtl('user1', 'name', 'Alice', timestamp: 100, ttl: 10));
        // Field "age" is available between [101, 106)
        $this->assertSame('', $db->setAtWithTtl('user1', 'age', '30', timestamp: 101, ttl: 5));
        // Field "city" is available between [102, 117)
        $this->assertSame('', $db->setAtWithTtl('user1', 'city', 'NY', timestamp: 102, ttl: 15));
        // All fields should be available at any time via the timestamp-less get()
        $this->assertSame('Alice', $db->get('user1', 'name'));
        $this->assertSame('30', $db->get('user1', 'age'));
        $this->assertSame('NY', $db->get('user1', 'city'));
    }

    #[Group('level3')]
    public function testScanAt(): void
    {
        $db = new InMemoryDatabase();
        // Field "name" is available between [100, 110)
        $this->assertSame('', $db->setAtWithTtl('user1', 'name', 'Alice', timestamp: 100, ttl: 10));
        // Field "age" is available between [101, 106)
        $this->assertSame('', $db->setAtWithTtl('user1', 'age', '30', timestamp: 101, ttl: 5));
        // Field "city" is available between [102, 117)
        $this->assertSame('', $db->setAtWithTtl('user1', 'city', 'NY', timestamp: 102, ttl: 15));
        // At timestamp 105, all fields should be available
        $this->assertSame('age(30), city(NY), name(Alice)', $db->scanAt('user1', timestamp: 105));
        // At timestamp 106, only "age" should have expired
        $this->assertSame('city(NY), name(Alice)', $db->scanAt('user1', timestamp: 106));
        // At timestamp 110, "name" should have expired too
        $this->assertSame('city(NY)', $db->scanAt('user1', timestamp: 110));
        // At timestamp 116, still only "city" should be available
        $this->assertSame('city(NY)', $db->scanAt('user1', timestamp: 116));
        // At timestamp 117, all fields should have expired
        $this->assertSame('', $db->scanAt('user1', timestamp: 117));
    }

    // scan() doesn't consider expiry time, so it should return all fields
    // regardless of the timestamp
    #[Group('level3')]
    public function testScanIgnoresExpiry(): void
    {
        $db = new InMemoryDatabase();
        $this->assertSame('', $db->setAtWithTtl('user1', 'name', 'Alice', timestamp: 100, ttl: 10));
        $this->assertSame('', $db->setAtWithTtl('user1', 'age', '30', timestamp: 101, ttl: 5));
        $this->assertSame('', $db->setAtWithTtl('user1', 'city', 'NY', timestamp: 102, ttl: 15));
        $this->assertSame('age(30), city(NY), name(Alice)', $db->scan('user1'));
    }

    #[Group('level3')]
    public function testScanByPrefixAt(): void
    {
        $db = new InMemoryDatabase();
        // Field "name" is available between [100, 110)
        $this->assertSame('', $db->setAtWithTtl('user1', 'name', 'Alice', timestamp: 100, ttl: 10));
        // Field "age" is available between [101, 106)
        $this->assertSame('', $db->setAtWithTtl('user1', 'age', '30', timestamp: 101, ttl: 5));
        // Field "city" is available between [102, 117)
        $this->assertSame('', $db->setAtWithTtl('user1', 'city', 'NY', timestamp: 102, ttl: 15));
        // Field "nationality" is available between [103, 108)
        $this->assertSame('', $db->setAtWithTtl('user1', 'nationality', 'free_country', timestamp: 103, ttl: 5));
        // At timestamp 105, "age" should be returned
        $this->assertSame('age(30)', $db->scanByPrefixAt('user1', prefix: 'a', timestamp: 105));
        // At timestamp 106, "age" should have expired, so it should return an empty string
        $this->assertSame('', $db->scanByPrefixAt('user1', prefix: 'a', timestamp: 106));
        // At timestamp 107, both "name" and "nationality" should be returned
        $this->assertSame('name(Alice), nationality(free_country)', $db->scanByPrefixAt('user1', prefix: 'n', timestamp: 107));
        // At timestamp 109, "nationality" should have expired, so only "name" is returned
        $this->assertSame('name(Alice)', $db->scanByPrefixAt('user1', prefix: 'n', timestamp: 109));
    }

    // scanByPrefix() doesn't consider expiry time, so it should return all
    // fields with the given prefix regardless of the timestamp
    #[Group('level3')]
    public function testScanByPrefixIgnoresExpiry(): void
    {
        $db = new InMemoryDatabase();
        $this->assertSame('', $db->setAtWithTtl('user1', 'name', 'Alice', timestamp: 100, ttl: 10));
        $this->assertSame('', $db->setAtWithTtl('user1', 'age', '30', timestamp: 101, ttl: 5));
        $this->assertSame('', $db->setAtWithTtl('user1', 'city', 'NY', timestamp: 102, ttl: 15));
        $this->assertSame('', $db->setAtWithTtl('user1', 'nationality', 'free_country', timestamp: 103, ttl: 5));
        $this->assertSame('age(30)', $db->scanByPrefix('user1', prefix: 'a'));
        $this->assertSame('name(Alice), nationality(free_country)', $db->scanByPrefix('user1', prefix: 'n'));
    }

    // ========== Level 4 ==========

    #[Group('level4')]
    public function testBackupReturnsCount(): void
    {
        $db = new InMemoryDatabase();
        $this->assertSame('', $db->setAtWithTtl('A', 'B', 'C', timestamp: 1, ttl: 10));
        $this->assertSame('1', $db->backup(3));
    }

    #[Group('level4')]
    public function testBackupExcludesExpired(): void
    {
        $db = new InMemoryDatabase();
        $db->setAtWithTtl('A', 'B', 'C', 1, 10); // expiry = 11
        $this->assertSame('0', $db->backup(12));
    }

    #[Group('level4')]
    public function testRestoreFromSpecExample(): void
    {
        $db = new InMemoryDatabase();
        $db->setAtWithTtl('A', 'B', 'C', 1, 10);
        $db->backup(3);
        $db->setAt('A', 'D', 'E', 4);
        $db->backup(5);
        $db->deleteAt('A', 'B', 8);
        $db->backup(9);
        // B should now expire at 10 + 6 = 16
        $db->restore(10, 7);
        $this->assertSame('', $db->setAt('B', 'C', 'D', 11));
        $this->assertSame('B(C), D(E)', $db->scanAt('A', 15));
        $this->assertSame('D(E)', $db->scanAt('A', 16));
        $this->assertSame('C(D)', $db->scanAt('B', 17));
    }
}
