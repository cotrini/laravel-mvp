import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  SafeAreaView,
  ScrollView,
  Platform,
  Alert,
  Pressable,
  TextInput,
  TextInputProps,
} from 'react-native';
import { router } from 'expo-router';
import * as WebBrowser from 'expo-web-browser';
import * as Google from 'expo-auth-session/providers/google';
import { useAuth } from '@/hooks/useAuth';
import { theme } from '@/constants/themes';
import { BrandLogo } from '@/components/common/BrandLogo';

WebBrowser.maybeCompleteAuthSession();

// Client IDs de Google (Firebase > Authentication > Google). Ver START_APP.md.
const googleConfig = {
  webClientId: process.env.EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID,
  androidClientId: process.env.EXPO_PUBLIC_ANDROID_CLIENT_ID,
  iosClientId: process.env.EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID,
};

type Mode = 'login' | 'register';

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
// Mismo mínimo que la validación de registro web del backend (min:8).
const MIN_PASSWORD_LENGTH = 8;

function Field({ label, ...inputProps }: { label: string } & TextInputProps) {
  return (
    <View style={styles.field}>
      <Text style={styles.label}>{label}</Text>
      <TextInput
        style={styles.input}
        placeholderTextColor={theme.colors.muted}
        autoCorrect={false}
        {...inputProps}
      />
    </View>
  );
}

export default function LoginScreen() {
  const {
    loginWithGoogle,
    loginWithEmail,
    registerWithEmail,
    resendVerificationEmail,
    dismissEmailVerification,
    isLoading,
    error,
    clearError,
    isAuthenticated,
    needsPhoneCompletion,
    pendingEmailVerification,
  } = useAuth();
  const [request, response, promptAsync] = Google.useIdTokenAuthRequest(googleConfig);

  const [mode, setMode] = useState<Mode>('login');
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [phone, setPhone] = useState('');
  const [formError, setFormError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  useEffect(() => {
    if (isAuthenticated) {
      router.replace('/(app)/home');
    }
  }, [isAuthenticated]);

  // Nativo: el id_token de Google se convierte en credencial de Firebase dentro del store.
  useEffect(() => {
    if (response?.type !== 'success') return;
    loginWithGoogle(response.params.id_token).catch(() => undefined);
  }, [response]);

  const switchMode = (next: Mode) => {
    clearError();
    setFormError(null);
    setNotice(null);
    setMode(next);
  };

  const validateForm = (): string | null => {
    if (mode === 'register' && !name.trim()) return 'Ingresa tu nombre.';
    if (!EMAIL_PATTERN.test(email.trim())) return 'Ingresa un correo electrónico válido.';
    if (mode === 'register' && phone.replace(/\D/g, '').length < 10) {
      return 'Ingresa tu número de celular colombiano (10 dígitos).';
    }
    if (mode === 'login') return password ? null : 'Ingresa tu contraseña.';
    if (password.length < MIN_PASSWORD_LENGTH) {
      return `La contraseña debe tener al menos ${MIN_PASSWORD_LENGTH} caracteres.`;
    }
    if (password !== confirmPassword) return 'Las contraseñas no coinciden.';
    return null;
  };

  const handleEmailSubmit = async () => {
    const validationError = validateForm();
    setFormError(validationError);
    if (validationError) return;

    clearError();
    setNotice(null);
    try {
      if (mode === 'register') {
        await registerWithEmail({
          name,
          email,
          phone_number: phone,
          password,
          password_confirmation: confirmPassword,
        });
      } else {
        await loginWithEmail(email, password);
      }
    } catch {
      // El error queda en el store y se muestra abajo.
    }
  };

  const handleGoogleSignIn = async () => {
    clearError();
    try {
      if (Platform.OS === 'web') {
        await loginWithGoogle();
      } else {
        if (!request) {
          Alert.alert('Configuración', 'Faltan los EXPO_PUBLIC_GOOGLE_*_CLIENT_ID en .env.local');
          return;
        }
        await promptAsync();
      }
    } catch {
      // El error queda en el store y se muestra abajo.
    }
  };

  const handleResendVerification = async () => {
    setNotice(null);
    try {
      setNotice(await resendVerificationEmail());
    } catch {
      // El error queda en el store y se muestra abajo.
    }
  };

  // La cuenta ya fue creada: se vuelve al inicio de sesión con el correo escrito.
  const handleBackToLogin = () => {
    dismissEmailVerification();
    clearError();
    setNotice(null);
    setFormError(null);
    setMode('login');
    setPassword('');
    setConfirmPassword('');
  };

  const shownError = formError ?? error;

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <View style={styles.card}>
          <View style={styles.brand}>
            <BrandLogo size={38} />
            <Text style={styles.brandTitle}>
              Colombia <Text style={styles.brandAccent}>Sin Comisión</Text>
            </Text>
          </View>

          {pendingEmailVerification ? (
            <View>
              <Text style={styles.heading}>Verifica tu correo</Text>
              <Text style={styles.subheading}>
                Enviamos un enlace de verificación a {pendingEmailVerification}. Ábrelo en tu navegador y luego
                inicia sesión con tu correo y contraseña.
              </Text>

              {shownError ? <Text style={styles.errorText}>{shownError}</Text> : null}
              {notice ? <Text style={styles.noticeText}>{notice}</Text> : null}

              <Pressable
                onPress={handleBackToLogin}
                style={({ pressed }) => [styles.primaryButton, pressed && styles.primaryButtonPressed]}
              >
                <Text style={styles.primaryButtonText}>Ya verifiqué mi correo, iniciar sesión</Text>
              </Pressable>

              <Pressable onPress={handleResendVerification} disabled={isLoading} style={styles.linkButton}>
                <Text style={styles.linkText}>Reenviar correo de verificación</Text>
              </Pressable>
            </View>
          ) : (
            <View>
              <Text style={styles.heading}>{mode === 'login' ? 'Inicia sesión' : 'Crea tu cuenta'}</Text>
              <Text style={styles.subheading}>
                {mode === 'login'
                  ? 'Accede con tu correo y contraseña para publicar inmuebles y gestionar tus recorridos 360°.'
                  : 'Regístrate con tu correo para publicar inmuebles y gestionar tus recorridos 360°.'}
              </Text>

              <View style={styles.tabs}>
                <Pressable
                  onPress={() => switchMode('login')}
                  style={[styles.tab, mode === 'login' && styles.tabActive]}
                >
                  <Text style={[styles.tabText, mode === 'login' && styles.tabTextActive]}>
                    Iniciar sesión
                  </Text>
                </Pressable>
                <Pressable
                  onPress={() => switchMode('register')}
                  style={[styles.tab, mode === 'register' && styles.tabActive]}
                >
                  <Text style={[styles.tabText, mode === 'register' && styles.tabTextActive]}>
                    Crear cuenta
                  </Text>
                </Pressable>
              </View>

              {mode === 'register' ? (
                <Field label="Nombre" value={name} onChangeText={setName} autoCapitalize="words" />
              ) : null}
              <Field
                label="Correo electrónico"
                value={email}
                onChangeText={setEmail}
                keyboardType="email-address"
                autoCapitalize="none"
              />
              {mode === 'register' ? (
                <Field
                  label="Celular"
                  placeholder="+57 300 123 4567"
                  value={phone}
                  onChangeText={setPhone}
                  keyboardType="phone-pad"
                />
              ) : null}
              <Field label="Contraseña" value={password} onChangeText={setPassword} secureTextEntry />
              {mode === 'register' ? (
                <Field
                  label="Confirmar contraseña"
                  value={confirmPassword}
                  onChangeText={setConfirmPassword}
                  secureTextEntry
                />
              ) : null}

              {shownError ? <Text style={styles.errorText}>{shownError}</Text> : null}

              <Pressable
                onPress={handleEmailSubmit}
                disabled={isLoading}
                style={({ pressed }) => [styles.primaryButton, pressed && styles.primaryButtonPressed]}
              >
                <Text style={styles.primaryButtonText}>
                  {isLoading ? 'Conectando…' : mode === 'login' ? 'Iniciar sesión' : 'Crear cuenta'}
                </Text>
              </Pressable>

              <View style={styles.divider}>
                <View style={styles.dividerLine} />
                <Text style={styles.dividerText}>o</Text>
                <View style={styles.dividerLine} />
              </View>

              <Pressable
                onPress={handleGoogleSignIn}
                disabled={isLoading}
                style={({ pressed }) => [styles.googleButton, pressed && styles.googleButtonHover]}
              >
                <Text style={styles.googleButtonText}>
                  {isLoading ? 'Conectando…' : 'Continuar con Google'}
                </Text>
              </Pressable>
            </View>
          )}

          {needsPhoneCompletion ? (
            <Text style={styles.hint}>Completa tu número de teléfono para continuar.</Text>
          ) : null}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: theme.colors.pageBg,
  },
  scroll: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 24,
  },
  card: {
    backgroundColor: theme.colors.white,
    borderRadius: 18,
    padding: 28,
    maxWidth: 440,
    width: '100%',
    alignSelf: 'center',
    shadowColor: '#191919',
    shadowOpacity: 0.08,
    shadowRadius: 18,
    shadowOffset: { width: 0, height: 5 },
    elevation: 4,
  },
  brand: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 24,
    gap: 10,
  },
  brandTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: theme.colors.ink,
    letterSpacing: -0.6,
  },
  brandAccent: {
    color: theme.colors.teal,
  },
  heading: {
    fontSize: 23,
    fontWeight: '700',
    color: theme.colors.ink,
    marginBottom: 8,
  },
  subheading: {
    fontSize: 15,
    color: theme.colors.muted,
    marginBottom: 20,
    lineHeight: 22,
  },
  errorText: {
    color: theme.colors.danger,
    marginBottom: 12,
  },
  noticeText: {
    color: theme.colors.teal,
    marginBottom: 12,
  },
  tabs: {
    flexDirection: 'row',
    backgroundColor: theme.colors.pageBg,
    borderRadius: theme.radius.pill,
    padding: 4,
    marginBottom: 20,
  },
  tab: {
    flex: 1,
    height: 40,
    borderRadius: theme.radius.pill,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabActive: {
    backgroundColor: theme.colors.white,
  },
  tabText: {
    fontSize: 14,
    fontWeight: '600',
    color: theme.colors.muted,
  },
  tabTextActive: {
    color: theme.colors.ink,
  },
  field: {
    marginBottom: 14,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: theme.colors.ink,
    marginBottom: 6,
  },
  input: {
    height: 48,
    borderWidth: 1,
    borderColor: theme.colors.border,
    borderRadius: 12,
    paddingHorizontal: 14,
    fontSize: 15,
    color: theme.colors.ink,
    backgroundColor: theme.colors.white,
  },
  primaryButton: {
    backgroundColor: theme.colors.teal,
    borderRadius: theme.radius.pill,
    height: 52,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 6,
  },
  primaryButtonPressed: {
    backgroundColor: theme.colors.tealHover,
  },
  primaryButtonText: {
    fontSize: 16,
    fontWeight: '700',
    color: theme.colors.white,
  },
  linkButton: {
    alignItems: 'center',
    paddingVertical: 12,
  },
  linkText: {
    color: theme.colors.tealHover,
    fontWeight: '700',
    fontSize: 14,
  },
  divider: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: 18,
    gap: 10,
  },
  dividerLine: {
    flex: 1,
    height: 1,
    backgroundColor: theme.colors.border,
  },
  dividerText: {
    color: theme.colors.muted,
    fontSize: 13,
  },
  googleButton: {
    backgroundColor: theme.colors.yellowButton,
    borderRadius: theme.radius.pill,
    height: 52,
    alignItems: 'center',
    justifyContent: 'center',
  },
  googleButtonHover: {
    backgroundColor: theme.colors.yellowButtonHover,
  },
  googleButtonText: {
    fontSize: 16,
    fontWeight: '700',
    color: theme.colors.ink,
  },
  hint: {
    marginTop: 14,
    color: theme.colors.muted,
    fontSize: 13,
  },
});
