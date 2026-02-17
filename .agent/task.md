# Task: Add Wallet Payment Option to Payment Report

## Status
- [x] Analyze `moodle_local_smartdashboard` plugin structure.
- [x] Analyze provided `wallet` plugin structure and database schema.
- [x] Identify relevant tables (`enrol_wallet_transactions`) and transaction types (`debit`, `enrol_instance`).
- [x] Modify `classes/external/analytics.php` in `moodle_local_smartdashboard` to:
    - [x] Include `enrol = 'wallet'` in the initial enrol instance query.
    - [x] Add logic to fetch wallet transactions from `enrol_wallet_transactions` table.
    - [x] Map wallet transactions to the payment analytics data structure.
    - [x] Include "Wallet" in the list of available gateways.

## Files Modified
- `classes/external/analytics.php`: Updated `get_payment_analytics` method.

## Notes
- The wallet plugin uses `enrol_wallet_transactions` to record payments.
- We filter for `type = 'debit'` and `opby = 'enrol_instance'`.
- The currency is retrieved from the enrol instance configuration as the transaction table does not store it.
- "Wallet" is treated as a gateway for filtering purposes.
