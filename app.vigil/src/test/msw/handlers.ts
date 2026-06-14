import { http, HttpResponse } from 'msw';

const BASE = 'http://localhost';

// Default fixtures — override per-test with server.use(http.get(...))
export const mockUser = {
  id: 1,
  name: 'Alice',
  last_name: 'Smith',
  username: 'alice',
  role: 'Admin',
  role_slug: 'administrator',
  status: 'active',
  permissions: ['users.read', 'users.create', 'users.update', 'users.archive', 'users.delete'],
};

export const mockRole = {
  id: 'role-1',
  name: 'Admin',
  slug: 'admin',
  description: 'Administrator role',
  users: [],
  permissionGroups: [
    {
      id: 'group-users',
      nameKey: 'permissions.users',
      icon: 'pi pi-user',
      permissions: [
        { key: 'users.read', labelKey: 'permissions.users.read', value: true },
        { key: 'users.create', labelKey: 'permissions.users.create', value: false },
      ],
    },
  ],
};

export const handlers = [
  // ---- Auth ----
  http.post(`${BASE}/auth/login`, () => HttpResponse.json({ data: mockUser })),

  http.get(`${BASE}/auth/me`, () => HttpResponse.json({ data: mockUser })),

  http.post(`${BASE}/auth/logout`, () => HttpResponse.json({})),

  // ---- Users ----
  http.get(`${BASE}/auth/users`, () => HttpResponse.json({ data: [mockUser] })),

  http.get(`${BASE}/auth/users/:id`, ({ params }) =>
    HttpResponse.json({ data: { ...mockUser, id: Number(params.id) } })
  ),

  http.post(`${BASE}/auth/users`, async ({ request }) => {
    const body = (await request.json()) as Record<string, unknown>;
    return HttpResponse.json({ data: { ...mockUser, id: 99, ...body } }, { status: 201 });
  }),

  http.patch(`${BASE}/auth/users/:id`, async ({ params, request }) => {
    const body = (await request.json()) as Record<string, unknown>;
    return HttpResponse.json({ data: { ...mockUser, id: Number(params.id), ...body } });
  }),

  http.patch(`${BASE}/auth/users/:id/archive`, ({ params }) =>
    HttpResponse.json({
      data: { ...mockUser, id: Number(params.id), status: 'inactive' },
    })
  ),

  http.delete(`${BASE}/auth/users/:id`, ({ params }) =>
    HttpResponse.json({ data: { ...mockUser, id: Number(params.id) } })
  ),

  http.get(`${BASE}/auth/users/check-username`, () => HttpResponse.json({ data: { available: true } })),

  // ---- Roles ----
  http.get(`${BASE}/auth/roles`, () => HttpResponse.json({ data: [mockRole] })),

  http.get(`${BASE}/auth/roles/:id`, ({ params }) => HttpResponse.json({ data: { ...mockRole, id: params.id } })),

  http.patch(`${BASE}/auth/roles/:id/permissions`, async ({ params, request }) => {
    const permissions = (await request.json()) as Record<string, boolean>;
    const updatedGroups = mockRole.permissionGroups.map((group) => ({
      ...group,
      permissions: group.permissions.map((perm) => ({
        ...perm,
        value: permissions[perm.key] ?? perm.value,
      })),
    }));
    return HttpResponse.json({ data: { ...mockRole, id: params.id, permissionGroups: updatedGroups } });
  }),

  // ---- Permission groups ----
  http.get(`${BASE}/auth/permission-groups`, () => HttpResponse.json({ data: mockRole.permissionGroups })),
];
