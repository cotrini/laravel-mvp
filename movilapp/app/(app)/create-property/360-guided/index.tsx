import React, { useRef, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Pressable,
  Alert,
  Image,
  ScrollView,
  ActivityIndicator,
  useWindowDimensions,
} from 'react-native';
import { router } from 'expo-router';
import { CameraView } from 'expo-camera';
import { SphereGuide } from '@/components/tour360/SphereGuide';
import { PropertyPicker } from '@/components/property/PropertyPicker';
import { useCamera } from '@/hooks/useCamera';
import { composeEquirectangular } from '@/utils/equirectangular';
import { tour360Service } from '@/services/tour360.service';
import { CapturedFrame } from '@/types/tour360';
import { SphereGridPoint } from '@/types/sensors';
import { Property } from '@/types/property';
import { theme } from '@/constants/themes';

const GRID_SIZE = 4;

type Step = 'pick' | 'ready' | 'capturing' | 'processing' | 'preview';

export default function GuidedTour360Screen() {
  const { width: screenWidth } = useWindowDimensions();
  const [step, setStep] = useState<Step>('pick');
  const [property, setProperty] = useState<Property | null>(null);
  const [panoramaUri, setPanoramaUri] = useState<string | null>(null);
  const [uploading, setUploading] = useState(false);
  const framesRef = useRef<CapturedFrame[]>([]);
  const { takePicture, setCameraRef, requestPermissions } = useCamera();

  const handleStart = async () => {
    const granted = await requestPermissions();
    if (!granted) {
      Alert.alert('Permiso requerido', 'Se necesita acceso a la cámara');
      return;
    }
    framesRef.current = [];
    setStep('capturing');
  };

  // Devuelve true solo si la foto se guardó: así el punto no se marca como capturado en falso.
  const handlePointCapture = async (point: SphereGridPoint): Promise<boolean> => {
    const uri = await takePicture();
    if (!uri) return false;
    framesRef.current.push({
      uri,
      latitude: point.latitude,
      longitude: point.longitude,
      timestamp: Date.now(),
    });
    return true;
  };

  const handleComplete = async () => {
    setStep('processing');
    try {
      const uri = await composeEquirectangular(framesRef.current, GRID_SIZE);
      setPanoramaUri(uri);
      setStep('preview');
    } catch (error) {
      Alert.alert('Error', `No se pudo unir el panorama: ${String(error)}`);
      setStep('ready');
    }
  };

  const handleUpload = async () => {
    if (!property || !panoramaUri) return;
    setUploading(true);
    try {
      const scene = await tour360Service.uploadSceneImage(property.id, panoramaUri, 0);
      await tour360Service.createTour({
        property_id: property.id,
        title: `Recorrido 360° · ${property.title}`.slice(0, 255),
        scenes: [{ title: 'Panorámica', staged_image: scene.path }],
      });
      Alert.alert('Listo', 'El recorrido 360° quedó asociado al inmueble.', [
        { text: 'Continuar', onPress: () => router.back() },
      ]);
    } catch (error: any) {
      const message = error?.response?.data?.message ?? 'No se pudo subir el recorrido 360°.';
      Alert.alert('Error', message);
    } finally {
      setUploading(false);
    }
  };

  if (step === 'pick') {
    return (
      <View style={styles.screen}>
        <Text style={styles.title}>¿Para qué inmueble es el recorrido?</Text>
        <PropertyPicker
          onSelect={(selected) => {
            setProperty(selected);
            setStep('ready');
          }}
        />
      </View>
    );
  }

  if (step === 'ready') {
    return (
      <View style={styles.screen}>
        <Text style={styles.title}>Captura 360° guiada</Text>
        <Text style={styles.subtitle}>
          {property?.title}. Gira el teléfono y captura los puntos marcados en la esfera hasta completarla.
        </Text>
        <Pressable style={styles.primaryButton} onPress={handleStart} accessibilityRole="button">
          <Text style={styles.primaryButtonText}>Comenzar captura</Text>
        </Pressable>
        <Pressable style={styles.secondaryButton} onPress={() => setStep('pick')}>
          <Text style={styles.secondaryButtonText}>Cambiar inmueble</Text>
        </Pressable>
      </View>
    );
  }

  if (step === 'capturing') {
    return (
      <View style={styles.camera}>
        <CameraView style={styles.camera} ref={setCameraRef}>
          <SphereGuide
            onCapture={handlePointCapture}
            onComplete={handleComplete}
            gridSize={GRID_SIZE}
          />
        </CameraView>
      </View>
    );
  }

  if (step === 'processing') {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={theme.colors.teal} />
        <Text style={styles.subtitle}>Uniendo las fotos en una panorámica…</Text>
      </View>
    );
  }

  // Vista previa: el mapa equirectangular 2:1 se recorre horizontalmente.
  const previewWidth = screenWidth * 2;
  return (
    <View style={styles.screen}>
      <Text style={styles.title}>Vista previa del panorama</Text>
      <ScrollView horizontal style={styles.preview} showsHorizontalScrollIndicator>
        {panoramaUri ? (
          <Image
            source={{ uri: panoramaUri }}
            style={{ width: previewWidth, height: previewWidth / 2 }}
            resizeMode="contain"
          />
        ) : null}
      </ScrollView>
      <Pressable
        style={[styles.primaryButton, uploading && styles.disabled]}
        onPress={handleUpload}
        disabled={uploading}
        accessibilityRole="button"
      >
        <Text style={styles.primaryButtonText}>
          {uploading ? 'Subiendo…' : 'Subir recorrido 360°'}
        </Text>
      </Pressable>
      <Pressable style={styles.secondaryButton} onPress={() => setStep('ready')} disabled={uploading}>
        <Text style={styles.secondaryButtonText}>Capturar de nuevo</Text>
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    padding: 20,
    backgroundColor: theme.colors.pageBg,
  },
  centered: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
    backgroundColor: theme.colors.pageBg,
  },
  camera: {
    flex: 1,
  },
  title: {
    fontSize: 22,
    fontWeight: '700',
    color: theme.colors.ink,
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 15,
    color: theme.colors.muted,
    lineHeight: 22,
    marginBottom: 20,
    textAlign: 'center',
  },
  preview: {
    flexGrow: 0,
    backgroundColor: theme.colors.ink,
    borderRadius: 14,
    marginBottom: 20,
  },
  primaryButton: {
    backgroundColor: theme.colors.yellowButton,
    borderRadius: theme.radius.pill,
    height: 52,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  primaryButtonText: {
    fontSize: 16,
    fontWeight: '700',
    color: theme.colors.ink,
  },
  secondaryButton: {
    height: 48,
    alignItems: 'center',
    justifyContent: 'center',
  },
  secondaryButtonText: {
    fontSize: 15,
    fontWeight: '600',
    color: theme.colors.teal,
  },
  disabled: {
    opacity: 0.6,
  },
});
