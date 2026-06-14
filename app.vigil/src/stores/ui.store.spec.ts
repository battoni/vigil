import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import useUiStore from './ui.store';

describe('ui.store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('initialises with isSidenavOpen false', () => {
    const store = useUiStore();
    expect(store.isSidenavOpen).toBe(false);
  });

  it('toggleSidenav opens the sidenav when it is closed', () => {
    const store = useUiStore();
    store.toggleSidenav();
    expect(store.isSidenavOpen).toBe(true);
  });

  it('toggleSidenav closes the sidenav when it is open', () => {
    const store = useUiStore();
    store.toggleSidenav();
    store.toggleSidenav();
    expect(store.isSidenavOpen).toBe(false);
  });

  it('closeSidenav sets isSidenavOpen to false', () => {
    const store = useUiStore();
    store.toggleSidenav(); // open it first
    store.closeSidenav();
    expect(store.isSidenavOpen).toBe(false);
  });

  it('closeSidenav is a no-op when already closed', () => {
    const store = useUiStore();
    store.closeSidenav();
    expect(store.isSidenavOpen).toBe(false);
  });

  it('exposes appWidth and appHeight getters', () => {
    const store = useUiStore();
    expect(typeof store.appWidth).toBe('number');
    expect(typeof store.appHeight).toBe('number');
  });
});
