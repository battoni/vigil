import { useWindowSize } from '@vueuse/core';
import { defineStore } from 'pinia';

interface UiState {
  isSidenavOpen: boolean;
}

const { height, width } = useWindowSize();

export default defineStore('ui', {
  state: (): UiState => ({
    isSidenavOpen: false,
  }),
  actions: {
    closeSidenav() {
      this.isSidenavOpen = false;
    },
    toggleSidenav() {
      this.isSidenavOpen = !this.isSidenavOpen;
    },
  },
  getters: {
    appHeight: () => height.value,
    appWidth: () => width.value,
  },
});
