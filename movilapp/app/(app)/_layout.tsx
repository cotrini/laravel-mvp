import { Stack } from 'expo-router';

export default function AppLayout() {
  return (
    <Stack
      screenOptions={{
        headerShown: true,
        headerTintColor: '#FF5722',
        headerTitleStyle: {
          fontWeight: '600',
        },
      }}
    >
      <Stack.Screen
        name="home"
        options={{
          title: 'Colombia Sin Comisión',
          headerShown: false,
        }}
      />
    </Stack>
  );
}
