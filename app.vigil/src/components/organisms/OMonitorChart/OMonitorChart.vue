<script setup lang="ts">
import { computed } from 'vue';
import type { MonitorSeriesPoint } from '@MonitorModule';

const props = withDefaults(
  defineProps<{
    series: MonitorSeriesPoint[];
    variant?: 'latency' | 'uptime';
  }>(),
  {
    variant: 'latency',
  }
);

const chartData = computed(() => (props.variant === 'uptime' ? uptimeData() : latencyData()));

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: props.variant === 'latency' },
  },
  scales: {
    y: {
      beginAtZero: true,
      max: props.variant === 'uptime' ? 100 : undefined,
    },
  },
}));

// HELPERS
function labels(): string[] {
  return props.series.map((point) => point.bucketStart);
}

// Canvas can't consume Tailwind classes, so read the design-system color tokens
// at runtime (falling back to the token hex values when no DOM is present).
function token(name: string, fallback: string): string {
  if (typeof document === 'undefined') {
    return fallback;
  }

  return getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;
}

function latencyData() {
  return {
    labels: labels(),
    datasets: [
      { label: 'p50 (ms)', data: props.series.map((point) => point.p50Ms), borderColor: token('--color-primary-500', '#6366f1'), tension: 0.3 },
      { label: 'p95 (ms)', data: props.series.map((point) => point.p95Ms), borderColor: token('--color-warn-500', '#f59e0b'), tension: 0.3 },
    ],
  };
}

function uptimeData() {
  return {
    labels: labels(),
    datasets: [
      {
        label: 'uptime %',
        data: props.series.map((point) => Number((point.uptimeRatio * 100).toFixed(2))),
        borderColor: token('--color-success-500', '#22c55e'),
        backgroundColor: token('--color-success-100', 'rgba(34, 197, 94, 0.15)'),
        fill: true,
        tension: 0.3,
      },
    ],
  };
}
</script>

<template>
  <div class="h-64 w-full">
    <Chart
      class="h-full w-full"
      type="line"
      :data="chartData"
      :options="chartOptions"
    />
  </div>
</template>
