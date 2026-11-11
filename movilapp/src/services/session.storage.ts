import { Platform } from 'react-native';
import * as SecureStore from 'expo-secure-store';

const TOKEN_KEY = 'csc_db_session_token';

interface WebStorage {
  getItem(key: string): string | null;
  setItem(key: string, value: string): void;
  removeItem(key: string): void;
}

const webStorage = () => (globalThis as unknown as { localStorage?: WebStorage }).localStorage;

// Token Sanctum de la cuenta de base de datos: SecureStore en nativo y localStorage en web.
export const sessionStorage = {
  async getToken(): Promise<string | null> {
    if (Platform.OS === 'web') return webStorage()?.getItem(TOKEN_KEY) ?? null;
    return SecureStore.getItemAsync(TOKEN_KEY);
  },

  async setToken(token: string): Promise<void> {
    if (Platform.OS === 'web') {
      webStorage()?.setItem(TOKEN_KEY, token);
      return;
    }
    await SecureStore.setItemAsync(TOKEN_KEY, token);
  },

  async clearToken(): Promise<void> {
    if (Platform.OS === 'web') {
      webStorage()?.removeItem(TOKEN_KEY);
      return;
    }
    await SecureStore.deleteItemAsync(TOKEN_KEY);
  },
};
