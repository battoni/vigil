---
version: 1.0.0
origin: vigil
based-on: 1.0.0
---

# RULES — Home Module

> For AI agents. Last updated: 2026-05-22.

## Domain

Dashboard and marketing views for authenticated users. Currently contains the home placeholder and the finance dashboard prototype.

## What belongs here

- Top-level authenticated views that don't belong to a specific domain module
- Finance dashboard (prototype)

## What does NOT belong here

- Business logic for financial data — when a real finance backend exists, extract to a dedicated `Finance` module
- User or auth features

## Public Barrel

Exports only `views` (route configs). No shared components, stores, or services — all data in this module is currently local state.

## Current Views & Status

| View          | Status      | Notes                                  |
| ------------- | ----------- | -------------------------------------- |
| `HomeView`    | Placeholder | No content yet; pending product design |
| `FinanceView` | Prototype   | All data is local state — no API calls |

## Intentional Decisions

- **No store**: Home views manage local state only. If persistent state is needed, add `store.ts` at that point.
- **No services**: Finance data is seeded locally until the backend is implemented.
- **No module alias configured yet**: Home is not yet exposed as `@HomeModule` — import route configs directly from the barrel if needed.

## When to extract a new module from Home

Extract `Finance` as its own module when it has: a real API, its own entity types, and more than one view. Do not pre-emptively extract.

## Do Not

- **Do not add API calls to `FinanceView`** until a real finance backend exists — keep it prototype until then.
- **Do not add business features to `HomeView`** until the product home screen is designed.
