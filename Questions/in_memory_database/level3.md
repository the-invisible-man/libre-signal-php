Level 3
-------

Support the timeline of operations and TTL (Time-To-Live) settings for records and fields. Each operation from previous levels now has an alternative version with a timestamp parameter to represent when the operation was executed. For each field-value pair in the database, the TTL determines how long that value will persist before being removed.

Notes:

* Time should always flow forward, so timestamps are guaranteed to strictly increase as operations are executed.
* Each test cannot contain both versions of operations (with and without timestamp). However, you should maintain backward compatibility, so all previously defined methods should work in the same way as before.
    * *My understanding of "backward compatibility" is that the methods without timestamp should work just as before, and the methods with timestamp should work as described in this level. So, `set()` function sets a field-value pair without any expiration; `get()` function retrieves all field-value pairs in the database without considering expiration since there's no timestamp for this operation; `scan()` and `scanByPrefix()` functions also retrieve all field-value pairs without considering expiration. The new methods in this level with timestamp will consider the expiration based on the provided timestamp and TTL. Definitely me know if you have a different understanding of "backward compatibility" in this context.*
* `setAt(key, field, value, timestamp)` — should insert a field-value pair or updates the value of the field in the record associated with key. This operation should return an empty string.
* `setAtWithTtl(key, field, value, timestamp, ttl)` — should insert a field-value pair or update the value of the field in the record associated with key. Also sets its Time-To-Live starting at timestamp to be ttl. The ttl is the amount of time that this field-value pair should exist in the database, meaning it will be available during this interval: \[timestamp, timestamp + ttl). This operation should return an empty string.
* `deleteAt(key, field, timestamp)` — the same as DELETE, but with timestamp of the operation specified. Should return "true" if the field existed and was successfully deleted and "false" if the key didn't exist.
    * *My understanding is that `deleteAt()` should only delete a field if it exists and is still alive at the given timestamp while `delete()` does not consider timestamps or expiry*
* `getAt(key, field, timestamp)` — the same as GET, but with timestamp of the operation specified.
* `scanAt(key, timestamp)` — the same as SCAN, but with timestamp of the operation specified.
* `scanByPrefixAt(key, prefix, timestamp)` — the same as SCAN\_BY\_PREFIX, but with timestamp of the operation specified.

### Examples

The examples below show how these operations should work:

| Queries | Explanations |
| --- | --- |
| `setAtWithTtl("A", "BC", "E", 1, 9)` | returns ""; database state: `{"A": {"BC": "E"}}` where `{"BC": "E"}` expires at timestamp 10 |
| `setAtWithTtl("A", "BC", "E", 5, 10)` | returns ""; database state: `{"A": {"BC": "E"}}` as field "BC" in record "A" already exists, it was overwritten, and `{"BC": "E"}` now expires at timestamp 15 |
| `setAt("A", "BD", "F", 6)` | returns ""; database state: `{"A": {"BC": E", "BD": "F"}}` where `{"BD": "F"}` does not expire |
| `scanByPrefixAt("A", "B", 14)` | returns "BC(E), BD(F)" |
| `scanByPrefixAt("A", "B", 15)` | returns "BD(F)" |

Another example could be:

| Queries | Explanations |
| --- | --- |
| `setAt("A", "B", "C", 1)` | returns ""; database state: `{"A": {"B": "C"}}` |
| `setAtWithTtl("X", "Y", "Z", 2, 15)` | returns ""; database state: `{"X": {"Y": "Z"}, "A": {"B": "C"}}` where `{"Y": "Z"}` expires at timestamp 17 |
| `getAt("X", "Y", 3)` | returns "Z" |
| `setAtWithTtl("A", "D", "E", 4, 10)` | returns ""; database state: `{"X": {"Y": "Z"}, "A": {"D": "E", "B": "C"}}` where `{"D": "E"}` expires at timestamp 14 and `{"Y": "Z"}` expires at timestamp 17 |
| `scanAt("A", 13)` | returns "B(C), D(E)" |
| `scanAt("X", 16)` | returns "Y(Z)" |
| `scanAt("X", 17)` | returns ""; Note that all fields in record "X" have expired |
| `deleteAt("X", "Y", 20)` | returns "false"; the record "X" was expired at timestamp 17 and can't be deleted. |

## Test
You can execute the test cases for this level by running the following command in the terminal: `vendor/bin/phpunit --testsuite in_memory_database --group level3` from the project root directory.
