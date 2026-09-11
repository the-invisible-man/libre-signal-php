## Level 4

The banking system should support merging two accounts while retaining both accounts’ balance and transaction histories.

*   `mergeAccounts(int $timestamp, string $accountId1, string $accountId2): bool` — should merge `$accountId2` into the `$accountId1`. Returns `true` if accounts were successfully merged, or `false` otherwise. Specifically:
    *   Returns `false` if `$accountId1` is equal to `$accountId2`.
    *   Returns `false` if `$accountId1` or `$accountId2` doesn’t exist.
    *   All pending cashback refunds for `$accountId2` should still be processed, but refunded to `$accountId1` instead.
    *   After the merge, it must be possible to check the status of payment transactions for `$accountId2` with payment identifiers by replacing `$accountId2` with `$accountId1`.
    *   The balance of `$accountId2` should be added to the balance of `$accountId1`.
    *   `topSpenders` operations should recognize merged accounts – the total outgoing transactions for merged accounts should be the sum of all money transferred and/or withdrawn in both accounts.
    *   `$accountId2` should be removed from the system after the merge.
*   `getBalance(int $timestamp, string $accountId, int $timeAt): ?int` — should return the total amount of money in the account `$accountId` at the given timestamp `$timeAt`. If the specified account did not exist at a given time `$timeAt`, returns `null`.
    *   If queries have been processed at timestamp `$timeAt`, `get_balance` must reflect the account balance **after** the query has been processed.
    *   If the account was merged into another account, the merged account should inherit its balance history.

## Examples

The examples below show how these operations should work:

| Queries | Explanations |
| --- | --- |
| createAccount(1, "account1") | returns true |
| createAccount(2, "account2") | returns true |
| deposit(3, "account1", 2000) | returns 2000 |
| deposit(4, "account2", 2000) | returns 2000 |
| pay(5, "account1", 500) | returns "payment1" |
| transfer(6, "account1", "account2", 500) | returns 1500 |
| mergeAccounts(7, "account1", "non-existing") | returns false; account "non-existing" does not exist |
| mergeAccounts(8, "account1", "account1") | returns false; account "account1" cannot be merged into itself |
| mergeAccounts(9, "account1", "account2") | returns true |
| getBalance(10, "account1", 10) | returns 3000 |
| getBalance(11, "account2", 10) | returns null; account "account2" doesn’t exist anymore |
| getPaymentStatus(12, "account1", "payment1") | returns "IN\_PROGRESS" |
| getPaymentStatus(13, "account2", "payment1") | returns null; "account2" doesn’t exist anymore |
| getBalance(14, "account2", 1) | returns null; "account2" was not created yet |
| getBalance(15, "account2", 9) | returns null; "account2" was already merged and doesn’t exist |
| getBalance(16, "account1", 11) | returns 3000 |
| deposit(5 + MILLISECONDS\_IN\_1\_DAY, "account1", 100) | returns 3906 |

### Another example:

| Queries | Explanations |
| --- | --- |
| createAccount(1, "account1") | returns true |
| deposit(2, "account1", 1000) | returns 1000 |
| pay(3, "account1", 300) | returns "payment1" |
| getBalance(4, "account1", 3) | returns 700 |
| getBalance(5 + MILLISECONDS\_IN\_1\_DAY, "account1", 2 + MILLISECONDS\_IN\_1\_DAY) | returns 700 |
| getBalance(6 + MILLISECONDS\_IN\_1\_DAY, "account1", 3 + MILLISECONDS\_IN\_1\_DAY) | returns 706; cashback for "payment1" was refunded |

## Test
You can execute the test cases for this level by running the following command in the terminal: `vendor/bin/phpunit --testsuite bank_system --group level4` from the project root directory.
