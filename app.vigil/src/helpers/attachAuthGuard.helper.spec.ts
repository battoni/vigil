import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import attachAuthGuard from './attachAuthGuard.helper';

// Mock the user store so the guard can be exercised without a real session.
const fetchMe = vi.fn();
const userStoreState: { user: unknown; hasSessionHint: boolean } = { user: null, hasSessionHint: true };

const { cancelAllRequests, createNewController } = vi.hoisted(() => ({
  cancelAllRequests: vi.fn(),
  createNewController: vi.fn(),
}));

vi.mock('@UserModule', () => ({
  useUserStore: () => ({
    get user() {
      return userStoreState.user;
    },
    fetchMe,
    hasSessionHint: () => userStoreState.hasSessionHint,
  }),
}));

vi.mock('@Composables', () => ({
  useGlobalAbortController: () => ({ cancelAllRequests, createNewController }),
}));

type Guard = (
  to: { name?: string; meta: Record<string, unknown> },
  from: { name?: string },
  next: ReturnType<typeof vi.fn>
) => unknown;

const LOGIN_REDIRECT = { path: '/entrar' };

function makeRouterCapture() {
  let guard: Guard | undefined;
  const router = {
    beforeEach: (fn: Guard) => {
      guard = fn;
    },
  };
  attachAuthGuard(router as never);
  if (!guard) throw new Error('guard was not registered');
  return guard;
}

describe('attachAuthGuard', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    fetchMe.mockReset();
    cancelAllRequests.mockClear();
    createNewController.mockClear();
    userStoreState.user = null;
    userStoreState.hasSessionHint = true;
  });

  it('allows a public route through without checking the user', () => {
    const guard = makeRouterCapture();
    const next = vi.fn();

    guard({ name: 'login', meta: { isPublic: true } }, { name: 'home' }, next);

    expect(next).toHaveBeenCalledWith();
    expect(fetchMe).not.toHaveBeenCalled();
  });

  it('redirects an authenticated user away from a public route to home', () => {
    userStoreState.user = { id: 1 };
    const guard = makeRouterCapture();
    const next = vi.fn();

    guard({ name: 'login', meta: { isPublic: true } }, { name: 'users' }, next);

    expect(next).toHaveBeenCalledWith({ name: 'home' });
  });

  it('allows a protected route when a user is already loaded', () => {
    userStoreState.user = { id: 1 };
    const guard = makeRouterCapture();
    const next = vi.fn();

    guard({ name: 'users', meta: {} }, { name: 'home' }, next);

    expect(next).toHaveBeenCalledWith();
    expect(fetchMe).not.toHaveBeenCalled();
  });

  it('fetches the user on a protected route, then proceeds on success', async () => {
    fetchMe.mockResolvedValue(undefined);
    const guard = makeRouterCapture();
    const next = vi.fn();

    await guard({ name: 'users', meta: {} }, { name: 'home' }, next);

    expect(fetchMe).toHaveBeenCalledOnce();
    expect(next).toHaveBeenCalledWith();
  });

  it('redirects to login when fetching the user fails', async () => {
    fetchMe.mockRejectedValue(new Error('401'));
    const guard = makeRouterCapture();
    const next = vi.fn();

    await guard({ name: 'users', meta: {} }, { name: 'home' }, next);

    expect(next).toHaveBeenCalledWith(LOGIN_REDIRECT);
  });

  it('skips the fetch and redirects to login when no session hint exists', () => {
    userStoreState.hasSessionHint = false;
    const guard = makeRouterCapture();
    const next = vi.fn();

    guard({ name: 'users', meta: {} }, { name: 'home' }, next);

    expect(fetchMe).not.toHaveBeenCalled();
    expect(next).toHaveBeenCalledWith(LOGIN_REDIRECT);
  });

  it('cancels in-flight requests when navigating to a different route', () => {
    const guard = makeRouterCapture();
    const next = vi.fn();

    guard({ name: 'login', meta: { isPublic: true } }, { name: 'home' }, next);

    expect(cancelAllRequests).toHaveBeenCalledOnce();
    expect(createNewController).toHaveBeenCalledOnce();
  });

  it('does not cancel requests when navigating within the same route', () => {
    const guard = makeRouterCapture();
    const next = vi.fn();

    guard({ name: 'login', meta: { isPublic: true } }, { name: 'login' }, next);

    expect(cancelAllRequests).not.toHaveBeenCalled();
  });
});
