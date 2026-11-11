import React from 'react';
import { View, Text, Image, Pressable, StyleSheet } from 'react-native';
import { router } from 'expo-router';
import { Property } from '@/types/property';
import { theme } from '@/constants/themes';
import { ACCENTS, Accent } from '@/constants/site';
import { formatCOP, getCoverUrl, getLocationLabel } from '@/utils/format';

interface PropertyCardProps {
  property: Property;
  accent?: Accent;
  width?: number | `${number}%`;
}

export function PropertyCard({ property, accent = 'teal', width = '100%' }: PropertyCardProps) {
  const cover = getCoverUrl(property);
  const operation =
    property.listing_type === 'alquiler'
      ? { label: 'En alquiler', style: styles.rental, textStyle: styles.rentalText }
      : property.listing_type === 'venta'
        ? { label: 'En venta', style: styles.sale, textStyle: styles.saleText }
        : null;
  const facts = [
    property.bedrooms !== null ? `${property.bedrooms} hab.` : null,
    property.bathrooms !== null ? `${property.bathrooms} baños` : null,
    property.parking_spaces !== null ? `${property.parking_spaces} garajes` : null,
    property.built_area_m2 !== null ? `${formatCOP(property.built_area_m2)} m²` : null,
  ].filter(Boolean);

  return (
    <Pressable
      onPress={() => router.push(`/(app)/properties/${property.id}`)}
      style={[styles.card, { width, borderTopColor: ACCENTS[accent].border }]}
      accessibilityRole="link"
    >
      {cover ? (
        <Image source={{ uri: cover }} style={styles.image} resizeMode="cover" />
      ) : (
        <View style={[styles.image, styles.imagePlaceholder]}>
          <Text style={styles.placeholderText}>Sin fotografía</Text>
        </View>
      )}

      <View style={styles.details}>
        <Text style={styles.type}>{property.property_type}</Text>

        <View style={styles.badges}>
          {operation ? (
            <View style={[styles.badge, operation.style]}>
              <Text style={[styles.badgeText, operation.textStyle]}>{operation.label}</Text>
            </View>
          ) : (
            <View style={[styles.badge, styles.neutral]}>
              <Text style={[styles.badgeText, styles.neutralText]}>Operación sin especificar</Text>
            </View>
          )}
          <View style={[styles.badge, property.is_verified ? styles.verified : styles.neutral]}>
            <Text
              style={[styles.badgeText, property.is_verified ? styles.verifiedText : styles.neutralText]}
            >
              {property.is_verified ? '✓ Verificado' : 'No verificada'}
            </Text>
          </View>
        </View>

        <Text style={styles.title} numberOfLines={2}>
          {property.title}
        </Text>
        <Text style={styles.location}>{getLocationLabel(property)}</Text>
        {property.identification_code ? (
          <Text style={styles.location}>Código: {property.identification_code}</Text>
        ) : null}
        {facts.length > 0 ? <Text style={styles.facts}>{facts.join('  ·  ')}</Text> : null}

        <View style={styles.bottom}>
          <Text style={styles.price}>
            ${formatCOP(property.price)}
            {property.listing_type === 'alquiler' ? ' / mes' : ''}
          </Text>
          <Text style={styles.link}>Ver inmueble →</Text>
        </View>
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: theme.colors.white,
    borderRadius: theme.radius.card,
    borderWidth: 1,
    borderColor: theme.colors.border,
    borderTopWidth: 6,
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 8,
    elevation: 2,
  },
  image: {
    width: '100%',
    height: 220,
    backgroundColor: '#eee',
  },
  imagePlaceholder: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  placeholderText: {
    color: theme.colors.muted,
    fontSize: 13,
  },
  details: {
    padding: 16,
  },
  type: {
    color: theme.colors.tealHover,
    fontSize: 11,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.6,
    marginBottom: 6,
  },
  badges: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginBottom: 8,
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
  sale: { backgroundColor: '#eff6ff' },
  saleText: { color: '#1d4ed8' },
  rental: { backgroundColor: '#ecfdf5' },
  rentalText: { color: '#047857' },
  verified: { backgroundColor: '#eff6ff' },
  verifiedText: { color: '#1d4ed8' },
  neutral: { backgroundColor: '#f1f5f9' },
  neutralText: { color: '#475569' },
  title: {
    fontSize: 17,
    fontWeight: '700',
    color: theme.colors.ink,
    marginBottom: 4,
  },
  location: {
    fontSize: 13,
    color: '#777',
  },
  facts: {
    fontSize: 13,
    color: theme.colors.muted,
    marginTop: 6,
  },
  bottom: {
    marginTop: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    flexWrap: 'wrap',
    gap: 8,
  },
  price: {
    fontSize: 18,
    fontWeight: '800',
    color: '#292929',
  },
  link: {
    color: theme.colors.tealHover,
    fontWeight: '700',
    fontSize: 14,
  },
});
