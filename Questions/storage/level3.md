# Level 3

Add support for multiple users with individual storage capacity limits.

All users share the same global filesystem.

---

## `ADD_USER <userId> <capacity>`

Creates a new user with a storage capacity limit.

### Rules

- `capacity` is the maximum total size, in bytes, of files owned by the user.
- If `userId` already exists:
    - do nothing
    - return `"false"`
- Otherwise:
    - create the user
    - return `"true"`

---

## `ADD_FILE_BY <userId> <name> <size>`

Adds a file owned by a specific user.

### Rules

- The file is added to the same global filesystem used by `ADD_FILE`.
- File names are globally unique across all users.
- The operation fails if:
    - `userId` does not exist
    - a file with `name` already exists
    - adding the file would exceed the user's storage capacity
- If successful:
    - add the file
    - assign ownership to `userId`
    - return the user's remaining capacity as a string
- Otherwise:
    - return `""`

### Remaining capacity

```text
remaining capacity
= user capacity
- total size of files currently owned by the user
```

---

## `ADD_FILE <name> <size>`

Existing Level 1 behavior remains unchanged.

### Additional Rule

Every `ADD_FILE` call is performed by the special user:

```text
admin
```

`admin` has unlimited storage capacity.

Files created through `ADD_FILE` are therefore owned by `admin`.

---

## `MERGE_USER <userId1> <userId2>`

Merges `userId2` into `userId1`.

### Invalid Cases

Return `""` if:

- `userId1` does not exist
- `userId2` does not exist
- `userId1 === userId2`

Neither user will ever be `"admin"`.

### On Successful Merge

- Transfer ownership of every file owned by `userId2` to `userId1`.
- Add `userId2`'s remaining storage capacity to `userId1`.
- Delete `userId2`.
- Return `userId1`'s remaining capacity as a string.

### Capacity Behavior

The amount transferred is `userId2`'s **remaining capacity**.

Example:

```text
user1:
capacity = 200
files owned = 190
remaining = 10

user2:
capacity = 110
files owned = 50
remaining = 60
```

After:

```text
MERGE_USER("user1", "user2")
```

The resulting remaining capacity is:

```text
10 + 60 = 70
```

---

# Examples

```text
ADD_USER("user1", 200)
-> "true"

ADD_USER("user1", 100)
-> "false"
```

`user1` already exists, so the second operation fails.

```text
ADD_FILE_BY("user1", "/dir/file.med", 50)
-> "150"

ADD_FILE_BY("user1", "/big.blob", 140)
-> "10"

ADD_FILE_BY("user1", "/file-small", 20)
-> ""
```

The last operation fails because `user1` only has 10 bytes remaining.

```text
ADD_FILE("/dir/admin_file", 300)
-> "true"
```

This succeeds because `ADD_FILE` is performed by `admin`, who has unlimited capacity.

```text
ADD_USER("user2", 110)
-> "true"

ADD_FILE_BY("user2", "/dir/file.med", 45)
-> ""
```

This fails because `"/dir/file.med"` already exists in the global filesystem.

```text
ADD_FILE_BY("user2", "/new_file", 50)
-> "60"
```

Before merging:

```text
user1 remaining = 10
user2 remaining = 60
```

Then:

```text
MERGE_USER("user1", "user2")
-> "70"
```

After the merge:

- `"/new_file"` is owned by `user1`
- `user2` no longer exists
- `user1` has 70 bytes remaining

---

# Expected Output

```json
[
  "true",
  "false",
  "150",
  "10",
  "",
  "true",
  "true",
  "",
  "60",
  "70"
]
```

---

# Important Rules

- The filesystem is global.
- File names are globally unique across all users.
- Each non-admin user has a storage capacity limit.
- `admin` has unlimited storage capacity.
- `ADD_FILE` creates admin-owned files.
- `ADD_FILE_BY` creates files owned by the specified user.
- Merging users transfers file ownership.
- Merging users combines their remaining storage capacities.
- `userId2` is deleted after a successful merge.