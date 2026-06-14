interface Permission {
  labelKey: string;
  key: string;
  value: boolean;
}

interface PermissionGroup {
  id: string;
  nameKey: string;
  icon: string;
  permissions: Permission[];
}

interface Profile {
  description?: string;
  id: string;
  name: string;
  slug?: string;
  permissionGroups: PermissionGroup[];
  users: string[];
}

export type { Permission, PermissionGroup, Profile };
