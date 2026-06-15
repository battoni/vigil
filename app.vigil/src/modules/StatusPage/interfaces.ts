interface StatusPageMonitor {
  id: string;
  name: string;
  status: string;
  groupName?: string | null;
  sortOrder?: number;
}

interface StatusPage {
  id: string;
  name: string;
  slug: string;
  customDomain: string | null;
  branding: Record<string, unknown>;
  isPublic: boolean;
  monitorsCount?: number;
  monitors?: StatusPageMonitor[];
  createdAt?: string;
  updatedAt?: string;
}

interface PublicStatusMonitor {
  name: string;
  status: string;
  uptime: Record<string, number | null>;
}

interface PublicStatusGroup {
  name: string;
  monitors: PublicStatusMonitor[];
}

interface PublicStatus {
  name: string;
  slug: string;
  branding: Record<string, unknown>;
  overallStatus: string;
  groups: PublicStatusGroup[];
}

export type { PublicStatus, PublicStatusGroup, PublicStatusMonitor, StatusPage, StatusPageMonitor };
