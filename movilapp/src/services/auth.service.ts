import { GoogleAuthProvider, signInWithCredential, signInWithPopup, signOut } from 'firebase/auth';
import { Platform } from 'react-native';
import apiClient from './api.config';
import { firebaseAuth } from './firebase';
import { sessionStorage } from './session.storage';
import { AuthResponse, User, PhoneVerificationPayload } from '@/types/auth';

export interface RegisterPayload {
  name: string;
  email: string;
  phone_number: string;
  password: string;
  password_confirmation: string;
}

// Primer error de validación (422) o mensaje general de la API.
export function getApiErrorMessage(error: unknown, fallback: string): string {
  const data = (error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })
    ?.response?.data;
  const firstFieldError = data?.errors ? Object.values(data.errors)[0]?.[0] : undefined;
  return firstFieldError ?? data?.message ?? fallback;
}

export const authService = {
  // Web: popup de Google (igual que login.blade.php). Nativo: credencial del id_token de expo-auth-session.
  async signInWithGoogle(googleIdToken?: string): Promise<void> {
    // Una sola sesión activa: Google reemplaza a la cuenta de base de datos.
    await sessionStorage.clearToken();
    if (Platform.OS === 'web') {
      await signInWithPopup(firebaseAuth, new GoogleAuthProvider());
      return;
    }
    if (!googleIdToken) throw new Error('Falta el id_token de Google');
    await signInWithCredential(firebaseAuth, GoogleAuthProvider.credential(googleIdToken));
  },

  // Cuenta de base de datos: POST /auth/login devuelve un token Sanctum que se guarda en el dispositivo.
  async loginWithDatabase(email: string, password: string): Promise<void> {
    const response = await apiClient.post<{ token: string }>('/auth/login', {
      email: email.trim(),
      password,
    });
    await sessionStorage.setToken(response.data.token);
  },

  async registerWithDatabase(payload: RegisterPayload): Promise<string> {
    const response = await apiClient.post<{ message: string }>('/auth/register', {
      ...payload,
      name: payload.name.trim(),
      email: payload.email.trim(),
      phone_number: payload.phone_number.trim(),
    });
    return response.data.message;
  },

  async resendVerification(email: string): Promise<string> {
    const response = await apiClient.post<{ message: string }>('/auth/email/resend', {
      email: email.trim(),
    });
    return response.data.message;
  },

  async hasDatabaseSession(): Promise<boolean> {
    return (await sessionStorage.getToken()) !== null;
  },

  // Envía el Firebase ID token en Bearer; el backend crea/actualiza el usuario local.
  async syncWithBackend(): Promise<AuthResponse> {
    const firebaseUser = firebaseAuth.currentUser;
    if (!firebaseUser) throw new Error('No hay sesión de Firebase activa');
    // Forzar refresco: el token cacheado puede ser anterior a la verificación y traer email_verified=false.
    const idToken = await firebaseUser.getIdToken(true);
    const response = await apiClient.post<AuthResponse>('/auth/firebase', null, {
      headers: { Authorization: `Bearer ${idToken}` },
    });
    return response.data;
  },

  async getCurrentUser(): Promise<User> {
    const response = await apiClient.get<{ data: User }>('/me');
    return response.data.data;
  },

  async updatePhone(payload: PhoneVerificationPayload): Promise<User> {
    const response = await apiClient.patch<{ data: User }>('/me/phone', payload);
    return response.data.data;
  },

  // Revoca el token de base de datos en el servidor y limpia el dispositivo; también cierra Firebase (Google).
  async logout(): Promise<void> {
    if (await sessionStorage.getToken()) {
      try {
        await apiClient.post('/auth/logout');
      } catch {
        // Si el token ya expiró o no hay red, se limpia igualmente en el dispositivo.
      }
    }
    await sessionStorage.clearToken();
    await signOut(firebaseAuth);
  },
};
