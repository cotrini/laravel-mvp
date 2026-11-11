import React from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';

interface AdvisoryAction {
  label: string;
  onPress: () => void;
}

interface AdvisoryCardProps {
  eyebrow: string;
  title: string;
  description: string;
  backgroundColor: string;
  photoUri: string;
  photoLabel: string;
  actions: AdvisoryAction[];
  wide?: boolean;
}

// Tarjeta de asesoría con el mismo estilo de backend/resources/views/welcome.blade.php (.advisory-card).
export function AdvisoryCard({
  eyebrow,
  title,
  description,
  backgroundColor,
  photoUri,
  photoLabel,
  actions,
  wide = false,
}: AdvisoryCardProps) {
  return (
    <View style={[styles.card, { backgroundColor }, wide && styles.cardWide]}>
      {wide ? null : (
        <Image source={{ uri: photoUri }} accessibilityLabel={photoLabel} style={styles.photo} resizeMode="cover" />
      )}

      <View style={styles.copy}>
        <Text style={styles.eyebrow}>{eyebrow}</Text>
        <Text style={styles.title}>{title}</Text>
        <Text style={styles.description}>{description}</Text>

        <View style={styles.actions}>
          {actions.map((action) => (
            <Pressable
              key={action.label}
              onPress={action.onPress}
              accessibilityRole="link"
              style={({ pressed }) => [styles.button, pressed && styles.buttonPressed]}
            >
              <Text style={styles.buttonText}>→ {action.label}</Text>
            </Pressable>
          ))}
        </View>
      </View>

      {wide ? (
        <Image source={{ uri: photoUri }} accessibilityLabel={photoLabel} style={styles.photoWide} resizeMode="cover" />
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    borderRadius: 20,
    overflow: 'hidden',
  },
  cardWide: {
    flex: 1,
    flexDirection: 'row',
    minHeight: 282,
  },
  copy: {
    paddingVertical: 27,
    paddingHorizontal: 24,
    flex: 1,
    justifyContent: 'center',
  },
  eyebrow: {
    color: 'rgba(36, 36, 36, 0.68)',
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 1.1,
    textTransform: 'uppercase',
    marginBottom: 8,
  },
  title: {
    color: '#292929',
    fontSize: 24,
    fontWeight: '800',
    letterSpacing: -0.6,
    lineHeight: 27,
  },
  description: {
    color: '#514c46',
    fontSize: 14,
    lineHeight: 21,
    marginTop: 13,
    marginBottom: 18,
  },
  actions: {
    alignItems: 'flex-start',
    gap: 10,
  },
  button: {
    minHeight: 43,
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 17,
    borderRadius: 999,
    backgroundColor: '#292929',
  },
  buttonPressed: {
    backgroundColor: '#444',
  },
  buttonText: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '700',
  },
  photo: {
    width: '100%',
    height: 180,
  },
  photoWide: {
    flex: 0.9,
    minHeight: 282,
  },
});
