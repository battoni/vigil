import type { MONITOR_STATUS, MONITOR_TYPE } from './enums';

type MonitorType = `${MONITOR_TYPE}`;
type MonitorStatus = `${MONITOR_STATUS}`;
type UptimeRange = '24h' | '7d' | '30d' | '90d';

export type { MonitorStatus, MonitorType, UptimeRange };
