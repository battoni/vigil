import { describe, expect, it } from 'vitest';
import { mountWithPlugins } from '@/test/mount';
import MMonitorCard from './MMonitorCard.vue';

const cardStub = {
  template:
    '<div class="card-stub"><slot name="header" /><slot name="content" /><slot name="footer" /></div>',
};
const tagStub = { props: ['severity', 'value'], template: '<span class="tag-stub" :data-severity="severity">{{ value }}</span>' };
const buttonStub = { emits: ['click'], template: '<button class="btn-stub" @click="$emit(\'click\', $event)"><slot /></button>' };

const baseMonitor = {
  id: '1',
  projectId: '10',
  name: 'Homepage',
  type: 'http',
  target: 'https://example.com',
  config: {},
  intervalSeconds: 60,
  timeoutMs: 10000,
  confirmationThreshold: 2,
  recoveryThreshold: 1,
  status: 'up',
  consecutiveFailures: 0,
  consecutiveSuccesses: 5,
  lastCheckedAt: '2026-06-15T10:00:00+00:00',
};

function mountCard(monitor: Record<string, unknown> = baseMonitor, props: Record<string, unknown> = {}) {
  return mountWithPlugins(MMonitorCard, {
    props: { monitor, ...props } as never,
    global: { stubs: { Card: cardStub, Tag: tagStub, Button: buttonStub, Divider: true } },
  });
}

describe('MMonitorCard', () => {
  it('renders the name and target', () => {
    const wrapper = mountCard();
    expect(wrapper.text()).toContain('Homepage');
    expect(wrapper.text()).toContain('https://example.com');
  });

  it('maps the up status to the success severity', () => {
    const wrapper = mountCard();
    expect(wrapper.find('.tag-stub').attributes('data-severity')).toBe('success');
  });

  it('maps the down status to the danger severity', () => {
    const wrapper = mountCard({ ...baseMonitor, status: 'down' });
    expect(wrapper.find('.tag-stub').attributes('data-severity')).toBe('danger');
  });

  it('shows the 24h uptime percentage when provided', () => {
    const wrapper = mountCard(baseMonitor, { uptime24h: 99.9 });
    expect(wrapper.text()).toContain('99.9%');
  });

  it('shows a dash when uptime is unknown', () => {
    const wrapper = mountCard();
    expect(wrapper.text()).toContain('—');
  });

  it('shows the heartbeat ping URL for heartbeat monitors', () => {
    const wrapper = mountCard({
      ...baseMonitor,
      type: 'heartbeat',
      heartbeatUrl: 'https://vigil.test/api/heartbeats/tok',
    });
    expect(wrapper.text()).toContain('https://vigil.test/api/heartbeats/tok');
  });

  it('emits onViewRequest when the view button is clicked', async () => {
    const wrapper = mountCard();
    await wrapper.findAll('.btn-stub')[0]?.trigger('click');
    expect(wrapper.emitted('onViewRequest')).toBeTruthy();
  });

  it('emits onEditRequest when the edit button is clicked', async () => {
    const wrapper = mountCard();
    await wrapper.findAll('.btn-stub')[1]?.trigger('click');
    expect(wrapper.emitted('onEditRequest')).toBeTruthy();
  });

  it('emits onPauseRequest when the pause button is clicked', async () => {
    const wrapper = mountCard();
    await wrapper.findAll('.btn-stub')[2]?.trigger('click');
    expect(wrapper.emitted('onPauseRequest')).toBeTruthy();
  });
});
