import type { MonitorStatus, MonitorType } from './types';
import { MONITOR_STATUS, MONITOR_TYPE } from './enums';

interface MonitorTypeOption {
  value: MonitorType;
  labelKey: string;
}

const MONITOR_TYPE_OPTIONS: MonitorTypeOption[] = [
  { value: MONITOR_TYPE.HTTP, labelKey: 'monitors.type.http' },
  { value: MONITOR_TYPE.TCP, labelKey: 'monitors.type.tcp' },
  { value: MONITOR_TYPE.SSL, labelKey: 'monitors.type.ssl' },
  { value: MONITOR_TYPE.KEYWORD, labelKey: 'monitors.type.keyword' },
  { value: MONITOR_TYPE.HEARTBEAT, labelKey: 'monitors.type.heartbeat' },
  { value: MONITOR_TYPE.PING, labelKey: 'monitors.type.ping' },
  { value: MONITOR_TYPE.DNS, labelKey: 'monitors.type.dns' },
];

// Maps a monitor status to a PrimeVue severity / state-token tone.
const MONITOR_STATUS_SEVERITY: Record<MonitorStatus, string> = {
  [MONITOR_STATUS.UP]: 'success',
  [MONITOR_STATUS.DOWN]: 'danger',
  [MONITOR_STATUS.PENDING]: 'info',
  [MONITOR_STATUS.PAUSED]: 'secondary',
  [MONITOR_STATUS.MAINTENANCE]: 'warn',
};

const MONITOR_INTERVAL_OPTIONS = [60, 120, 300, 600, 1800, 3600];

export { MONITOR_INTERVAL_OPTIONS, MONITOR_STATUS_SEVERITY, MONITOR_TYPE_OPTIONS };
