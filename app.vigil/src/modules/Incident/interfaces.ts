interface Incident {
  id: string;
  monitorId: string;
  monitorName?: string;
  startedAt: string;
  resolvedAt: string | null;
  isOpen: boolean;
  cause: string | null;
  durationSeconds: number | null;
  acknowledgedBy?: string | null;
  acknowledgedAt?: string | null;
  createdAt?: string;
}

export type { Incident };
