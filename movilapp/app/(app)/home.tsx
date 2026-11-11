import React, { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  Linking,
  Pressable,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  useWindowDimensions,
  View,
} from 'react-native';
import { router } from 'expo-router';
import { useAuth } from '@/hooks/useAuth';
import { propertyService } from '@/services/property.service';
import { Property } from '@/types/property';
import { theme } from '@/constants/themes';
import {
  ACCENTS,
  LISTING_TYPE_OPTIONS,
  PROPERTY_CATEGORIES,
  PROPERTY_TYPE_OPTIONS,
  PropertyCategory,
  WHATSAPP_MESSAGES,
  WHATSAPP_NUMBER,
} from '@/constants/site';
import { PropertyCard } from '@/components/property/PropertyCard';
import { getCoverUrl, getLocationLabel } from '@/utils/format';
import { BrandLogo } from '@/components/common/BrandLogo';
import { CategoryIcon, CategoryIconName } from '@/components/common/CategoryIcon';
import { AdvisoryCard } from '@/components/home/AdvisoryCard';
import { API_ORIGIN, ADVISORY_PHOTOS } from '@/constants/api.config';

interface CategorySection {
  category: PropertyCategory;
  properties: Property[];
}

// Igual que HomeController: "comercial" agrupa también "local".
// La API compara property_type por igualdad, así que se consulta cada tipo y se unen.
async function fetchCategory(category: PropertyCategory): Promise<Property[]> {
  const pages = await Promise.all(
    category.types.map((type) => propertyService.getProperties({ property_type: type, per_page: 3 })),
  );
  return pages
    .flatMap((page) => page.data)
    .sort((a, b) => b.id - a.id)
    .slice(0, 3);
}

function openWhatsApp(message: string) {
  Linking.openURL(`https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(message)}`);
}

export default function HomeScreen() {
  const { isAuthenticated, user, logout } = useAuth();
  const { width } = useWindowDimensions();
  const isWide = width >= 900;

  const [latest, setLatest] = useState<Property | null>(null);
  const [sections, setSections] = useState<CategorySection[]>([]);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState(false);

  const [destination, setDestination] = useState('');
  const [propertyType, setPropertyType] = useState('');
  const [listingType, setListingType] = useState('');

  const loadHome = useCallback(async () => {
    setLoading(true);
    setLoadError(false);

    const [latestResult, ...categoryResults] = await Promise.allSettled([
      propertyService.getProperties({ per_page: 1 }),
      ...PROPERTY_CATEGORIES.map(fetchCategory),
    ]);

    // Si todas las peticiones fallan (p. ej. API inalcanzable) se muestra el error, no "sin publicaciones".
    const allFailed = [latestResult, ...categoryResults].every((result) => result.status === 'rejected');
    if (allFailed) {
      setLoadError(true);
      setLoading(false);
      return;
    }

    if (latestResult.status === 'fulfilled') {
      setLatest(latestResult.value.data[0] ?? null);
    }

    const nextSections: CategorySection[] = [];
    categoryResults.forEach((result, index) => {
      if (result.status === 'fulfilled' && result.value.length > 0) {
        nextSections.push({ category: PROPERTY_CATEGORIES[index], properties: result.value });
      }
    });
    setSections(nextSections);
    setLoading(false);
  }, []);

  useEffect(() => {
    loadHome();
  }, [loadHome]);

  const search = () => {
    router.push({
      pathname: '/(app)/properties',
      params: {
        ...(destination.trim() ? { destination: destination.trim() } : {}),
        ...(propertyType ? { property_type: propertyType } : {}),
        ...(listingType ? { listing_type: listingType } : {}),
      },
    });
  };

  const latestCover = latest ? getCoverUrl(latest) : null;

  return (
    <SafeAreaView style={styles.safe}>
      <ScrollView contentContainerStyle={styles.scroll}>
        {/* Header */}
        <View style={styles.header}>
          <View style={styles.headerRow}>
            <View style={styles.brand}>
              <BrandLogo size={38} />
              <Text style={styles.brandText}>
                <Text style={styles.brandStrong}>Colombia </Text>
                <Text style={styles.brandTeal}>Sin Comisión</Text>
              </Text>
            </View>

            <View style={styles.headerActions}>
              {isAuthenticated ? (
                <Pressable onPress={logout} accessibilityRole="button">
                  <Text style={styles.headerLink}>Cerrar sesión</Text>
                </Pressable>
              ) : (
                <Pressable onPress={() => router.push('/(auth)/login')} accessibilityRole="link">
                  <Text style={styles.headerLink}>Iniciar sesión</Text>
                </Pressable>
              )}
              <Pressable
                style={styles.publishPill}
                onPress={() =>
                  isAuthenticated
                    ? openWhatsApp(WHATSAPP_MESSAGES.publish)
                    : router.push('/(auth)/login')
                }
                accessibilityRole="link"
              >
                <Text style={styles.publishPillText}>Publica tu inmueble</Text>
              </Pressable>
            </View>
          </View>

          {/* Category navigation */}
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.categoryNav}>
            <Pressable style={[styles.categoryItem, styles.categoryItemActive]} onPress={() => router.push('/(app)/properties')}>
              <CategoryIcon name="todos" color={theme.colors.tealHover} />
              <Text style={[styles.categoryText, styles.categoryTextActive]}>Todos</Text>
            </Pressable>
            {PROPERTY_CATEGORIES.map((category) => (
              <Pressable
                key={category.key}
                style={styles.categoryItem}
                onPress={() => router.push({ pathname: '/(app)/properties', params: { property_type: category.key } })}
              >
                <CategoryIcon name={category.key as CategoryIconName} />
                <Text style={styles.categoryText}>{category.label}</Text>
              </Pressable>
            ))}
          </ScrollView>
        </View>

        <View style={styles.container}>
          {/* Hero */}
          <View style={[styles.hero, isWide && styles.heroWide]}>
            <View style={[styles.heroCopy, isWide && styles.heroCopyWide]}>
              <Text style={styles.kicker}>Colombia Sin Comisión</Text>
              <Text style={[styles.heroTitle, isWide && styles.heroTitleWide]}>Un lugar para llamar hogar.</Text>
              <Text style={styles.heroDescription}>
                Encuentra tu próximo inmueble y conecta directamente con quien lo publica.
              </Text>
              <Text style={styles.heroNote}>Compra o arrienda sin intermediarios.</Text>
            </View>

            <View style={[styles.heroMedia, isWide && styles.heroMediaWide]}>
              {latest && latestCover ? (
                <Image source={{ uri: latestCover }} style={styles.heroImage} resizeMode="cover" />
              ) : (
                <View style={[styles.heroImage, styles.heroImageEmpty]} />
              )}
              <View style={styles.stamp}>
                <Text style={styles.stampTitle}>{latest ? latest.title : 'Tu próximo hogar te espera'}</Text>
                <Text style={styles.stampSub}>
                  {latest ? getLocationLabel(latest) : 'Explora propiedades en toda Colombia'}
                </Text>
              </View>
            </View>
          </View>

          {/* Search panel */}
          <View style={styles.searchPanel}>
            <Text style={styles.searchTitle}>Encuentra tu inmueble ideal</Text>

            <TextInput
              value={destination}
              onChangeText={setDestination}
              placeholder="Ciudad o barrio"
              placeholderTextColor={theme.colors.muted}
              style={styles.searchInput}
              returnKeyType="search"
              onSubmitEditing={search}
            />

            <Text style={styles.filterLabel}>Tipo de inmueble</Text>
            <ChipRow
              options={[{ value: '', label: 'Todos' }, ...PROPERTY_TYPE_OPTIONS]}
              selected={propertyType}
              onSelect={setPropertyType}
            />

            <Text style={styles.filterLabel}>Operación</Text>
            <ChipRow
              options={[{ value: '', label: 'Todas' }, ...LISTING_TYPE_OPTIONS]}
              selected={listingType}
              onSelect={setListingType}
            />

            <Pressable style={styles.searchButton} onPress={search} accessibilityRole="button">
              <Text style={styles.searchButtonText}>Buscar inmuebles</Text>
            </Pressable>
          </View>

          {/* Property sections */}
          {loading ? (
            <ActivityIndicator color={theme.colors.teal} style={styles.loader} />
          ) : loadError ? (
            <View style={styles.emptyBox}>
              <Text style={styles.emptyTitle}>No pudimos cargar los inmuebles. Verifica tu conexión.</Text>
              <Pressable onPress={loadHome}>
                <Text style={styles.allLink}>Reintentar</Text>
              </Pressable>
            </View>
          ) : sections.length === 0 ? (
            <View style={styles.emptyBox}>
              <Text style={styles.emptyTitle}>Pronto publicaremos nuevos inmuebles.</Text>
              <Pressable onPress={() => router.push('/(app)/properties')}>
                <Text style={styles.allLink}>Ver todas las publicaciones</Text>
              </Pressable>
            </View>
          ) : (
            sections.map(({ category, properties }) => {
              const accent = ACCENTS[category.accent];
              return (
                <View key={category.key} style={[styles.section, { backgroundColor: accent.background }]}>
                  <View style={styles.sectionHeading}>
                    <View style={styles.sectionHeadingText}>
                      <Text style={styles.sectionTitle}>{category.title}</Text>
                      <Text style={styles.sectionDescription}>
                        Las publicaciones más recientes de esta categoría.
                      </Text>
                    </View>
                    <Pressable
                      onPress={() => router.push({ pathname: '/(app)/properties', params: { property_type: category.key } })}
                    >
                      <Text style={styles.allLink}>Ver más</Text>
                    </Pressable>
                  </View>

                  <View style={styles.grid}>
                    {properties.map((property) => (
                      <PropertyCard
                        key={property.id}
                        property={property}
                        accent={category.accent}
                        width={isWide ? '31%' : '100%'}
                      />
                    ))}
                  </View>
                </View>
              );
            })
          )}

          {/* Advisory */}
          <View style={styles.advisory}>
            <Text style={styles.advisoryTitle}>Da el siguiente paso hacia tu nuevo hogar</Text>
            <Text style={styles.advisorySubtitle}>Te acompañamos con asesoría y servicios para tu inmueble.</Text>

            <View style={[styles.advisoryGrid, isWide && styles.advisoryGridWide]}>
              <AdvisoryCard
                wide={isWide}
                eyebrow="Asesoría financiera"
                title="Haz realidad tu próximo hogar"
                description="Conoce opciones de financiación y recibe orientación para solicitar un préstamo bancario para tu inmueble."
                backgroundColor="#ffe77d"
                photoUri={ADVISORY_PHOTOS.finance}
                photoLabel="Asesoría para financiar la compra de un inmueble"
                actions={[
                  { label: 'Pedir asesoría', onPress: () => openWhatsApp(WHATSAPP_MESSAGES.financing) },
                  { label: 'Simulador de crédito', onPress: () => Linking.openURL(`${API_ORIGIN}/simulador-credito`) },
                ]}
              />
              <AdvisoryCard
                wide={isWide}
                eyebrow="Publica con nosotros"
                title="Tu inmueble merece brillar"
                description="Te asesoramos para publicar y crear fotos, videos y recorridos 360° que destaquen tu inmueble."
                backgroundColor="#cfb8e9"
                photoUri={ADVISORY_PHOTOS.publish}
                photoLabel="Servicio de fotos, videos y recorridos 360 para inmuebles"
                actions={[{ label: 'Publica con nosotros', onPress: () => openWhatsApp(WHATSAPP_MESSAGES.publish) }]}
              />
            </View>
          </View>

          {/* Herramientas de publicación: solo para usuarios autenticados */}
          {isAuthenticated ? (
            <View style={styles.tools}>
              <Text style={styles.toolsTitle}>Herramientas para {user?.name ?? 'tu cuenta'}</Text>
              <View style={[styles.advisoryGrid, isWide && styles.advisoryGridWide]}>
                <Pressable
                  style={styles.toolCard}
                  onPress={() => router.push('/(app)/create-property/360-guided')}
                >
                  <Text style={styles.toolTitle}>Captura 360° guiada</Text>
                  <Text style={styles.toolText}>Captura una panorámica con los sensores de tu dispositivo.</Text>
                </Pressable>
                <Pressable
                  style={styles.toolCard}
                  onPress={() => router.push('/(app)/create-property/commercial-video/format-select')}
                >
                  <Text style={styles.toolTitle}>Video comercial</Text>
                  <Text style={styles.toolText}>Arma un video de máximo 40 segundos por escenas.</Text>
                </Pressable>
              </View>
            </View>
          ) : null}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

interface ChipRowProps {
  options: { value: string; label: string }[];
  selected: string;
  onSelect: (value: string) => void;
}

function ChipRow({ options, selected, onSelect }: ChipRowProps) {
  return (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.chipRow}>
      {options.map((option) => {
        const active = option.value === selected;
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
  safe: {
    flex: 1,
    backgroundColor: theme.colors.pageBg,
  },
  scroll: {
    paddingBottom: 48,
  },
  container: {
    maxWidth: 1320,
    width: '100%',
    alignSelf: 'center',
    paddingHorizontal: 20,
  },

  // Header
  header: {
    backgroundColor: theme.colors.white,
    shadowColor: '#191919',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.08,
    shadowRadius: 8,
    elevation: 3,
    zIndex: 2,
  },
  headerRow: {
    maxWidth: 1320,
    width: '100%',
    alignSelf: 'center',
    paddingHorizontal: 20,
    paddingVertical: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    flexWrap: 'wrap',
    gap: 12,
  },
  brand: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  brandText: {
    fontSize: 22,
    letterSpacing: -0.8,
  },
  brandStrong: {
    fontWeight: '800',
    color: '#202124',
  },
  brandTeal: {
    fontWeight: '700',
    color: theme.colors.teal,
  },
  headerActions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
  },
  headerLink: {
    fontSize: 14,
    color: '#525252',
    fontWeight: '600',
  },
  publishPill: {
    backgroundColor: theme.colors.yellow,
    borderRadius: theme.radius.pill,
    paddingHorizontal: 16,
    paddingVertical: 9,
  },
  publishPillText: {
    fontSize: 14,
    fontWeight: '700',
    color: theme.colors.ink,
  },
  categoryNav: {
    borderTopWidth: 1,
    borderTopColor: '#f0f0f0',
    maxHeight: 58,
  },
  categoryItem: {
    height: 58,
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 9,
    borderBottomWidth: 3,
    borderBottomColor: 'transparent',
  },
  categoryItemActive: {
    borderBottomColor: theme.colors.teal,
  },
  categoryText: {
    fontSize: 15,
    fontWeight: '600',
    color: '#333',
  },
  categoryTextActive: {
    color: theme.colors.tealHover,
  },

  // Hero
  hero: {
    marginTop: 22,
    backgroundColor: '#f4b8ce',
    borderRadius: theme.radius.section,
    padding: 24,
    gap: 20,
  },
  heroWide: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 40,
  },
  heroCopy: {
    gap: 8,
  },
  heroCopyWide: {
    flex: 1,
  },
  kicker: {
    color: '#744958',
    fontWeight: '700',
    fontSize: 14,
    textTransform: 'uppercase',
    letterSpacing: 1,
  },
  heroTitle: {
    color: '#252123',
    fontSize: 36,
    fontWeight: '800',
    lineHeight: 42,
  },
  heroTitleWide: {
    fontSize: 56,
    lineHeight: 62,
  },
  heroDescription: {
    color: '#513d44',
    fontSize: 18,
    lineHeight: 26,
  },
  heroNote: {
    color: '#513d44',
    fontSize: 15,
  },
  heroMedia: {
    borderRadius: 18,
    overflow: 'hidden',
    backgroundColor: '#e9d3dc',
  },
  heroMediaWide: {
    flex: 1,
    marginLeft: 32,
    height: 360,
  },
  heroImage: {
    width: '100%',
    height: 240,
  },
  heroImageEmpty: {
    backgroundColor: '#e9d3dc',
  },
  stamp: {
    position: 'absolute',
    left: 14,
    bottom: 14,
    right: 14,
    backgroundColor: 'rgba(255,255,255,0.92)',
    borderRadius: 14,
    padding: 12,
  },
  stampTitle: {
    fontWeight: '800',
    fontSize: 16,
    color: theme.colors.ink,
  },
  stampSub: {
    color: theme.colors.muted,
    fontSize: 13,
    marginTop: 2,
  },

  // Search
  searchPanel: {
    backgroundColor: theme.colors.white,
    borderRadius: 18,
    padding: 22,
    marginTop: 22,
    shadowColor: '#1f2937',
    shadowOffset: { width: 0, height: 5 },
    shadowOpacity: 0.1,
    shadowRadius: 18,
    elevation: 3,
    gap: 10,
  },
  searchTitle: {
    fontSize: 22,
    fontWeight: '800',
    color: theme.colors.ink,
    marginBottom: 6,
  },
  searchInput: {
    borderBottomWidth: 1,
    borderBottomColor: '#8c8c8c',
    fontSize: 16,
    paddingVertical: 10,
    color: theme.colors.ink,
  },
  filterLabel: {
    fontSize: 13,
    fontWeight: '700',
    color: theme.colors.muted,
    marginTop: 6,
  },
  chipRow: {
    flexGrow: 0,
  },
  chip: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: theme.radius.pill,
    borderWidth: 1,
    borderColor: '#d4d4d4',
    marginRight: 8,
  },
  chipActive: {
    backgroundColor: theme.colors.teal,
    borderColor: theme.colors.teal,
  },
  chipText: {
    fontSize: 14,
    color: '#333',
    fontWeight: '600',
  },
  chipTextActive: {
    color: theme.colors.white,
  },
  searchButton: {
    marginTop: 12,
    height: 52,
    borderRadius: theme.radius.pill,
    backgroundColor: theme.colors.yellowButton,
    alignItems: 'center',
    justifyContent: 'center',
  },
  searchButtonText: {
    fontSize: 16,
    fontWeight: '800',
    color: theme.colors.ink,
  },

  // Sections
  loader: {
    marginVertical: 40,
  },
  emptyBox: {
    marginTop: 32,
    alignItems: 'center',
    gap: 12,
  },
  emptyTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: theme.colors.ink,
  },
  section: {
    marginTop: 28,
    borderRadius: theme.radius.section,
    padding: 25,
  },
  sectionHeading: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    gap: 12,
    marginBottom: 18,
  },
  sectionHeadingText: {
    flex: 1,
  },
  sectionTitle: {
    fontSize: 25,
    fontWeight: '800',
    color: theme.colors.ink,
  },
  sectionDescription: {
    fontSize: 15,
    color: theme.colors.muted,
    marginTop: 4,
  },
  allLink: {
    color: theme.colors.tealHover,
    fontWeight: '700',
    fontSize: 15,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 22,
  },

  // Advisory
  advisory: {
    marginTop: 36,
  },
  advisoryTitle: {
    fontSize: 26,
    fontWeight: '800',
    color: theme.colors.ink,
  },
  advisorySubtitle: {
    fontSize: 15,
    color: theme.colors.muted,
    marginTop: 4,
    marginBottom: 16,
  },
  advisoryGrid: {
    gap: 16,
  },
  advisoryGridWide: {
    flexDirection: 'row',
  },

  // Tools (authenticated)
  tools: {
    marginTop: 36,
  },
  toolsTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: theme.colors.ink,
    marginBottom: 12,
  },
  toolCard: {
    flex: 1,
    backgroundColor: theme.colors.white,
    borderRadius: theme.radius.card,
    padding: 20,
    borderWidth: 1,
    borderColor: theme.colors.border,
    gap: 6,
  },
  toolTitle: {
    fontSize: 17,
    fontWeight: '700',
    color: theme.colors.ink,
  },
  toolText: {
    fontSize: 14,
    color: theme.colors.muted,
  },
});
