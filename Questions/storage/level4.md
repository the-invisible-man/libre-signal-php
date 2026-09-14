# Level 4

Add support for backing up and restoring each user's files.

Backups are stored separately from the live filesystem.

---

## `BACKUP_USER <userId>`

Back up the current state of all files owned by `userId`.

### Backup Contents

For each owned file, store:

- file name
- file size

### Rules

- If `userId` does not exist:
  - return `""`
- Otherwise:
  - snapshot all files currently owned by the user
  - replace any previous backup for that same user
  - return the number of backed-up files as a string

### Important Behavior

After a backup is created, later file operations must not modify that backup.

Example:

```text
user owns:
  /a.txt  10
  /b.txt  20

BACKUP_USER("user")
-> "2"
```

If `/a.txt` is later deleted or changed in the live filesystem, the backup still contains the original backed-up state.

---

## `RESTORE_USER <userId>`

Restore the user's files to their latest backup.

### Invalid User

If `userId` does not exist:

```text
return ""
```

---

### If No Backup Exists

Delete all files currently owned by the user.

Return:

```text
"0"
```

---

### If a Backup Exists

Restore the user's live files to match the latest backup.

### Restore Process

- Remove the user's current files.
- Attempt to recreate each file from the backup.
- Preserve the backed-up file name and size.
- Restore ownership to `userId`.

### Name Conflicts

A backed-up file cannot be restored if another user currently owns a file with the same name.

If a conflict exists:

- skip that file
- continue restoring the other backed-up files

Return the number of files successfully restored.

---

## Capacity Behavior

`RESTORE_USER` does **not** change the user's storage capacity.

The user's capacity limit remains exactly the same as before the restore.

---

## Merge Behavior

### `MERGE_USER(userId1, userId2)`

Backups follow these rules:

- `userId1`'s existing backup is unchanged by the merge.
- `userId2`'s backup is deleted when `userId2` is deleted.
- Files transferred from `userId2` to `userId1` are **not** automatically added to `userId1`'s backup.

---

# Example

```text
ADD_USER("user", 100)
-> "true"
```

User has:

```text
capacity = 100
used = 0
```

---

```text
ADD_FILE_BY("user", "/dir/file1", 50)
-> "50"

ADD_FILE_BY("user", "/file2.txt", 30)
-> "20"
```

User now owns:

```text
/dir/file1   50
/file2.txt   30
```

No backup exists yet.

---

```text
RESTORE_USER("user")
-> "0"
```

Because no backup exists:

- delete all current files
- restore nothing

User now owns no files.

---

```text
ADD_FILE_BY("user", "/file3.mp4", 60)
-> "40"

ADD_FILE_BY("user", "/file4.txt", 10)
-> "30"
```

User now owns:

```text
/file3.mp4   60
/file4.txt   10
```

---

```text
BACKUP_USER("user")
-> "2"
```

Backup now contains:

```text
/file3.mp4   60
/file4.txt   10
```

---

```text
DELETE_FILE("/file3.mp4")
-> "60"

DELETE_FILE("/file4.txt")
-> "10"
```

The live files are deleted, but the backup remains unchanged.

---

```text
ADD_FILE_BY("user", "/dir/file5.new", 20)
-> "80"
```

User now owns:

```text
/dir/file5.new   20
```

---

```text
RESTORE_USER("user")
-> "1"
```

The restore:

- deletes `/dir/file5.new`
- attempts to restore the backed-up files
- restores the files that are still allowed by the current filesystem state

According to the example, one backed-up file is successfully restored.

---

# Expected Output

```json
[
  "true",
  "50",
  "20",
  "0",
  "40",
  "30",
  "2",
  "60",
  "10",
  "80",
  "1"
]
```

---

# Important Rules

- Backups are per-user.
- A new backup replaces the user's previous backup.
- Backups are independent from the live filesystem.
- Restore replaces the user's current file state with the latest backup.
- If no backup exists, restore deletes all current files.
- Restore skips backed-up files whose names are currently taken by another user.
- Restore does not modify the user's capacity.
- Merging users does not modify `userId1`'s backup.
- `userId2` and `userId2`'s backup are deleted after a successful merge.