import type { LoginPasswordCredentialsPayload } from '../interfaces';

/**
 * Login com identificador (telefone ou e-mail) + senha.
 * TODO-ID-45: Enquanto `MLoginPasswordCredentials` usa mocks locais, só regista o payload.
 * Depois, alinhar com o fluxo real (remover mocks no componente e tratar resposta/erro como em `MLoginForm`).
 */
export default async function LoginPasswordCredentialsService(payload: LoginPasswordCredentialsPayload): Promise<void> {
  console.log('[LoginPasswordCredentials]', payload);

  // TODO-ID-45: Integrar com API quando o backend deste fluxo estiver estável, p.ex.:
  // import type { ApiResponse } from '@Providers';
  // import type { User } from '@UserModule';
  // import { API } from '@Providers';
  // return API({
  //   payload: { username: payload.username, password: payload.password },
  //   endpoint: 'auth/login',
  //   method: 'post',
  // });
}
