import { defineStore, getActivePinia } from 'pinia';

export default function () {
  const activepinia = getActivePinia();

  if (!activepinia) return;

  Object.entries(activepinia.state.value).forEach(([storeName, state]) => {
    if (['session', 'ui'].includes(storeName)) return;

    const storeDefinition = defineStore(storeName, state);
    const store = storeDefinition(activepinia);

    store.$reset();
  });
}
