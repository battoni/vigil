---
version: 1.1.0
origin: vigil
based-on: 1.0.0
---

# RULES — FinanceView

> For AI agents. Last updated: 2026-05-22.

## Purpose

Finance dashboard showcasing wallet metric cards, a transaction table, and a deposit/withdraw action dialog.

## Intentional Decisions

- **Transaction data is seeded in `buildInitialTransactions()`**: all transaction data is local state, not fetched from an API. This is a prototype/demo view. Do not add API calls here until the backend endpoint exists.
- **Wallet card values are hardcoded strings** (`'R$ 100.000'`, etc.): demo data, not computed from real transactions.
- **Action dialog form is marked as "TEMPORARY FOR DEMONSTRATION PURPOSES"**: the `<form>` inside `MMainDialog` is a prototype form. Do not treat it as production code.
- **The action form has a fixed footer with `position: fixed`**: the dialog's native sticky footer is bypassed because the demo form has too many fields for a standard footer. `pb-[calc(4rem+env(safe-area-inset-bottom,0))]` on the form body adds safe-area-aware bottom padding.
- **`transactionToneByAction`** maps `'deposit'` → `'success'` and `'withdraw'` → `'warn'` for type tone.
- **`formatAmountToCurrency` normalizes Brazilian currency input**: handles both `'1.000'` (BR) and `'1000'` formats before passing to `Intl.NumberFormat`.
- **The `n in 15` loop** in the action form template: this is intentional demo padding to show the dialog's scroll behavior with long content. Remove when implementing the real form.

## Do Not

- **Do not add API calls** until the finance backend is implemented — all data is local state.
- **Do not remove the `n in 15` demo loop** without replacing it with real form fields.
