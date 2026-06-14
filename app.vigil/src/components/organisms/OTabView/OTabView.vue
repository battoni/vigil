<script setup lang="ts">
import { computed, Fragment, useSlots } from 'vue';
import type { VNode } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import type { RouteLocationRaw } from 'vue-router';

type OTabViewMode = 'routes' | 'view';

interface OTabDescriptor {
  actions?: () => VNode[];
  content?: () => VNode[];
  icon?: string;
  key: string;
  title: string;
  to?: RouteLocationRaw;
}

const props = withDefaults(
  defineProps<{
    mode?: OTabViewMode;
  }>(),
  {
    mode: 'view',
  }
);

const route = useRoute();
const router = useRouter();
const slots = useSlots();

const activeValue = defineModel<string>();

const isRoutesMode = computed(() => props.mode === 'routes');
const tabs = computed<OTabDescriptor[]>(() => extractTabs(slots.default?.() ?? []));

const activeTabKey = computed<string | number>({
  get: () => {
    if (!isRoutesMode.value) return activeValue.value ?? tabs.value[0]?.key ?? '';

    const activeRouteTab = tabs.value.find((tab) => isTabActive(tab));
    return activeRouteTab?.key ?? tabs.value[0]?.key ?? '';
  },
  set: (value) => {
    const nextValue = String(value);

    if (!isRoutesMode.value) {
      activeValue.value = nextValue;
      return;
    }

    const targetTab = tabs.value.find((tab) => tab.key === nextValue);
    if (targetTab?.to) router.push(targetTab.to);
  },
});

const activeTab = computed(() => tabs.value.find((tab) => tab.key === activeTabKey.value));

// HELPERS
function extractTabs(nodes: VNode[]): OTabDescriptor[] {
  return flattenPanels(nodes).map((vnode, index) => {
    const panelSlots = (vnode.children ?? {}) as Record<string, () => VNode[]>;
    const panelProps = (vnode.props ?? {}) as Record<string, unknown>;

    return {
      key: resolveTabKey(panelProps, index),
      actions: panelSlots.actions,
      content: panelSlots.default,
      icon: panelProps.icon as string | undefined,
      title: panelProps.title as string,
      to: panelProps.to as RouteLocationRaw | undefined,
    };
  });
}

function flattenPanels(nodes: VNode[]): VNode[] {
  const flattened = nodes.flatMap((node) => (node.type === Fragment ? (node.children as VNode[]) : node));

  return flattened.filter((node) => isTabPanel(node));
}

function isTabActive(tab: OTabDescriptor): boolean {
  if (!tab.to) return false;

  return router.resolve(tab.to).name === route.name;
}

function isTabPanel(node: VNode): boolean {
  const type = node.type as { __name?: string; name?: string } | null;

  return type?.__name === 'OTabPanel' || type?.name === 'OTabPanel';
}

function resolveTabKey(panelProps: Record<string, unknown>, index: number): string {
  if (panelProps.value) return String(panelProps.value);

  if (panelProps.to) return String(router.resolve(panelProps.to as RouteLocationRaw).name ?? index);

  return String(index);
}
</script>

<template>
  <div class="flex flex-col gap-4">
    <Tabs v-model:value="activeTabKey">
      <TabList class="border-none bg-transparent shadow-none">
        <Tab
          v-for="tab in tabs"
          class="relative cursor-pointer text-xl"
          :key="tab.key"
          :value="tab.key"
        >
          <div class="flex items-center gap-2">
            <i
              v-if="tab.icon"
              :class="tab.icon"
            />

            <span>{{ $t(tab.title) }}</span>

            <span
              v-if="tab.actions"
              @click.stop
            >
              <component :is="tab.actions" />
            </span>
          </div>
        </Tab>
      </TabList>
    </Tabs>

    <RouterView v-if="isRoutesMode" />

    <component
      v-else-if="activeTab?.content"
      :is="activeTab.content"
    />
  </div>
</template>
