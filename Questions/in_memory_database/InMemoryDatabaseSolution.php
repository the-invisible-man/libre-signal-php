<?php

declare(strict_types=1);

namespace LibreSignal\InMemoryDatabase;

/**
 * A solution for the in-memory database problem.
 *
 * Ported to PHP from the Python reference solution.
 *
 * Note on PHP arrays: a numeric-looking string key such as "30" is silently
 * converted to the int key 30. Every place that reads keys back casts them to
 * string so the output format stays correct regardless.
 */
class InMemoryDatabaseSolution
{
    /**
     * key => field => [value, expiry]. A null expiry means the field never expires.
     *
     * @var array<string, array<string, array{0:string,1:?int}>>
     */
    private array $database = [];

    /**
     * Level 4: backup timestamps, in increasing order, for a quick lookup of
     * the latest backup before a restore timestamp.
     *
     * @var list<int>
     */
    private array $backupTimestamps = [];

    /**
     * Level 4: the backed-up states, parallel to $backupTimestamps.
     * key => field => [value, remainingLifespan].
     *
     * @var list<array<string, array<string, array{0:string,1:?int}>>>
     */
    private array $backupStates = [];

    // ========== Level 1 ==========

    public function set(string $key, string $field, string $value): string
    {
        // For Levels 1 and 2 a plain `$this->database[$key][$field] = $value`
        // is enough. Level 3 needs an expiry alongside the value.
        return $this->setInternal($key, $field, $value, null);
    }

    public function get(string $key, string $field): string
    {
        return $this->database[$key][$field][0] ?? '';
    }

    public function delete(string $key, string $field): string
    {
        if (!isset($this->database[$key][$field])) {
            return 'false';
        }
        unset($this->database[$key][$field]);
        return 'true';
    }

    // ========== Level 2 ==========

    public function scan(string $key): string
    {
        return $this->scanByPrefix($key, '');
    }

    public function scanByPrefix(string $key, string $prefix): string
    {
        if (!isset($this->database[$key])) {
            return '';
        }
        $items = [];
        foreach ($this->database[$key] as $field => [$value]) {
            $field = (string) $field;
            if (str_starts_with($field, $prefix)) {
                $items[$field] = $value;
            }
        }
        return $this->format($items);
    }

    // ========== Level 3 ==========

    /** Helper for setting a field with an optional expiry time. */
    private function setInternal(string $key, string $field, string $value, ?int $expiry): string
    {
        $this->database[$key][$field] = [$value, $expiry];
        return '';
    }

    public function setAt(string $key, string $field, string $value, int $timestamp): string
    {
        return $this->setInternal($key, $field, $value, null);
    }

    public function setAtWithTtl(string $key, string $field, string $value, int $timestamp, int $ttl): string
    {
        return $this->setInternal($key, $field, $value, $timestamp + $ttl);
    }

    public function deleteAt(string $key, string $field, int $timestamp): string
    {
        if (!$this->isAlive($key, $field, $timestamp)) {
            return 'false';
        }
        unset($this->database[$key][$field]);
        return 'true';
    }

    private function isAlive(string $key, string $field, int $timestamp): bool
    {
        if (!isset($this->database[$key][$field])) {
            return false;
        }
        $expiry = $this->database[$key][$field][1];
        return $expiry === null || $timestamp < $expiry;
    }

    public function getAt(string $key, string $field, int $timestamp): string
    {
        if (!$this->isAlive($key, $field, $timestamp)) {
            return '';
        }
        return $this->database[$key][$field][0];
    }

    public function scanAt(string $key, int $timestamp): string
    {
        return $this->scanByPrefixAt($key, '', $timestamp);
    }

    public function scanByPrefixAt(string $key, string $prefix, int $timestamp): string
    {
        if (!isset($this->database[$key])) {
            return '';
        }
        $items = [];
        foreach ($this->database[$key] as $field => [$value]) {
            $field = (string) $field;
            if (str_starts_with($field, $prefix) && $this->isAlive($key, $field, $timestamp)) {
                $items[$field] = $value;
            }
        }
        return $this->format($items);
    }

    /**
     * Render field => value pairs as "field1(value1), field2(value2)" with
     * fields sorted lexicographically.
     *
     * @param array<string, string> $items
     */
    private function format(array $items): string
    {
        // SORT_STRING keeps numeric-looking field names in lexicographic order.
        ksort($items, SORT_STRING);
        $parts = [];
        foreach ($items as $field => $value) {
            $parts[] = "{$field}({$value})";
        }
        return implode(', ', $parts);
    }

    // ========== Level 4 ==========

    public function backup(int $timestamp): string
    {
        // Snapshot every live field along with its remaining lifespan
        // (null means it never expires).
        $state = [];
        foreach ($this->database as $key => $fields) {
            $key = (string) $key;
            foreach ($fields as $field => [$value, $expiry]) {
                $field = (string) $field;
                if ($this->isAlive($key, $field, $timestamp)) {
                    $state[$key][$field] = [$value, $expiry === null ? null : $expiry - $timestamp];
                }
            }
        }
        $this->backupTimestamps[] = $timestamp;
        $this->backupStates[] = $state;
        // Number of non-empty, non-expired records (keys).
        return (string) count($state);
    }

    public function restore(int $timestamp, int $timestampToRestore): string
    {
        // Find the latest backup taken at or before timestampToRestore.
        $idx = null;
        for ($i = count($this->backupTimestamps) - 1; $i >= 0; $i--) {
            if ($this->backupTimestamps[$i] <= $timestampToRestore) {
                $idx = $i;
                break;
            }
        }
        if ($idx === null) {
            return '';
        }

        // Rebuild the database, recalculating expiries from the current timestamp.
        $this->database = [];
        foreach ($this->backupStates[$idx] as $key => $fields) {
            foreach ($fields as $field => [$value, $remainingLifespan]) {
                $expiry = $remainingLifespan === null ? null : $timestamp + $remainingLifespan;
                $this->setInternal((string) $key, (string) $field, $value, $expiry);
            }
        }
        return '';
    }
}
