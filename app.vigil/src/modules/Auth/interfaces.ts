import type { Permission, Profile } from './views/RolesAndPermissions/interfaces';

interface ConfirmPasswordResetPayload {
  code: string;
  password: string;
  passwordConfirmation: string;
  token: string;
}

interface ConfirmPasswordResetResponse {
  ok: boolean;
}

interface LoginPasswordCredentialsPayload {
  identifierMode: 'email' | 'phone';
  password: string;
  username: string;
}

interface LookupPhonePayload {
  phone: string;
}

interface LookupPhoneResponse {
  hasAccount: boolean;
}

interface PermissionGroup {
  id: string;
  nameKey: string;
  icon: string;
  permissions: PermissionGroupItem[];
}

interface PermissionGroupItem {
  key: string;
  labelKey: string;
  value: boolean;
}

interface RegisterUserPayload {
  birthDate: string;
  cpf: string;
  firstName: string;
  lastName: string;
  password: string;
  termsAccepted: boolean;
}

interface RegisterUserResponse {
  email: string;
  id: number;
  name: string;
}

interface RequestEmailOtpPayload {
  email?: string;
  phone?: string;
}

interface RequestEmailOtpResponse {
  sent: boolean;
}

interface RequestPasswordResetPayload {
  email?: string;
  phone?: string;
}

interface RequestPasswordResetResponse {
  token: string;
}

interface ResendPasswordResetPayload {
  token: string;
}

interface ResendPasswordResetResponse {
  ok: boolean;
}

interface VerifyLoginCodePayload {
  code: string;
}

interface VerifyLoginCodeResponse {
  email: string;
  id: number;
  name: string;
}

export type {
  ConfirmPasswordResetPayload,
  ConfirmPasswordResetResponse,
  LoginPasswordCredentialsPayload,
  LookupPhonePayload,
  LookupPhoneResponse,
  Permission,
  PermissionGroup,
  PermissionGroupItem,
  Profile,
  RegisterUserPayload,
  RegisterUserResponse,
  RequestEmailOtpPayload,
  RequestEmailOtpResponse,
  RequestPasswordResetPayload,
  RequestPasswordResetResponse,
  ResendPasswordResetPayload,
  ResendPasswordResetResponse,
  VerifyLoginCodePayload,
  VerifyLoginCodeResponse,
};
