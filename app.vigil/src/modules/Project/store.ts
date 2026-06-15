import { defineStore } from 'pinia';
import type { Project } from './interfaces';
import { GetProjectsService } from './services';

interface ProjectState {
  projects: Project[];
  activeProjectId: string | null;
}

const ACTIVE_PROJECT_KEY = 'app.vigil:active-project';

function persistActiveProject(id: string | null): void {
  if (id) {
    localStorage.setItem(ACTIVE_PROJECT_KEY, id);
    return;
  }

  localStorage.removeItem(ACTIVE_PROJECT_KEY);
}

export const useProjectStore = defineStore('project', {
  state: (): ProjectState => ({
    projects: [],
    activeProjectId: localStorage.getItem(ACTIVE_PROJECT_KEY),
  }),
  getters: {
    activeProject: (state): Project | null =>
      state.projects.find((project) => project.id === state.activeProjectId) ?? null,
  },
  actions: {
    fetchProjects(): Promise<void> {
      return GetProjectsService().then(({ data }) => {
        this.projects = data;
        this.ensureActiveProject();
      });
    },
    setActiveProject(id: string | null): void {
      this.activeProjectId = id;
      persistActiveProject(id);
    },
    ensureActiveProject(): void {
      const hasActive = this.projects.some((project) => project.id === this.activeProjectId);

      if (hasActive) {
        return;
      }

      const [firstProject] = this.projects;

      if (!firstProject) {
        return;
      }

      this.setActiveProject(firstProject.id);
    },
    addProject(project: Project): void {
      this.projects = [project, ...this.projects];
      this.ensureActiveProject();
    },
    replaceProject(project: Project): void {
      this.projects = this.projects.map((existing) => (existing.id === project.id ? project : existing));
    },
    removeProject(id: string): void {
      this.projects = this.projects.filter((project) => project.id !== id);

      if (this.activeProjectId === id) {
        this.setActiveProject(this.projects[0]?.id ?? null);
      }
    },
  },
});
