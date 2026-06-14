---
version: 1.1.0
origin: vigil
based-on: 1.1.0
---
# RULES — App Helpers (ApiResponse)

> For AI agents. Shared, app-wide. Not module-scoped.

## ApiResponse — the response envelope contract

Every API response in api.vigil goes through `ApiResponse`. The envelope is fixed:

- **success**: `{ "success": true, "message": ?string, "data": mixed }`
- **error**:   `{ "success": false, "message": ?string, "errors": mixed }`

Note: success carries `data` (no `errors` key); error carries `errors` (no `data` key). The
two shapes differ — clients branch on `success`.

## Methods

- `success(data, message, status=200)` — base success.
- `created(data, message)` — success at 201. Use for store actions.
- `error(message, status=400, errors)` — base error.
- `notFound(message='Resource not found')` — error at 404. The house find-or-404 helper.
- `unauthorized(message='Unauthenticated')` — error at 401.

## Do Not

- Do not build raw `response()->json(...)` in controllers — always use `ApiResponse`.
- Do not add a `data` key to errors or an `errors` key to success — the asymmetry is the
  contract. Changing the envelope is a MAJOR change (breaks every client).
