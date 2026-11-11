import React, { useEffect } from 'react';
import { Stack } from 'expo-router';
import { useAuth } from '@/hooks/useAuth';
import { useFonts } from 'expo-font';
import * as SplashScreen from 'expo-splash-screen';

SplashScreen.preventAutoHideAsync();

export default function RootLayout() {
  const { initialize, initialized } = useAuth();
  const [fontsLoaded] = useFonts({
    // Agregar fuentes personalizadas aquí si es necesario
  });

  // Firebase restaura la sesión y el store sincroniza con el backend.
  useEffect(() => initialize(), [initialize]);

  useEffect(() => {
    if (fontsLoaded && initialized) {
      SplashScreen.hideAsync();
    }
  }, [fontsLoaded, initialized]);

  if (!fontsLoaded) {
    return null;
  }

  return (
    <Stack>
      <Stack.Screen name="(auth)" options={{ headerShown: false }} />
      <Stack.Screen name="(app)" options={{ headerShown: false }} />
    </Stack>
  );
}
