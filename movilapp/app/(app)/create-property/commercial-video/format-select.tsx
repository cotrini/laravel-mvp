import React, { useState } from 'react';
import { View, Text, StyleSheet, Pressable, ScrollView } from 'react-native';
import { router, Stack } from 'expo-router';
import { useVideoStore } from '@/stores/videoStore';
import { VideoFormat, MAX_VIDEO_SECONDS } from '@/types/video';
import { Property } from '@/types/property';
import { PropertyPicker } from '@/components/property/PropertyPicker';
import { theme } from '@/constants/themes';

const FORMAT_OPTIONS: {
  value: VideoFormat;
  title: string;
  platforms: string;
  description: string;
}[] = [
  {
    value: 'horizontal',
    title: 'Horizontal (16:9)',
    platforms: 'YouTube · Web',
    description: 'Ideal para la ficha del inmueble y YouTube.',
  },
  {
    value: 'vertical',
    title: 'Vertical (9:16)',
    platforms: 'Reels · TikTok · Shorts',
    description: 'Optimizado para redes sociales verticales.',
  },
];

export default function FormatSelectScreen() {
  const createStoryboard = useVideoStore((state) => state.createStoryboard);
  const [format, setFormat] = useState<VideoFormat | null>(null);

  const handleSelectProperty = (property: Property) => {
    if (!format) return;
    createStoryboard(property.id, format);
    router.push('/(app)/create-property/commercial-video/storyboard');
  };

  if (format === null) {
    return (
      <ScrollView style={styles.screen} contentContainerStyle={styles.content}>
        <Stack.Screen options={{ title: 'Video comercial' }} />
        <Text style={styles.heading}>Elige el formato del video</Text>
        <Text style={styles.subheading}>
          Máximo {MAX_VIDEO_SECONDS} segundos, con escenas guiadas y el logo de Colombia Sin Comisión.
        </Text>
        {FORMAT_OPTIONS.map((option) => (
          <Pressable
            key={option.value}
            style={({ pressed }) => [styles.card, pressed && styles.cardPressed]}
            onPress={() => setFormat(option.value)}
            accessibilityRole="button"
          >
            <Text style={styles.cardTitle}>{option.title}</Text>
            <Text style={styles.cardPlatforms}>{option.platforms}</Text>
            <Text style={styles.cardDescription}>{option.description}</Text>
          </Pressable>
        ))}
      </ScrollView>
    );
  }

  return (
    <View style={styles.screen}>
      <Stack.Screen options={{ title: 'Video comercial' }} />
      <View style={styles.pickerHeader}>
        <Text style={styles.heading}>Elige el inmueble</Text>
        <Text style={styles.subheading}>El video se asociará al inmueble que elijas.</Text>
      </View>
      <View style={styles.pickerBody}>
        <PropertyPicker onSelect={handleSelectProperty} />
      </View>
      <Pressable style={styles.backLink} onPress={() => setFormat(null)}>
        <Text style={styles.backLinkText}>Cambiar formato</Text>
      </Pressable>
    </View>
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
  pickerHeader: {
    paddingHorizontal: 20,
    paddingTop: 20,
  },
  pickerBody: {
    flex: 1,
    paddingHorizontal: 20,
  },
  heading: {
    fontSize: 22,
    fontWeight: '700',
    color: theme.colors.ink,
    marginBottom: 6,
  },
  subheading: {
    fontSize: 15,
    color: theme.colors.muted,
    lineHeight: 22,
    marginBottom: 20,
  },
  card: {
    backgroundColor: theme.colors.white,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: theme.colors.border,
    padding: 18,
    marginBottom: 14,
  },
  cardPressed: {
    borderColor: theme.colors.teal,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: theme.colors.ink,
  },
  cardPlatforms: {
    fontSize: 13,
    fontWeight: '700',
    color: theme.colors.teal,
    marginTop: 4,
    marginBottom: 6,
  },
  cardDescription: {
    fontSize: 14,
    color: theme.colors.muted,
    lineHeight: 20,
  },
  backLink: {
    alignItems: 'center',
    paddingVertical: 14,
  },
  backLinkText: {
    color: theme.colors.teal,
    fontWeight: '700',
  },
});
