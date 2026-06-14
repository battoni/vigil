import PrimeVue from 'primevue/config';
import themePreset from './theme';

const options = {
  theme: {
    preset: themePreset,
    options: {
      prefix: 'p',
      darkModeSelector: '[data-theme="dark"]',
      cssLayer: {
        name: 'primevue',
        order: 'theme, base, primevue',
      },
    },
  },
};

const PrimeVuePlugin = {
  package: PrimeVue,
  options,
};

export default PrimeVuePlugin;
