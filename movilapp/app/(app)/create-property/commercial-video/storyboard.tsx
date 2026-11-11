import React from 'react';
import { View, Text, StyleSheet, Pressable, ScrollView, Alert } from 'react-native';
import { router, Stack } from 'expo-router';
import * as ImagePicker from 'expo-image-picker';
import { useVideoStore } from '@/stores/videoStore';
import { MUSIC_HINTS, MAX_VIDEO_SECONDS } from '@/types/video';
import { theme } from '@/constants/themes';

export default function StoryboardScreen() {
  const storyboard = useVideoStore((state) => state.currentStoryboard);
  const currentIndex = useVideoStore((state) => state.currentSceneIndex);
  const updateScene = useVideoStore((state) => state.updateScene);
  const nextScene = useVideoStore((state) => state.nextScene);
  const previousScene = useVideoStore((state) => state.previousScene);

  if (!storyboard) {
    return (
      <View style={styles.centered}>
        <Text style={styles.muted}>Primero elige formato e inmueble.</Text>
        <Pressable style={styles.secondaryButton} onPress={() => router.replace('/(app)/create-property/commercial-video/format-select')}>
          <Text style={styles.secondaryButtonText}>Elegir formato</Text>
        </Pressable>
      </View>
    );
  }

  const scenes = storyboard.scenes;
  const scene = scenes[currentIndex];
  const recorded = scenes.filter((s) => s.videoUri).length;
  const allRecorded = recorded === scenes.length;
  const isLast = currentIndex === scenes.length - 1;

  const attachClip = (result: ImagePicker.ImagePickerResult) => {
    if (result.canceled || !result.assets[0]) return;
    const asset = result.assets[0];
    const seconds = asset.duration ? Math.round(asset.duration / 1000) : scene.recommendedDuration;
    updateScene(scene.id, asset.uri, seconds);
  };

  const recordClip = async () => {
    const permission = await ImagePicker.requestCameraPermissionsAsync();
    if (!permission.granted) {
      Alert.alert('Permiso requerido', 'Se necesita acceso a la cámara para grabar la escena.');
      return;
    }
    const result = await ImagePicker.launchCameraAsync({
      mediaTypes: ['videos'],
      videoMaxDuration: Math.max(scene.recommendedDuration + 5, 5),
    });
    attachClip(result);
  };

  const importClip = async () => {
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['videos'],
    });
    attachClip(result);
  };

  const goToExport = () => {
    if (!allRecorded) {
      Alert.alert('Faltan escenas', `Graba o importa las ${scenes.length - recorded} escenas pendientes.`);
      return;
    }
    router.push('/(app)/create-property/commercial-video/export');
  };

  return (
    <ScrollView style={styles.screen} contentContainerStyle={styles.content}>
      <Stack.Screen options={{ title: 'Guía de video' }} />

      <View style={styles.summary}>
        <Text style={styles.summaryText}>
          Formato {storyboard.format === 'horizontal' ? 'horizontal' : 'vertical'} · máximo {MAX_VIDEO_SECONDS} s
        </Text>
        <Text style={styles.summaryText}>
          Escena {currentIndex + 1} de {scenes.length} · {recorded} listas
        </Text>
        <View style={styles.progressTrack}>
          <View style={[styles.progressFill, { width: `${(recorded / scenes.length) * 100}%` }]} />
        </View>
      </View>

      <View style={styles.tabs}>
        {scenes.map((item, index) => {
          const active = index === currentIndex;
          return (
            <Pressable
              key={item.id}
              onPress={() => {
                const delta = index - currentIndex;
                if (delta > 0) for (let i = 0; i < delta; i++) nextScene();
                if (delta < 0) for (let i = 0; i < -delta; i++) previousScene();
              }}
              style={[styles.tab, active && styles.tabActive, item.videoUri && styles.tabDone]}
            >
              <Text style={[styles.tabText, active && styles.tabTextActive]}>{index + 1}</Text>
            </Pressable>
          );
        })}
      </View>

      <View style={styles.card}>
        <Text style={styles.sceneTitle}>{scene.title}</Text>
        <Text style={styles.sceneDescription}>{scene.description}</Text>

        <Text style={styles.label}>Duración recomendada: {scene.recommendedDuration} s</Text>

        <Text style={styles.label}>Qué grabar</Text>
        {scene.hints.map((hint) => (
          <Text key={hint} style={styles.hint}>
            • {hint}
          </Text>
        ))}

        {MUSIC_HINTS[scene.id] ? (
          <>
            <Text style={styles.label}>Música / ritmo</Text>
            <Text style={styles.hint}>{MUSIC_HINTS[scene.id]}</Text>
          </>
        ) : null}

        <View style={[styles.status, scene.videoUri ? styles.statusDone : styles.statusPending]}>
          <Text style={styles.statusText}>
            {scene.videoUri ? `Clip listo (${scene.duration ?? 0} s)` : 'Pendiente de grabar'}
          </Text>
        </View>

        <Pressable style={styles.primaryButton} onPress={recordClip} accessibilityRole="button">
          <Text style={styles.primaryButtonText}>{scene.videoUri ? 'Volver a grabar' : 'Grabar escena'}</Text>
        </Pressable>
        <Pressable style={styles.secondaryButton} onPress={importClip} accessibilityRole="button">
          <Text style={styles.secondaryButtonText}>Importar de galería</Text>
        </Pressable>
      </View>

      <View style={styles.navRow}>
        <Pressable
          style={[styles.navButton, currentIndex === 0 && styles.disabled]}
          onPress={previousScene}
          disabled={currentIndex === 0}
        >
          <Text style={styles.navText}>← Anterior</Text>
        </Pressable>
        {isLast ? (
          <Pressable style={[styles.navButton, styles.navPrimary, !allRecorded && styles.disabled]} onPress={goToExport}>
            <Text style={[styles.navText, styles.navPrimaryText]}>Revisar y ensamblar</Text>
          </Pressable>
        ) : (
          <Pressable style={[styles.navButton, styles.navPrimary]} onPress={nextScene}>
            <Text style={[styles.navText, styles.navPrimaryText]}>Siguiente →</Text>
          </Pressable>
        )}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: theme.colors.pageBg,
  },
  content: {
    padding: 20,
    paddingBottom: 40,
  },
  centered: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
    backgroundColor: theme.colors.pageBg,
  },
  muted: {
    color: theme.colors.muted,
    marginBottom: 12,
  },
  summary: {
    marginBottom: 16,
  },
  summaryText: {
    color: theme.colors.muted,
    fontSize: 14,
    marginBottom: 4,
  },
  progressTrack: {
    height: 6,
    borderRadius: 3,
    backgroundColor: theme.colors.border,
    overflow: 'hidden',
    marginTop: 8,
  },
  progressFill: {
    height: 6,
    backgroundColor: theme.colors.teal,
  },
  tabs: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 16,
  },
  tab: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: theme.colors.border,
    backgroundColor: theme.colors.white,
  },
  tabActive: {
    borderColor: theme.colors.teal,
  },
  tabDone: {
    backgroundColor: '#e6f6f3',
  },
  tabText: {
    fontWeight: '700',
    color: theme.colors.muted,
  },
  tabTextActive: {
    color: theme.colors.teal,
  },
  card: {
    backgroundColor: theme.colors.white,
    borderRadius: 18,
    padding: 20,
    borderWidth: 1,
    borderColor: theme.colors.border,
  },
  sceneTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: theme.colors.ink,
    marginBottom: 6,
  },
  sceneDescription: {
    fontSize: 15,
    color: theme.colors.muted,
    lineHeight: 22,
    marginBottom: 12,
  },
  label: {
    fontSize: 13,
    fontWeight: '700',
    color: theme.colors.teal,
    marginTop: 10,
    marginBottom: 4,
  },
  hint: {
    fontSize: 14,
    color: theme.colors.ink,
    lineHeight: 21,
  },
  status: {
    borderRadius: 10,
    padding: 10,
    marginTop: 14,
    marginBottom: 12,
  },
  statusPending: {
    backgroundColor: '#fff7e0',
  },
  statusDone: {
    backgroundColor: '#e6f6f3',
  },
  statusText: {
    fontWeight: '700',
    color: theme.colors.ink,
  },
  primaryButton: {
    backgroundColor: theme.colors.yellowButton,
    borderRadius: theme.radius.pill,
    height: 50,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
  },
  primaryButtonText: {
    fontSize: 16,
    fontWeight: '700',
    color: theme.colors.ink,
  },
  secondaryButton: {
    height: 46,
    alignItems: 'center',
    justifyContent: 'center',
  },
  secondaryButtonText: {
    fontSize: 15,
    fontWeight: '600',
    color: theme.colors.teal,
  },
  navRow: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 18,
  },
  navButton: {
    flex: 1,
    height: 48,
    borderRadius: theme.radius.pill,
    borderWidth: 1,
    borderColor: theme.colors.border,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: theme.colors.white,
  },
  navPrimary: {
    backgroundColor: theme.colors.ink,
    borderColor: theme.colors.ink,
  },
  navText: {
    fontWeight: '700',
    color: theme.colors.ink,
  },
  navPrimaryText: {
    color: theme.colors.white,
  },
  disabled: {
    opacity: 0.4,
  },
});
