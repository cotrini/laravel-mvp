import { create } from 'zustand';
import { onAuthStateChanged } from 'firebase/auth';
import { User } from '@/types/auth';
import { authService, getApiErrorMessage, RegisterPayload } from '@/services/auth.service';
import { firebaseAuth } from '@/services/firebase';
import { sessionStorage } from '@/services/session.storage';

interface AuthStoreState {
  user: User | null;
  isLoading: boolean;
  initialized: boolean;
  error: string | null;
  isAuthenticated: boolean;
  needsPhoneCompletion: boolean;
  // Correo de una cuenta recién creada que debe verificarse antes de iniciar sesión.
  pendingEmailVerification: string | null;

  initialize: () => () => void;
  loginWithGoogle: (googleIdToken?: string) => Promise<void>;
  loginWithEmail: (email: string, password: string) => Promise<void>;
  registerWithEmail: (payload: RegisterPayload) => Promise<void>;
  resendVerificationEmail: () => Promise<string>;
  dismissEmailVerification: () => void;
  logout: () => Promise<void>;
  updatePhone: (phoneNumber: string) => Promise<void>;
  clearError: () => void;
}

type SetState = (partial: Partial<AuthStoreState>) => void;

const signedInState = (user: User): Partial<AuthStoreState> => ({
  user,
  isAuthenticated: true,
  needsPhoneCompletion: !user.phone_number,
  pendingEmailVerification: null,
});

const signedOutState: Partial<AuthStoreState> = {
  user: null,
  isAuthenticated: false,
  needsPhoneCompletion: false,
  pendingEmailVerification: null,
};

// Sesión de Google (Firebase): el backend crea/actualiza el usuario desde el ID token.
const syncFirebaseSession = async (set: SetState) => {
  const response = await authService.syncWithBackend();
  set({
    user: response.data,
    isAuthenticated: true,
    needsPhoneCompletion: response.needs_phone_completion,
    pendingEmailVerification: null,
  });
};

// Recupera la sesión de base de datos guardada en el dispositivo.
const restoreDatabaseSession = async (set: SetState) => {
  if (!(await authService.hasDatabaseSession())) return;
  try {
    const user = await authService.getCurrentUser();
    set(signedInState(user));
  } catch (error: any) {
    // Solo se descarta el token si el servidor lo rechaza; un fallo de red no cierra la sesión.
    if (error?.response?.status === 401) {
      await sessionStorage.clearToken();
      set(signedOutState);
    }
  } finally {
    set({ initialized: true });
  }
};

export const useAuthStore = create<AuthStoreState>((set, get) => ({
  user: null,
  isLoading: false,
  initialized: false,
  error: null,
  isAuthenticated: false,
  needsPhoneCompletion: false,
  pendingEmailVerification: null,

  // Restaura la sesión de base de datos y se suscribe a Firebase (Google); devuelve la función para cancelar.
  initialize: () => {
    const unsubscribe = onAuthStateChanged(firebaseAuth, async (firebaseUser) => {
      // La sesión de base de datos tiene prioridad y la gestiona restoreDatabaseSession.
      if (await authService.hasDatabaseSession()) return;

      if (!firebaseUser) {
        set({ ...signedOutState, initialized: true });
        return;
      }
      try {
        await syncFirebaseSession(set);
      } catch (error: any) {
        set({ error: error.message || 'No se pudo validar la sesión', isAuthenticated: false });
      } finally {
        set({ initialized: true });
      }
    });

    restoreDatabaseSession(set);
    return unsubscribe;
  },

  loginWithGoogle: async (googleIdToken) => {
    set({ isLoading: true, error: null });
    try {
      await authService.signInWithGoogle(googleIdToken);
      await syncFirebaseSession(set);
    } catch (error: any) {
      set({ error: error.message || 'Error al iniciar sesión', isAuthenticated: false });
      throw error;
    } finally {
      set({ isLoading: false });
    }
  },

  loginWithEmail: async (email, password) => {
    set({ isLoading: true, error: null });
    try {
      await authService.loginWithDatabase(email, password);
      const user = await authService.getCurrentUser();
      set(signedInState(user));
    } catch (error: any) {
      if (error?.response?.data?.code === 'email_not_verified') {
        set({ pendingEmailVerification: email.trim(), error: null, isAuthenticated: false });
        return;
      }
      const message = getApiErrorMessage(error, 'No se pudo iniciar sesión');
      set({ error: message, isAuthenticated: false });
      throw new Error(message);
    } finally {
      set({ isLoading: false });
    }
  },

  registerWithEmail: async (payload) => {
    set({ isLoading: true, error: null });
    try {
      await authService.registerWithDatabase(payload);
      set({ pendingEmailVerification: payload.email.trim() });
    } catch (error: any) {
      const message = getApiErrorMessage(error, 'No se pudo crear la cuenta');
      set({ error: message });
      throw new Error(message);
    } finally {
      set({ isLoading: false });
    }
  },

  resendVerificationEmail: async () => {
    const email = get().pendingEmailVerification;
    if (!email) return '';
    set({ isLoading: true, error: null });
    try {
      return await authService.resendVerification(email);
    } catch (error: any) {
      const message = getApiErrorMessage(error, 'No pudimos reenviar el correo');
      set({ error: message });
      throw new Error(message);
    } finally {
      set({ isLoading: false });
    }
  },

  dismissEmailVerification: () => set({ pendingEmailVerification: null, error: null }),

  logout: async () => {
    await authService.logout();
    set({ ...signedOutState, error: null });
  },

  updatePhone: async (phoneNumber) => {
    set({ isLoading: true, error: null });
    try {
      const updatedUser = await authService.updatePhone({ phone_number: phoneNumber });
      set({ user: updatedUser, needsPhoneCompletion: false });
    } catch (error: any) {
      set({ error: error.message });
      throw error;
    } finally {
      set({ isLoading: false });
    }
  },

  clearError: () => set({ error: null }),
}));
