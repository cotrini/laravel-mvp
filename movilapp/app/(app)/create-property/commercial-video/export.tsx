import React, { useState } from 'react';
import { View, Text, StyleSheet, Pressable, ActivityIndicator, Alert } from 'react-native';
import { router, Stack } from 'expo-router';
import { useVideoStore } from '@/stores/videoStore';
import { videoService } from '@/services/video.service';
import { MAX_VIDEO_SECONDS } from '@/types/video';
import { theme } from '@/constants/themes';

export default function ExportScreen() {
  const storyboard = useVideoStore((state) => state.currentStoryboard);
  const reset = useVideoStore((state) => state.reset);
  const [uploading, setUploading] = useState(false);

  if (!storyboard) {
    return (
      <View style={styles.centered}>
        <Text style={styles.muted}>No hay un video en preparación.</Text>
      </View>
    );
  }

  const clips = storyboard.scenes.filter((scene) => scene.videoUri);
  const ready = clips.length === storyboard.scenes.length;
  const totalSeconds = clips.reduce((sum, scene) => sum + (scene.duration ?? 0), 0);
  const overLimit = totalSeconds > MAX_VIDEO_SECONDS;

  const handleAssemble = async () => {
    if (!ready || uploading) return;
    setUploading(true);
    try {
      const response = await videoService.assemble(
        storyboard.propertyId,
        storyboard.format,
        clips.map((scene) => scene.videoUri as string)
      );
      reset();
      Alert.alert('Video publicado', response.message, [
        { text: 'Listo', onPress: () => router.replace('/(app)/home') },
      ]);
    } catch (error: any) {
      const message = error?.response?.data?.message ?? 'No se pudo ensamblar el video. Intenta de nuevo.';
      Alert.alert('Error', message);
    } finally {
      setUploading(false);
    }
  };

  return (
    <View style={styles.screen}>
      <Stack.Screen options={{ title: 'Ensamblar video' }} />
      <Text style={styles.heading}>Revisa antes de ensamblar</Text>

      <View style={styles.card}>
        <Row label="Formato" value={storyboard.format === 'horizontal' ? 'Horizontal (16:9)' : 'Vertical (9:16)'} />
        <Row label="Escenas" value={`${clips.length} de ${storyboard.scenes.length} listas`} />
        <Row label="Duración total" value={`${totalSeconds} s de ${MAX_VIDEO_SECONDS} s máximo`} />
        <Row label="Marca" value="Logo Colombia Sin Comisión en la esquina superior" />
      </View>

      {overLimit ? (
        <Text style={styles.warning}>
          Las escenas suman más de {MAX_VIDEO_SECONDS} s. El servidor cortará el video a {MAX_VIDEO_SECONDS} s.
        </Text>
      ) : null}

      <Pressable
        style={[styles.primaryButton, (!ready || uploading) && styles.disabled]}
        onPress={handleAssemble}
        disabled={!ready || uploading}
        accessibilityRole="button"
      >
        {uploading ? (
          <View style={styles.row}>
            <ActivityIndicator color={theme.colors.ink} />
            <Text style={styles.primaryButtonText}>  Ensamblando y subiendo…</Text>
          </View>
        ) : (
          <Text style={styles.primaryButtonText}>Ensamblar y subir al inmueble</Text>
        )}
      </Pressable>
      <Pressable style={styles.secondaryButton} onPress={() => router.back()} disabled={uploading}>
        <Text style={styles.secondaryButtonText}>Volver al storyboard</Text>
      </Pressable>
    </View>
  );
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.infoRow}>
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={styles.infoValue}>{value}</Text>
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
  muted: {
    color: theme.colors.muted,
  },
  heading: {
    fontSize: 22,
    fontWeight: '700',
    color: theme.colors.ink,
    marginBottom: 16,
  },
  card: {
    backgroundColor: theme.colors.white,
    borderRadius: 18,
    padding: 18,
    borderWidth: 1,
    borderColor: theme.colors.border,
    marginBottom: 16,
  },
  infoRow: {
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: theme.colors.border,
  },
  infoLabel: {
    fontSize: 12,
    fontWeight: '700',
    color: theme.colors.teal,
    marginBottom: 2,
  },
  infoValue: {
    fontSize: 15,
    color: theme.colors.ink,
  },
  warning: {
    color: theme.colors.danger,
    marginBottom: 12,
    lineHeight: 20,
  },
  primaryButton: {
    backgroundColor: theme.colors.yellowButton,
    borderRadius: theme.radius.pill,
    height: 52,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  primaryButtonText: {
    fontSize: 16,
    fontWeight: '700',
    color: theme.colors.ink,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
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
  disabled: {
    opacity: 0.5,
  },
});
