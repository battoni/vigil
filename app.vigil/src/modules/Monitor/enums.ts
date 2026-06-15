enum MONITOR_TYPE {
  HTTP = 'http',
  TCP = 'tcp',
  PING = 'ping',
  SSL = 'ssl',
  KEYWORD = 'keyword',
  HEARTBEAT = 'heartbeat',
  DNS = 'dns',
}

enum MONITOR_STATUS {
  UP = 'up',
  DOWN = 'down',
  PENDING = 'pending',
  PAUSED = 'paused',
  MAINTENANCE = 'maintenance',
}

export { MONITOR_STATUS, MONITOR_TYPE };
