const STORAGE_KEY = 'karkas.admin.reauth';

export function loadStoredReauth(): string {
  try {
    return sessionStorage.getItem(STORAGE_KEY) ?? '';
  } catch {
    return '';
  }
}

export function storeReauth(password: string): void {
  if (password === '') {
    return;
  }
  try {
    sessionStorage.setItem(STORAGE_KEY, password);
  } catch {
    // ignore quota / private mode
  }
}

export function clearStoredReauth(): void {
  try {
    sessionStorage.removeItem(STORAGE_KEY);
  } catch {
    // ignore
  }
}

export function reauthHeaders(password: string): Record<string, string> {
  return {
    'Content-Type': 'application/json',
    'X-Karkas-Reauth': password,
  };
}

export function effectiveReauth(passwordField: string): string {
  return loadStoredReauth() || passwordField;
}

export function isReauthRequiredMessage(message: string): boolean {
  return message.includes('X-Karkas-Reauth') || message.includes('Re-authentication') || message.includes('re-authentication');
}
