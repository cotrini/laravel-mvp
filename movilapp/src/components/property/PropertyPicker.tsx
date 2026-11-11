import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, Pressable, FlatList, ActivityIndicator } from 'react-native';
import { propertyService } from '@/services/property.service';
import { Property } from '@/types/property';
import { theme } from '@/constants/themes';

interface PropertyPickerProps {
  onSelect: (property: Property) => void;
}

// Lista los inmuebles del usuario (GET /my/properties). El backend exige property_id para 360 y video.
export function PropertyPicker({ onSelect }: PropertyPickerProps) {
  const [items, setItems] = useState<Property[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    propertyService
      .getMyProperties()
      .then((list) => setItems(list.data))
      .catch(() => setError('No pudimos cargar tus inmuebles.'))
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return <ActivityIndicator style={styles.loader} color={theme.colors.teal} />;
  }

  if (error) {
    return <Text style={styles.empty}>{error}</Text>;
  }

  if (items.length === 0) {
    return <Text style={styles.empty}>Aún no tienes inmuebles publicados en tu cuenta.</Text>;
  }

  return (
    <FlatList
      data={items}
      keyExtractor={(item) => String(item.id)}
      contentContainerStyle={styles.list}
      renderItem={({ item }) => (
        <Pressable
          style={({ pressed }) => [styles.row, pressed && styles.rowPressed]}
          onPress={() => onSelect(item)}
          accessibilityRole="button"
        >
          <Text style={styles.title} numberOfLines={1}>
            {item.title}
          </Text>
          <Text style={styles.action}>Elegir →</Text>
        </Pressable>
      )}
    />
  );
}

const styles = StyleSheet.create({
  loader: {
    marginTop: 24,
  },
  empty: {
    color: theme.colors.muted,
    textAlign: 'center',
    marginTop: 24,
    lineHeight: 22,
  },
  list: {
    gap: 10,
    paddingBottom: 24,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: theme.colors.white,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: theme.colors.border,
    padding: 16,
  },
  rowPressed: {
    borderColor: theme.colors.teal,
  },
  title: {
    flex: 1,
    fontSize: 15,
    fontWeight: '600',
    color: theme.colors.ink,
    marginRight: 12,
  },
  action: {
    color: theme.colors.teal,
    fontWeight: '700',
  },
});
