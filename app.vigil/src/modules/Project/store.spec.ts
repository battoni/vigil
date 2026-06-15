import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const getProjectsService = vi.fn();

vi.mock('./services', () => ({
  GetProjectsService: getProjectsService,
}));

// Import after mocks are registered
const { useProjectStore } = await import('./store');

const projectA = { id: '1', name: 'Acme', slug: 'acme' };
const projectB = { id: '2', name: 'Globex', slug: 'globex' };

describe('useProjectStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    getProjectsService.mockReset();
    localStorage.clear();
  });

  it('initialises empty with no active project', () => {
    const store = useProjectStore();
    expect(store.projects).toEqual([]);
    expect(store.activeProjectId).toBeNull();
  });

  it('fetchProjects hydrates and selects the first project as active', async () => {
    getProjectsService.mockResolvedValue({ data: [projectA, projectB] });
    const store = useProjectStore();

    await store.fetchProjects();

    expect(store.projects).toHaveLength(2);
    expect(store.activeProjectId).toBe('1');
    expect(store.activeProject).toEqual(projectA);
  });

  it('keeps a persisted active project when it still exists', async () => {
    localStorage.setItem('app.vigil:active-project', '2');
    getProjectsService.mockResolvedValue({ data: [projectA, projectB] });
    const store = useProjectStore();

    await store.fetchProjects();

    expect(store.activeProjectId).toBe('2');
  });

  it('setActiveProject persists the selection', () => {
    const store = useProjectStore();
    store.setActiveProject('2');
    expect(localStorage.getItem('app.vigil:active-project')).toBe('2');
  });

  it('addProject prepends granularly', () => {
    const store = useProjectStore();
    store.addProject(projectA);
    store.addProject(projectB);
    expect(store.projects.map((project) => project.id)).toEqual(['2', '1']);
  });

  it('replaceProject swaps by id', () => {
    const store = useProjectStore();
    store.addProject(projectA);
    store.replaceProject({ ...projectA, name: 'Acme Renamed' });
    expect(store.activeProject?.name).toBe('Acme Renamed');
  });

  it('removeProject drops it and reassigns the active project', () => {
    const store = useProjectStore();
    store.addProject(projectB);
    store.addProject(projectA);
    store.setActiveProject('1');

    store.removeProject('1');

    expect(store.projects).toHaveLength(1);
    expect(store.activeProjectId).toBe('2');
  });
});
