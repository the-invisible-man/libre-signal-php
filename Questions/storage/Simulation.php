<?php

namespace LibreSignal\Storage;

use LibreSignal\Storage\Resources\File;
use LibreSignal\Storage\Resources\User;

class Simulation
{
    protected array $files = [];

    protected array $users = [];

    protected ?User $admin = null;

    public function addFile(string $name, int $bytes): string
    {
        if ($this->getFile($name)) {
            return 'false';
        }

        $this->files[$name] = new File($name, $bytes);

        $this->getOrCreateAdmin()
            ->addFile($this->files[$name]);

        return 'true';
    }

    public function addUser(string $userId, int $capacity): string
    {
        if ($this->getUser($userId)) {
            return 'false';
        }

        $this->users[$userId] = new User($userId, $capacity);

        return 'true';
    }

    public function addFileBy(string $userId, string $name, int $size): string
    {
        $user = $this->getUser($userId);

        if (!$user) {
            return '';
        }

        $file = $this->getFile($name);

        if ($file) {
            return '';
        }

        if (!$user->hasCapacity($size)) {
            return '';
        }

        $file = new File($name, $size);

        $user->addFile($file);
        $this->files[$name] = $file;

        return (string)$user->getRemainingCapacity();
    }

    public function mergeUser(string $userId1, string $userId2): string
    {
        if ($userId1 === $userId2) {
            return '';
        }

        $user1 = $this->getUser($userId1);
        $user2 = $this->getUser($userId2);

        if (!$user1 || !$user2) {
            return '';
        }

        foreach ($user2->getFiles() as $file) {
            $user1->addFile($file);
        }

        $user1->increaseCapacityBy($user2->getCapacity());

        $this->deleteUser($user2);

        return (string)$user1->getRemainingCapacity();
    }

    protected function deleteUser(User $user): void
    {
        unset($this->users[$user->getId()]);
    }

    public function getUser(string $userId): ?User
    {
        return $this->users[$userId] ?? null;
    }

    public function getFileSize(string $name): string
    {
        $file = $this->getFile($name);

        return (string)$file?->getSize();
    }

    public function deleteFile(string $name): string
    {
        $file = $this->getFile($name);

        if (!$file) {
            return '';
        }

        unset($this->files[$name]);

        $file->getUser()?->removeFile($file->getName());

        return (string)$file->getSize();
    }

    public function getNLargest(string $prefix, int $n): string
    {
        $files = $this->getFilesByPrefix($prefix);

        if (!count($files)) {
            return '';
        }

        usort($files, function (File $a, File $b) : int{
            return ($b->getSize() <=> $a->getSize())
                ?: ($a->getName() <=> $b->getName());
        });

        return $this->printFiles(array_slice($files, 0, $n));
    }

    /**
     * @param File[] $files
     * @return string
     */
    protected function printFiles(array $files): string
    {
        $result = '';

        foreach ($files as $file) {
            $result .= "{$file->getName()}({$file->getSize()}), ";
        }

        if (str_ends_with($result, ', ')) {
            $result = substr($result, 0, -2);
        }

        return $result;
    }

    protected function getFilesByPrefix(string $prefix): array
    {
        $files = [];

        foreach ($this->files as $file) {
            if (str_starts_with($file->getName(), $prefix)) {
                $files[] = $file;
            }
        }

        return $files;
    }

    protected function getFile(string $name):? File
    {
        return $this->files[$name] ?? null;
    }

    protected function getOrCreateAdmin(): User
    {
        if (!$this->admin) {
            $this->admin = new User('admin', 0, true);
        }

        return $this->admin;
    }
}
