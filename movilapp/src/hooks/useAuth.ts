import { useAuthStore } from '@/stores/authStore';

export const useAuth = () => {
  const auth = useAuthStore();

  return {
    user: auth.user,
    isLoading: auth.isLoading,
    initialized: auth.initialized,
    error: auth.error,
    isAuthenticated: auth.isAuthenticated,
    needsPhoneCompletion: auth.needsPhoneCompletion,
    pendingEmailVerification: auth.pendingEmailVerification,
    initialize: auth.initialize,
    loginWithGoogle: auth.loginWithGoogle,
    loginWithEmail: auth.loginWithEmail,
    registerWithEmail: auth.registerWithEmail,
    resendVerificationEmail: auth.resendVerificationEmail,
    dismissEmailVerification: auth.dismissEmailVerification,
    logout: auth.logout,
    updatePhone: auth.updatePhone,
    clearError: auth.clearError,
  };
};
