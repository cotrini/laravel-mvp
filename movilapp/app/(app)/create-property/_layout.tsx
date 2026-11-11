import { Redirect, Stack } from 'expo-router';
import { useAuth } from '@/hooks/useAuth';
import { theme } from '@/constants/themes';

// Igual que las rutas protegidas del web (auth + verified): sin sesión se pide login.
export default function CreatePropertyLayout() {
  const { isAuthenticated, initialized } = useAuth();

  if (!initialized) return null;
  if (!isAuthenticated) return <Redirect href="/(auth)/login" />;

  return (
    <Stack
      screenOptions={{
        headerTintColor: theme.colors.teal,
        headerTitleStyle: { fontWeight: '700', color: theme.colors.ink },
      }}
    >
      <Stack.Screen name="360-guided/index" options={{ title: 'Captura 360 Guiada' }} />
      <Stack.Screen name="commercial-video/format-select" options={{ title: 'Formato de Video' }} />
      <Stack.Screen name="commercial-video/storyboard" options={{ title: 'Guía de video' }} />
      <Stack.Screen name="commercial-video/export" options={{ title: 'Ensamblar video' }} />
    </Stack>
  );
}
