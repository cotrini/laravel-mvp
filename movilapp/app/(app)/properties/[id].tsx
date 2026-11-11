import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  Linking,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  useWindowDimensions,
  View,
} from 'react-native';
import { Stack, useLocalSearchParams } from 'expo-router';
import { propertyService } from '@/services/property.service';
import { Property } from '@/types/property';
import { theme } from '@/constants/themes';
import { WHATSAPP_NUMBER } from '@/constants/site';
import { formatCOP, getLocationLabel } from '@/utils/format';

export default function PropertyDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { width } = useWindowDimensions();
  const [property, setProperty] = useState<Property | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let active = true;
    setLoading(true);
    setError(null);
    propertyService
      .getProperty(Number(id))
      .then((data) => active && setProperty(data))
      .catch(() => active && setError('No pudimos cargar este inmueble.'))
      .finally(() => active && setLoading(false));
    return () => {
      active = false;
    };
  }, [id]);

  if (loading) {
    return (
      <View style={styles.center}>
        <Stack.Screen options={{ title: 'Inmueble' }} />
        <ActivityIndicator color={theme.colors.teal} />
      </View>
    );
  }

  if (error || !property) {
    return (
      <View style={styles.center}>
        <Stack.Screen options={{ title: 'Inmueble' }} />
        <Text style={styles.error}>{error ?? 'Inmueble no encontrado.'}</Text>
      </View>
    );
  }

  const images = property.media.filter((m) => m.type === 'image');
  const operation =
    property.listing_type === 'alquiler'
      ? 'En alquiler'
      : property.listing_type === 'venta'
        ? 'En venta'
        : 'Operación sin especificar';

  const highlights = [
    property.bedrooms !== null ? { label: 'Habitaciones', value: String(property.bedrooms) } : null,
    property.bathrooms !== null ? { label: 'Baños', value: String(property.bathrooms) } : null,
    property.parking_spaces !== null ? { label: 'Garajes', value: String(property.parking_spaces) } : null,
    property.built_area_m2 !== null
      ? { label: 'Área construida', value: `${formatCOP(property.built_area_m2)} m²` }
      : null,
  ].filter(Boolean) as { label: string; value: string }[];

  const facts = [
    { label: 'Tipo de inmueble', value: capitalize(property.property_type) },
    { label: 'Tipo de operación', value: operation },
    { label: 'Estado', value: property.status === 'active' ? 'Disponible' : capitalize(property.status) },
    { label: 'Barrio o sector', value: property.neighborhood },
    { label: 'Dirección', value: property.address },
    { label: 'Ciudad y departamento', value: [property.city, property.department].filter(Boolean).join(', ') },
    { label: 'Baños completos', value: property.bathrooms },
    { label: 'Medios baños', value: property.half_bathrooms },
    { label: 'Área del lote', value: property.lot_area_m2 !== null ? `${formatCOP(property.lot_area_m2)} m²` : null },
    { label: 'Piso', value: property.floor_number },
    { label: 'Niveles del inmueble', value: property.total_floors },
    { label: 'Estrato', value: property.stratum },
    { label: 'Año de construcción', value: property.year_built },
    {
      label: 'Administración',
      value: property.administration_fee !== null ? `$${formatCOP(property.administration_fee)} COP` : null,
    },
    { label: 'Amoblado', value: property.is_furnished ? 'Sí' : 'No' },
    { label: 'Ascensor', value: property.has_elevator ? 'Sí' : 'No' },
  ].filter((fact) => fact.value !== null && fact.value !== undefined && fact.value !== '');

  const whatsappText = encodeURIComponent(
    `Hola, me interesa el inmueble ${property.identification_code ?? ''} - ${property.title}`.trim(),
  );
  const openWhatsApp = () => Linking.openURL(`https://wa.me/${WHATSAPP_NUMBER}?text=${whatsappText}`);

  return (
    <ScrollView style={styles.screen} contentContainerStyle={styles.content}>
      <Stack.Screen options={{ title: property.title }} />

      {images.length > 0 ? (
        <ScrollView horizontal pagingEnabled showsHorizontalScrollIndicator={false}>
          {images.map((image) => (
            <Image
              key={image.id}
              source={{ uri: image.url }}
              style={{ width, height: 260 }}
              resizeMode="cover"
            />
          ))}
        </ScrollView>
      ) : (
        <View style={[styles.noImage, { width }]}>
          <Text style={styles.muted}>Sin fotografías</Text>
        </View>
      )}

      <View style={styles.section}>
        <View style={styles.badges}>
          <View style={[styles.badge, styles.badgeBlue]}>
            <Text style={[styles.badgeText, styles.badgeBlueText]}>{operation}</Text>
          </View>
          <View style={[styles.badge, property.is_verified ? styles.badgeBlue : styles.badgeGray]}>
            <Text style={[styles.badgeText, property.is_verified ? styles.badgeBlueText : styles.badgeGrayText]}>
              {property.is_verified ? '✓ Verificado' : 'No verificada'}
            </Text>
          </View>
        </View>

        <Text style={styles.type}>{capitalize(property.property_type)}</Text>
        <Text style={styles.title}>{property.title}</Text>
        <Text style={styles.location}>{getLocationLabel(property)}</Text>
        {property.identification_code ? (
          <Text style={styles.location}>Código: {property.identification_code}</Text>
        ) : null}

        <Text style={styles.price}>
          ${formatCOP(property.price)}
          {property.listing_type === 'alquiler' ? ' / mes' : ''}
        </Text>
      </View>

      {highlights.length > 0 ? (
        <View style={styles.highlights}>
          {highlights.map((item) => (
            <View key={item.label} style={styles.highlight}>
              <Text style={styles.highlightValue}>{item.value}</Text>
              <Text style={styles.highlightLabel}>{item.label}</Text>
            </View>
          ))}
        </View>
      ) : null}

      <View style={styles.section}>
        <Text style={styles.subheading}>Descripción</Text>
        <Text style={styles.body}>{property.description || 'Sin descripción.'}</Text>
      </View>

      {property.tour ? (
        <View style={styles.section}>
          <Text style={styles.subheading}>Recorrido virtual 360°</Text>
          <Text style={styles.body}>{property.tour.title}</Text>
        </View>
      ) : null}

      {facts.length > 0 ? (
        <View style={styles.section}>
          <Text style={styles.subheading}>Características</Text>
          {facts.map((fact) => (
            <View key={fact.label} style={styles.factRow}>
              <Text style={styles.factLabel}>{fact.label}</Text>
              <Text style={styles.factValue}>{String(fact.value)}</Text>
            </View>
          ))}
        </View>
      ) : null}

      <View style={styles.section}>
        <Text style={styles.subheading}>¿Te interesa?</Text>
        <Text style={styles.body}>Escríbenos por WhatsApp y te asesoramos sin comisión.</Text>
        <Pressable style={styles.whatsapp} onPress={openWhatsApp} accessibilityRole="button">
          <Text style={styles.whatsappText}>Contactar por WhatsApp</Text>
        </Pressable>
      </View>
    </ScrollView>
  );
}

function capitalize(value: string | null | undefined): string {
  if (!value) return '';
  return value.charAt(0).toUpperCase() + value.slice(1);
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: theme.colors.pageBg,
  },
  content: {
    paddingBottom: 40,
  },
  center: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: theme.colors.pageBg,
    padding: 24,
  },
  error: {
    color: theme.colors.danger,
    textAlign: 'center',
  },
  muted: {
    color: theme.colors.muted,
  },
  noImage: {
    height: 220,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#eee',
  },
  section: {
    backgroundColor: theme.colors.white,
    marginTop: 12,
    marginHorizontal: 12,
    borderRadius: theme.radius.section,
    padding: 18,
    borderWidth: 1,
    borderColor: theme.colors.border,
  },
  badges: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginBottom: 10,
  },
  badge: {
    borderRadius: theme.radius.pill,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  badgeText: {
    fontSize: 12,
    fontWeight: '700',
  },
  badgeBlue: { backgroundColor: '#eff6ff' },
  badgeBlueText: { color: '#1d4ed8' },
  badgeGray: { backgroundColor: '#f1f5f9' },
  badgeGrayText: { color: '#475569' },
  type: {
    color: theme.colors.tealHover,
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.6,
    marginBottom: 4,
  },
  title: {
    fontSize: 22,
    fontWeight: '800',
    color: theme.colors.ink,
    marginBottom: 6,
  },
  location: {
    fontSize: 14,
    color: '#777',
  },
  price: {
    fontSize: 24,
    fontWeight: '800',
    color: theme.colors.ink,
    marginTop: 12,
  },
  highlights: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginTop: 12,
    marginHorizontal: 12,
  },
  highlight: {
    flexGrow: 1,
    flexBasis: '45%',
    backgroundColor: theme.colors.white,
    borderRadius: 12,
    padding: 14,
    borderWidth: 1,
    borderColor: theme.colors.border,
  },
  highlightValue: {
    fontSize: 18,
    fontWeight: '800',
    color: theme.colors.ink,
  },
  highlightLabel: {
    fontSize: 12,
    color: theme.colors.muted,
    marginTop: 2,
  },
  subheading: {
    fontSize: 17,
    fontWeight: '800',
    color: theme.colors.ink,
    marginBottom: 8,
  },
  body: {
    fontSize: 15,
    lineHeight: 22,
    color: '#333',
  },
  factRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: theme.colors.border,
    gap: 12,
  },
  factLabel: {
    color: theme.colors.muted,
    fontSize: 14,
    flexShrink: 1,
  },
  factValue: {
    color: theme.colors.ink,
    fontSize: 14,
    fontWeight: '600',
    textAlign: 'right',
    flexShrink: 1,
  },
  whatsapp: {
    marginTop: 14,
    backgroundColor: theme.colors.yellowButton,
    borderRadius: 10,
    paddingVertical: 14,
    alignItems: 'center',
  },
  whatsappText: {
    color: theme.colors.ink,
    fontWeight: '800',
    fontSize: 16,
  },
});
