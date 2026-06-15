import { describe, expect, it } from 'vitest';
import { mountWithPlugins } from '@/test/mount';
import OMonitorChart from './OMonitorChart.vue';

const chartStub = {
  name: 'Chart',
  props: ['type', 'data', 'options'],
  template: '<div class="chart-stub" :data-type="type" :data-datasets="data.datasets.length" :data-points="data.labels.length" />',
};

const series = [
  { bucketStart: '2026-06-15T09:00:00Z', uptimeRatio: 1, checksTotal: 60, p50Ms: 100, p95Ms: 180, maxMs: 200 },
  { bucketStart: '2026-06-15T10:00:00Z', uptimeRatio: 0.9, checksTotal: 60, p50Ms: 120, p95Ms: 220, maxMs: 400 },
];

function mountChart(variant: 'latency' | 'uptime') {
  return mountWithPlugins(OMonitorChart, {
    props: { series, variant } as never,
    global: { stubs: { Chart: chartStub } },
  });
}

describe('OMonitorChart', () => {
  it('renders a line chart', () => {
    expect(mountChart('latency').find('.chart-stub').attributes('data-type')).toBe('line');
  });

  it('builds two datasets (p50 + p95) for the latency variant', () => {
    expect(mountChart('latency').find('.chart-stub').attributes('data-datasets')).toBe('2');
  });

  it('builds a single dataset for the uptime variant', () => {
    expect(mountChart('uptime').find('.chart-stub').attributes('data-datasets')).toBe('1');
  });

  it('plots a point per series entry', () => {
    expect(mountChart('latency').find('.chart-stub').attributes('data-points')).toBe('2');
  });
});
