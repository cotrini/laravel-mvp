import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  SafeAreaView,
  Alert,
} from 'react-native';
import { router } from 'expo-router';
import { useAuth } from '@/hooks/useAuth';
import { Button } from '@/components/common/Button';
import { Input } from '@/components/common/Input';

export default function PhoneVerifyScreen() {
  const [phoneNumber, setPhoneNumber] = useState('');
  const [loading, setLoading] = useState(false);
  const { updatePhone } = useAuth();

  const handleVerifyPhone = async () => {
    if (!phoneNumber) {
      Alert.alert('Error', 'Por favor ingresa tu número telefónico');
      return;
    }

    setLoading(true);
    try {
      await updatePhone(phoneNumber);
      Alert.alert('Éxito', 'Número telefónico actualizado', [
        {
          text: 'Continuar',
          onPress: () => router.replace('/(app)/home'),
        },
      ]);
    } catch (error) {
      Alert.alert('Error', 'No se pudo actualizar el teléfono');
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.content}>
        <Text style={styles.title}>Verifica tu Teléfono</Text>
        <Text style={styles.subtitle}>
          Necesitamos tu número telefónico para completar tu perfil
        </Text>

        <Input
          label="Número Telefónico"
          placeholder="+57 3xx xxx xxxx"
          value={phoneNumber}
          onChangeText={setPhoneNumber}
          keyboardType="phone-pad"
        />

        <Button
          title="Verificar"
          onPress={handleVerifyPhone}
          loading={loading}
          style={styles.button}
        />

        <Button
          title="Omitir por ahora"
          onPress={() => router.replace('/(app)/home')}
          variant="secondary"
        />
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#fff',
  },
  content: {
    flex: 1,
    padding: 20,
    justifyContent: 'center',
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 14,
    color: '#666',
    marginBottom: 24,
    lineHeight: 20,
  },
  button: {
    marginBottom: 12,
  },
});
