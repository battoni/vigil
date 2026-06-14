---
title: Module file separation, enum naming, and service patterns for app.vigil (Vue 3 / TS)
outline: deep
---

::: info Generated page
This page is generated from `.claude/rules/celer-05-module-conventions.md` — **edit the source rule, not this page.** Activates for: `app.vigil/**/*.vue`, `app.vigil/**/*.ts`.
:::

# File Separation (CRITICAL)

Do **not** mix concerns in the same file.

- **enums.ts** – TypeScript enums (fixed sets of values, provide type + runtime)
- **constants.ts** – Static lists, const objects, literal values (e.g. option lists, config maps)
- **types.ts** – Types only: type aliases, type re-exports from enums
- **interfaces.ts** – Interfaces and payload types. **If an interface will be used outside the service, it must go in interfaces.ts** – not in the service file.

# Enums vs Constants

**Enums** go in `enums.ts`. Use when you need a fixed set of string/number values.

**Constants** go in `constants.ts`. Use for static data: option arrays, lookup maps, config objects.

# Enum Naming (CRITICAL)

Enums use **UPPER_SNAKE_CASE** to differentiate from interfaces (PascalCase).

❌ **NEVER:** PascalCase enums (looks like interface)

```typescript
enum FormFieldType { ... }
```

✅ **ALWAYS:** UPPER_SNAKE_CASE for enums

```typescript
enum FORM_FIELD_TYPE { ... }
enum FORM_OPTION_SOURCE_TYPE { ... }
```

# Service Pattern (CRITICAL)

Services use `export default async function` with a named function:

```typescript
// nameOfService.service.ts
export default async function nameOfService(
  id: number,
  payload: Payload
) {
  return someProvider(id, payload);
}
```

The barrel file re-exports with PascalCase:

```typescript
// services/index.ts
export { default as NameOfService } from './nameOfService.service';
```

# Single Export Block (CRITICAL)

Use one export block at the end. No inline `export const` or `export type`.

❌ **NEVER:**

```typescript
export const FOO = 'foo';
export type Bar = string;
```

✅ **ALWAYS:**

```typescript
const FOO = 'foo';
type Bar = string;

export { FOO };
export type { Bar };
```
