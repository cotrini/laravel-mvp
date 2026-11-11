import React, { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { Stack, useLocalSearchParams } from 'expo-router';
import { propertyService } from '@/services/property.service';
import { PaginationMeta, Property, PropertyFilters } from '@/types/property';
import { PropertyCard } from '@/components/property/PropertyCard';
import { LISTING_TYPE_OPTIONS, PROPERTY_TYPE_OPTIONS } from '@/constants/site';
import { theme } from '@/constants/themes';

interface Filters {
  destination: string;
  property_type: string;
  listing_type: string;
  min_price: string;
  max_price: string;
  bedrooms: string;
  bathrooms: string;
}

const EMPTY_FILTERS: Filters = {
  destination: '',
  property_type: '',
  listing_type: '',
  min_price: '',
  max_price: '',
  bedrooms: '',
  bathrooms: '',
};

function firstParam(value?: string | string[]): string {
  return Array.isArray(value) ? (value[0] ?? '') : (value ?? '');
}

function toApiFilters(filters: Filters): PropertyFilters {
  const api: PropertyFilters = {};
  if (filters.destination.trim()) api.destination = filters.destination.trim();
  if (filters.property_type) api.property_type = filters.property_type;
  if (filters.listing_type === 'venta' || filters.listing_type === 'alquiler') {
    api.listing_type = filters.listing_type;
  }
  if (filters.min_price) api.min_price = Number(filters.min_price);
  if (filters.max_price) api.max_price = Number(filters.max_price);
  if (filters.bedrooms) api.bedrooms = Number(filters.bedrooms);
  if (filters.bathrooms) api.bathrooms = Number(filters.bathrooms);
  return api;
}

function digitsOnly(value: string): string {
  return value.replace(/\D/g, '');
}

export default function PropertiesScreen() {
  const params = useLocalSearchParams<{
    destination?: string;
    property_type?: string;
    listing_type?: string;
  }>();

  const initial: Filters = {
    ...EMPTY_FILTERS,
    destination: firstParam(params.destination),
    property_type: firstParam(params.property_type),
    listing_type: firstParam(params.listing_type),
  };

  const [draft, setDraft] = useState<Filters>(initial);
  const [applied, setApplied] = useState<Filters>(initial);
  const [items, setItems] = useState<Property[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async (filters: Filters, page: number) => {
    setLoading(true);
    setError(null);
    try {
      const result = await propertyService.getProperties({ ...toApiFilters(filters), page });
      setItems((prev) => (page === 1 ? result.data : [...prev, ...result.data]));
      setMeta(result.meta);
    } catch {
      setError('No pudimos cargar los inmuebles. Verifica tu conexión.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load(applied, 1);
  }, [applied, load]);

  const applySearch = () => setApplied(draft);

  const clearFilters = () => {
    setDraft(EMPTY_FILTERS);
    setApplied(EMPTY_FILTERS);
  };

  const loadMore = () => {
    if (!loading && meta && meta.current_page < meta.last_page) {
      load(applied, meta.current_page + 1);
    }
  };

  const title =
    applied.listing_type
      ? `Inmuebles en ${applied.listing_type}${applied.destination ? ` en ${applied.destination}` : ''}`
      : applied.destination
        ? `Inmuebles en ${applied.destination}`
        : 'Encuentra tu próximo inmueble';

  const renderHeader = () => (
    <View>
      <Text style={styles.heading}>{title}</Text>

      <View style={styles.panel}>
        <Text style={styles.label}>Ubicación</Text>
        <TextInput
          style={styles.input}
          placeholder="Ej. Manizales, Chipre"
          placeholderTextColor={theme.colors.muted}
          value={draft.destination}
          onChangeText={(destination) => setDraft({ ...draft, destination })}
          onSubmitEditing={applySearch}
          returnKeyType="search"
        />

        <Text style={styles.label}>Tipo de inmueble</Text>
        <ChipRow
          options={PROPERTY_TYPE_OPTIONS}
          selected={draft.property_type}
          onSelect={(property_type) => setDraft({ ...draft, property_type })}
        />

        <Text style={styles.label}>Operación</Text>
        <ChipRow
          options={LISTING_TYPE_OPTIONS}
          selected={draft.listing_type}
          onSelect={(listing_type) => setDraft({ ...draft, listing_type })}
        />

        <View style={styles.row}>
          <View style={styles.half}>
            <Text style={styles.label}>Precio desde</Text>
            <TextInput
              style={styles.input}
              placeholder="Desde"
              placeholderTextColor={theme.colors.muted}
              keyboardType="numeric"
              value={draft.min_price}
              onChangeText={(min_price) => setDraft({ ...draft, min_price: digitsOnly(min_price) })}
            />
          </View>
          <View style={styles.half}>
            <Text style={styles.label}>Precio hasta</Text>
            <TextInput
              style={styles.input}
              placeholder="Hasta"
              placeholderTextColor={theme.colors.muted}
              keyboardType="numeric"
              value={draft.max_price}
              onChangeText={(max_price) => setDraft({ ...draft, max_price: digitsOnly(max_price) })}
            />
          </View>
        </View>

        <View style={styles.row}>
          <View style={styles.half}>
            <Text style={styles.label}>Habitaciones</Text>
            <TextInput
              style={styles.input}
              placeholder="Cualquiera"
              placeholderTextColor={theme.colors.muted}
              keyboardType="numeric"
              value={draft.bedrooms}
              onChangeText={(bedrooms) => setDraft({ ...draft, bedrooms: digitsOnly(bedrooms) })}
            />
          </View>
          <View style={styles.half}>
            <Text style={styles.label}>Baños</Text>
            <TextInput
              style={styles.input}
              placeholder="Cualquiera"
              placeholderTextColor={theme.colors.muted}
              keyboardType="numeric"
              value={draft.bathrooms}
              onChangeText={(bathrooms) => setDraft({ ...draft, bathrooms: digitsOnly(bathrooms) })}
            />
          </View>
        </View>

        <View style={styles.actions}>
          <Pressable style={styles.searchButton} onPress={applySearch} accessibilityRole="button">
            <Text style={styles.searchButtonText}>Buscar</Text>
          </Pressable>
          <Pressable onPress={clearFilters} accessibilityRole="button">
            <Text style={styles.clearText}>Limpiar filtros</Text>
          </Pressable>
        </View>
      </View>

      {meta ? (
        <Text style={styles.count}>
          {meta.total} {meta.total === 1 ? 'inmueble encontrado' : 'inmuebles encontrados'}
        </Text>
      ) : null}
      {error ? <Text style={styles.error}>{error}</Text> : null}
    </View>
  );

  const renderEmpty = () => {
    if (loading) return <ActivityIndicator style={styles.loader} color={theme.colors.teal} />;
    return (
      <View style={styles.empty}>
        <Text style={styles.emptyTitle}>Sin resultados</Text>
        <Text style={styles.emptyText}>No encontramos inmuebles con estos filtros.</Text>
      </View>
    );
  };

  return (
    <View style={styles.screen}>
      <Stack.Screen options={{ title: 'Inmuebles' }} />
      <FlatList
        data={items}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => (
          <View style={styles.cardWrap}>
            <PropertyCard property={item} />
          </View>
        )}
        ListHeaderComponent={renderHeader}
        ListEmptyComponent={renderEmpty}
        ListFooterComponent={
          loading && items.length > 0 ? (
            <ActivityIndicator style={styles.loader} color={theme.colors.teal} />
          ) : undefined
        }
        onEndReached={loadMore}
        onEndReachedThreshold={0.4}
        contentContainerStyle={styles.content}
      />
    </View>
  );
}

function ChipRow({
  options,
  selected,
  onSelect,
}: {
  options: { value: string; label: string }[];
  selected: string;
  onSelect: (value: string) => void;
}) {
  const all = [{ value: '', label: 'Todos' }, ...options];
  return (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.chips}>
      {all.map((option) => {
        const active = selected === option.value;
        return (
          <Pressable
            key={option.value || 'all'}
            onPress={() => onSelect(option.value)}
            style={[styles.chip, active && styles.chipActive]}
            accessibilityRole="button"
          >
            <Text style={[styles.chipText, active && styles.chipTextActive]}>{option.label}</Text>
          </Pressable>
        );
      })}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: theme.colors.pageBg,
  },
  content: {
    padding: 16,
    paddingBottom: 40,
    gap: 16,
  },
  heading: {
    fontSize: 24,
    fontWeight: '800',
    color: theme.colors.ink,
    marginBottom: 12,
  },
  panel: {
    backgroundColor: theme.colors.white,
    borderRadius: theme.radius.section,
    borderWidth: 1,
    borderColor: theme.colors.border,
    padding: 16,
  },
  label: {
    fontSize: 13,
    fontWeight: '700',
    color: theme.colors.ink,
    marginTop: 12,
    marginBottom: 6,
  },
  input: {
    borderWidth: 1,
    borderColor: '#d8dce2',
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 15,
    color: theme.colors.ink,
    backgroundColor: theme.colors.white,
  },
  row: {
    flexDirection: 'row',
    gap: 12,
  },
  half: {
    flex: 1,
  },
  chips: {
    flexGrow: 0,
  },
  chip: {
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: theme.radius.pill,
    borderWidth: 1,
    borderColor: '#d8dce2',
    marginRight: 8,
    backgroundColor: theme.colors.white,
  },
  chipActive: {
    backgroundColor: theme.colors.ink,
    borderColor: theme.colors.ink,
  },
  chipText: {
    fontSize: 13,
    fontWeight: '600',
    color: theme.colors.ink,
  },
  chipTextActive: {
    color: theme.colors.white,
  },
  actions: {
    marginTop: 18,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  searchButton: {
    backgroundColor: theme.colors.yellowButton,
    paddingHorizontal: 22,
    paddingVertical: 12,
    borderRadius: 10,
  },
  searchButtonText: {
    color: theme.colors.ink,
    fontWeight: '800',
    fontSize: 15,
  },
  clearText: {
    color: theme.colors.tealHover,
    fontWeight: '700',
    fontSize: 14,
  },
  count: {
    color: theme.colors.muted,
    fontSize: 14,
    marginTop: 4,
  },
  error: {
    color: theme.colors.danger,
    marginTop: 8,
  },
  cardWrap: {
    marginTop: 4,
  },
  loader: {
    marginVertical: 24,
  },
  empty: {
    alignItems: 'center',
    paddingVertical: 40,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: theme.colors.ink,
    marginBottom: 6,
  },
  emptyText: {
    color: theme.colors.muted,
    textAlign: 'center',
  },
});
