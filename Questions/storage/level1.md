# Level 1

Implement basic file storage operations.

## ADD_FILE `<name>` `<size>`

Adds a new file to storage.

### Rules
- `name` uniquely identifies the file.
- `size` is the file size in bytes.
- If a file with the same `name` already exists:
    - do not modify anything
    - return `"false"`
- Otherwise:
    - store the file and its size
    - return `"true"`

---

## GET_FILE_SIZE `<name>`

Returns the size of an existing file.

### Rules
- If the file exists:
    - return its size as a string
- If the file does not exist:
    - return `""`

---

## DELETE_FILE `<name>`

Deletes an existing file.

### Rules
- If the file exists:
    - remove it from storage
    - return its previous size as a string
- If the file does not exist:
    - return `""`

---

## Notes

- Treat the full file path as the unique file name.
- Different paths represent different files.
- No directory-specific behavior is required at this level.
- File sizes may be stored internally as integers, but returned values must be strings.

## Example

```text
ADD_FILE("/dir1/dir2/file.txt", 10)
-> "true"

ADD_FILE("/dir1/dir2/file.txt", 5)
-> "false"

GET_FILE_SIZE("/dir1/dir2/file.txt")
-> "10"

DELETE_FILE("/not-existing.file")
-> ""

DELETE_FILE("/dir1/dir2/file.txt")
-> "10"

GET_FILE_SIZE("/not-existing.file")
-> ""