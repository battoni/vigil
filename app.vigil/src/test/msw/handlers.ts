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

export const mockProject = {
  id: '1',
  name: 'System',
  slug: 'system',
  monitorsCount: 1,
};

export const mockMonitor = {
  id: '1',
  projectId: '1',
  name: 'Homepage',
  type: 'http',
  target: 'https://example.com',
  config: {},
  intervalSeconds: 60,
  timeoutMs: 10000,
  confirmationThreshold: 2,
  recoveryThreshold: 1,
  status: 'up',
  consecutiveFailures: 0,
  consecutiveSuccesses: 5,
  lastCheckedAt: '2026-06-15T10:00:00+00:00',
};

export const handlers = [
  // ---- Projects ----
  http.get(`${BASE}/projects`, () => HttpResponse.json({ data: [mockProject] })),

  // ---- Monitors ----
  http.get(`${BASE}/monitors`, () => HttpResponse.json({ data: [mockMonitor] })),

  http.get(`${BASE}/monitors/:id/uptime`, () =>
    HttpResponse.json({ data: { '24h': 99.9, '7d': 99, '30d': 98, '90d': 97 } })
  ),

  http.post(`${BASE}/monitors`, async ({ request }) => {
    const body = (await request.json()) as Record<string, unknown>;
    return HttpResponse.json({ data: { ...mockMonitor, id: '99', ...body } }, { status: 201 });
  }),

  http.patch(`${BASE}/monitors/:id`, async ({ params, request }) => {
    const body = (await request.json()) as Record<string, unknown>;
    return HttpResponse.json({ data: { ...mockMonitor, id: String(params.id), ...body } });
  }),

  http.delete(`${BASE}/monitors/:id`, ({ params }) =>
    HttpResponse.json({ data: { ...mockMonitor, id: String(params.id) } })
  ),

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
