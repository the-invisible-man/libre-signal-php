<?php

declare(strict_types=1);

namespace LibreSignal\InMemoryDatabase;

use LibreSignal\InMemoryDatabase\Resources\Record;

/**
 * All your implementation code for the in-memory database goes here.
 */
class InMemoryDatabase
{
    protected array $records = [];

    protected array $backups = [];

    public function __construct()
    {
    }

    // ========== Level 1 Operations ==========

    public function set(string $key, string $field, string $value): string
    {
        $this->createRecord($key, $field, $value);

        return '';
    }

    protected function createRecord(string $key, string $field, string $value, int $createdAt = null, int $ttl = null): void
    {
        if (!array_key_exists($key, $this->records)) {
            $this->records[$key] = [];
        }

        $this->records[$key][$field] = new Record($value, $createdAt, $ttl);
    }

    public function get(string $key, string $field): string
    {
        $record = $this->getRecord($key, $field);

        return $record ? $record->getValue() : '';
    }

    protected function getRecord(string $key, string $field): ?Record
    {
        if (array_key_exists($key, $this->records) && array_key_exists($field, $this->records[$key])) {
            return $this->records[$key][$field];
        }

        return null;
    }

    public function delete(string $key, string $field): string
    {
        if (array_key_exists($key, $this->records) && array_key_exists($field, $this->records[$key])) {
            unset($this->records[$key][$field]);
            return 'true';
        }

        return 'false';
    }

    protected function deleteRecord(string $key, string $field): void
    {
        if (array_key_exists($key, $this->records) && array_key_exists($field, $this->records[$key])) {
            unset($this->records[$key][$field]);
        }
    }

    // ========== Level 2 Operations ==========

    public function scan(string $key): string
    {
        if (isset($this->records[$key])) {
            return $this->print($this->records[$key]);
        }

        return '';
    }

    public function scanByPrefix(string $key, string $prefix): string
    {
        $records = $this->fetchByPrefix($key, $prefix);

        if (!count($records)) {
            return '';
        }

        return $this->print($records);
    }

    protected function fetchByPrefix(string $key, string $prefix, bool $filterExpired = false, int $timestamp = null): array
    {
        $records = [];

        if (isset($this->records[$key])) {

            foreach ($this->records[$key] as $field => $record) {
                if (($filterExpired && $record->isExpired($timestamp))) {
                    continue;
                }

                if (str_starts_with($field, $prefix)) {
                    $records[$field] = $record;
                }
            }
        }

        return $records;
    }

    /**
     * @param Record[] $records
     * @return string
     */
    protected function print(array $records): string
    {
        $result = '';
        ksort($records);

        foreach ($records as $field => $record) {
            $result .= "{$field}({$record->getValue()}), ";
        }

        // remove extra comma
        if (str_ends_with($result, ', ')) {
            $result = substr($result, 0, -2);
        }

        return $result;
    }

    // ========== Level 3 Operations ==========

    public function setAt(string $key, string $field, string $value, int $timestamp): string
    {
        $this->createRecord($key, $field, $value, $timestamp);

        return '';
    }

    public function setAtWithTtl(string $key, string $field, string $value, int $timestamp, int $ttl): string
    {
        $this->createRecord($key, $field, $value, $timestamp, $ttl);

        return '';
    }

    public function deleteAt(string $key, string $field, int $timestamp): string
    {
        $record = $this->getRecord($key, $field);

        if (!$record || $record->isExpired($timestamp)) {
            return 'false';
        }

        $this->deleteRecord($key, $field);

        return 'true';
    }

    public function getAt(string $key, string $field, int $timestamp): string
    {
        $record = $this->getRecord($key, $field);

        if (!$record) {
            return '';
        }

        if ($record->isExpired($timestamp)) {
            return '';
        }

        return $record->getValue();
    }

    public function scanAt(string $key, int $timestamp): string
    {
        if (!isset($this->records[$key])) {
            return '';
        }

        $records = [];

        foreach ($this->records[$key] as $field => $record) {
            if (!$record->isExpired($timestamp)) {
                $records[$field] = $record;
            }
        }

        return $this->print($records);
    }

    public function scanByPrefixAt(string $key, string $prefix, int $timestamp): string
    {
        $records = $this->fetchByPrefix($key, $prefix, true, $timestamp);

        if (!count($records)) {
            return '';
        }

        return $this->print($records);
    }

    // ========== Level 4 Operations ==========

    public function backup(int $timestamp): string
    {
        $backup = [];
        $totalBackedUp = 0;

        foreach ($this->records as $key => $field) {
            foreach ($field as $record) {
                if (!array_key_exists($key, $backup)) {
                    $backup[$key] = [];
                }

                if (!$record->isExpired($timestamp)) {
                    $backup[$key][$field] = clone $record;
                    $totalBackedUp++;
                }
            }
        }

        $this->backups[$timestamp] = $backup;

        return (string)$totalBackedUp;
    }

    public function restore(int $timestamp, int $timestampToRestore): string
    {
        // TODO: implement
    }
}
