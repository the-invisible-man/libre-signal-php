<?php

declare(strict_types=1);

namespace LibreSignal\InMemoryDatabase;

/**
 * All your implementation code for the in-memory database goes here.
 */
class InMemoryDatabase
{
    public function __construct()
    {
    }

    // ========== Level 1 Operations ==========

    public function set(string $key, string $field, string $value): string
    {
        // TODO: implement
    }

    public function get(string $key, string $field): string
    {
        // TODO: implement
    }

    public function delete(string $key, string $field): string
    {
        // TODO: implement
    }

    // ========== Level 2 Operations ==========

    public function scan(string $key): string
    {
        // TODO: implement
    }

    public function scanByPrefix(string $key, string $prefix): string
    {
        // TODO: implement
    }

    // ========== Level 3 Operations ==========

    public function setAt(string $key, string $field, string $value, int $timestamp): string
    {
        // TODO: implement
    }

    public function setAtWithTtl(string $key, string $field, string $value, int $timestamp, int $ttl): string
    {
        // TODO: implement
    }

    public function deleteAt(string $key, string $field, int $timestamp): string
    {
        // TODO: implement
    }

    public function getAt(string $key, string $field, int $timestamp): string
    {
        // TODO: implement
    }

    public function scanAt(string $key, int $timestamp): string
    {
        // TODO: implement
    }

    public function scanByPrefixAt(string $key, string $prefix, int $timestamp): string
    {
        // TODO: implement
    }

    // ========== Level 4 Operations ==========

    public function backup(int $timestamp): string
    {
        // TODO: implement
    }

    public function restore(int $timestamp, int $timestampToRestore): string
    {
        // TODO: implement
    }
}
