## Level 2

The bank wants to identify people who are not keeping money in their accounts, so implement operations to support ranking accounts based on outgoing transactions.


*   `topSpenders(int $timestamp, int $n): array` — should return the identifiers of the top `n` accounts with the highest outgoing transactions - the total amount of money either transferred out of or paid/withdrawn (the `pay` operation will be introduced in level 3) - sorted in descending order, or in case of a tie, sorted alphabetically by `$accountId` in ascending order. The result should be an array of strings in the following format: `["<account_id_1>(<total_outgoing_1>)", "<account_id_2>(<total_outgoing_2>)", ..., "<account_id_n>(<total_outgoing_n>)"]`.
    *   If less than `n` accounts exist in the system, then return all their identifiers (in the described format).
    *   Cashback (an operation that will be introduced in level 3) should not be reflected in the calculations for total outgoing transactions.

## Examples

The example below shows how these operations should work:

| Queries | Explanations |
| --- | --- |
| createAccount(1, "account3") | returns true |
| createAccount(2, "account2") | returns true |
| createAccount(3, "account1") | returns true |
| deposit(4, "account3", 2000) | returns 2000 |
| deposit(5, "account2", 3000) | returns 3000 |
| deposit(6, "account3", 4000) | returns 6000 |
| topSpenders(7, 3) | returns `['account1(0)', 'account2(0)', 'account3(0)']`; none of the accounts have any outgoing transactions, so they are sorted alphabetically |
| transfer(8, "account3", "account2", 500) | returns 5500 |
| transfer(9, "account3", "account1", 1000) | returns 4500 |
| transfer(10, "account1", "account2", 2500) | returns 500 |
| topSpenders(11, 3) | returns `['account1(2500)', 'account3(1500)', 'account2(0)']` |

## Test
You can execute the test cases for this level by running the following command in the terminal: `vendor/bin/phpunit --testsuite bank_system --group level2` from the project root directory.
*   **\[execution time limit\]** 3 seconds
*   **\[memory limit\]** 1 GB
