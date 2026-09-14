# Level 2

Implement an operation for retrieving the largest files whose names begin with a given prefix.

## GET_N_LARGEST `<prefix>` `<n>`

Returns up to `n` files whose names start with `prefix`.

### Filtering
- Only include files where the file name starts with `prefix`.
- If no files match, return `""`.

### Sorting
Sort matching files by:

1. File size, descending.
2. If two files have the same size, file name lexicographically ascending.

### Limit
- Return at most `n` files.
- If fewer than `n` files match, return all matching files.

### Output format

Return:

```text
<name1>(<size1>), <name2>(<size2>), ...