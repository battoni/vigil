import { translateError } from '@Helpers';
import { useUserStore } from '@UserModule';

function getTranslatedErrorMessage(error: any): string {
  const status = error.response?.status;

  // NOTE :: Validation errors
  const validationErrors = status === 422 && error.response?.data?.errors;
  if (validationErrors) {
    const errors = error.response?.data?.errors;
    if (errors) {
      const firstError = Object.values(errors)[0] as string[] | undefined;
      if (firstError && firstError.length > 0 && firstError[0]) return translateError(firstError[0]);
    }
  }

  // NOTE :: Other HTTP errors
  const otherHttpErrors = status && error.response?.data?.message;
  if (otherHttpErrors) {
    const errorMessage = error.response?.data?.message || error.message || 'errors.unexpected';
    return translateError(errorMessage);
  }

  // NOTE :: Network or other errors
  const errorMessage = error.message || 'errors.unexpected';
  return translateError(errorMessage);
}

async function errorHandler(error: any) {
  const status = error.response?.status;
  const isValidationError = status === 422 && error.response?.data?.errors;
  const isForwardedError = Boolean(error.config?.forwardError);

  const structuredError = {
    code: error.code,
    data: error.response?.data,
    message: error.message,
    method: error.config?.method,
    status: error.response?.status,
    url: error.config?.url,
  };

  if (!isValidationError && !isForwardedError) {
    console.error('API Error', structuredError);
  }

  const isAuthError = [401, 403].includes(status);

  if (isAuthError) {
    useUserStore().clearUser();
  }

  // NOTE :: Attach translated message to error for view consumption
  error.translatedMessage = getTranslatedErrorMessage(error);

  throw error;
}

export default errorHandler;
