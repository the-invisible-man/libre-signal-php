## Level 3

The banking system should allow scheduling payments with some cashback and checking the status of scheduled payments.

*   `pay(int $timestamp, string $accountId, int $amount): ?string` — should withdraw the given amount of money from the specified account. All withdraw transactions provide a 2% cashback – 2% of the withdrawn amount (rounded down to the nearest integer) will be refunded to the account 24 hours after the withdrawal. If the withdrawal is successful (i.e., the account holds sufficient funds to withdraw the given amount), returns a string with a unique identifier for the payment transaction in this format: `"payment[ordinal number of withdraws from all accounts]"` — e.g., `"payment1"`, `"payment2"`, etc. Additional conditions:
    *   Returns `null` if `$accountId` doesn’t exist.
    *   Returns `null` if `$accountId` has insufficient funds to perform the payment.
    *   `topSpenders` should now also account for the total amount of money withdrawn from accounts.
    *   The waiting period for cashback is 24 hours, equal to `24 * 60 * 60 * 1000 = 86400000` milliseconds (the unit for timestamps). So, cashback will be processed at timestamp `timestamp + 86400000`.
    *   When it's time to process cashback for a withdrawal, the amount must be refunded to the account before any other transactions are performed at the relevant timestamp.
*   `getPaymentStatus(int $timestamp, string $accountId, string $payment): ?string` — should return the status of the payment transaction for the given `payment`. Specifically:
    *   Returns `null` if `$accountId` doesn’t exist.
    *   Returns `null` if the given `payment` doesn’t exist for the specified account.
    *   Returns `null` if the payment transaction was for an account with a different identifier from `$accountId`.
    *   Returns a string representing the payment status: `"IN_PROGRESS"` or `"CASHBACK_RECEIVED"`.

## Examples

The example below shows how these operations should work:

| Queries | Explanations |
| --- | --- |
| createAccount(1, "account1") | returns true |
| createAccount(2, "account2") | returns true |
| deposit(3, "account1", 2000) | returns 2000 |
| pay(4, "account1", 1000) | returns "payment1" |
| pay(100, "account1", 1000) | returns "payment2" |
| getPaymentStatus(101, "non-existing", "payment1") | returns null; this account does not exist |
| getPaymentStatus(102, "account2", "payment1") | returns null; this payment was from another account |
| getPaymentStatus(103, "account1", "payment1") | returns "IN\_PROGRESS" |
| topSpenders(104, 2) | returns `['account1(2000)', 'account2(0)']` |
| deposit(3 + MILLISECONDS\_IN\_1\_DAY, "account1", 100) | returns 100; cashback for "payment1" was not refunded yet |
| getPaymentStatus(4 + MILLISECONDS\_IN\_1\_DAY, "account1", "payment1") | returns "CASHBACK\_RECEIVED" |
| deposit(5 + MILLISECONDS\_IN\_1\_DAY, "account1", 100) | returns 220; cashback of `20` from "payment1" was refunded |
| deposit(99 + MILLISECONDS\_IN\_1\_DAY, "account1", 100) | returns 320; cashback for "payment2" was not refunded yet |
| deposit(100 + MILLISECONDS\_IN\_1\_DAY, "account1", 100) | returns 440; cashback of `20` from "payment2" was refunded |

## Test
You can execute the test cases for this level by running the following command in the terminal: `vendor/bin/phpunit --testsuite bank_system --group level3` from the project root directory.

*   **\[execution time limit\]** 3 seconds
*   **\[memory limit\]** 1 GB
