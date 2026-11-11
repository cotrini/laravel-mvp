import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Dimensions,
  TouchableOpacity,
  Alert,
} from 'react-native';
import Animated, {
  useSharedValue,
  useAnimatedStyle,
} from 'react-native-reanimated';
import { useSensors } from '@/hooks/useSensors';
import { SphereGridPoint } from '@/types/sensors';

const { width, height } = Dimensions.get('window');

// Grilla de puntos de captura: gridSize filas de latitud y gridSize columnas de longitud.
export function buildSphereGrid(gridSize: number): SphereGridPoint[] {
  const grid: SphereGridPoint[] = [];
  const latStep = 180 / (gridSize + 1);
  const lonStep = 360 / gridSize;

  for (let lat = -90 + latStep; lat < 90; lat += latStep) {
    for (let lon = 0; lon < 360; lon += lonStep) {
      grid.push({
        id: `${lat}-${lon}`,
        latitude: lat,
        longitude: lon,
        captured: false,
      });
    }
  }

  return grid;
}

interface SphereGuideProps {
  // Devuelve true si la foto se guardó; solo entonces el punto se pinta como capturado.
  onCapture: (point: SphereGridPoint) => Promise<boolean>;
  onComplete: () => void;
  gridSize?: number;
}

export const SphereGuide: React.FC<SphereGuideProps> = ({
  onCapture,
  onComplete,
  gridSize = 4,
}) => {
  const {
    orientation,
    startListening,
    stopListening,
    calibrate,
    getRelativeOrientation,
  } = useSensors();

  const [sphereGrid, setSphereGrid] = useState<SphereGridPoint[]>(() => buildSphereGrid(gridSize));
  const [isCalibrated, setIsCalibrated] = useState(false);
  const [capturedCount, setCapturedCount] = useState(0);
  const [busy, setBusy] = useState(false);

  const sphereRotation = useSharedValue(0);

  const totalGridPoints = sphereGrid.length;

  useEffect(() => {
    startListening();
    return () => {
      stopListening();
    };
  }, []);

  const handleCalibrate = () => {
    calibrate();
    setIsCalibrated(true);
    Alert.alert('Calibración', 'Punto de referencia establecido. Comienza a grabar.');
  };

  const handleCapture = async () => {
    if (busy) return;
    const relativeOrientation = getRelativeOrientation();

    const longitude = ((relativeOrientation.alpha % 360) + 360) % 360;
    const latitude = Math.max(-90, Math.min(90, relativeOrientation.beta));

    let closestIndex = -1;
    let minDistance = Infinity;

    sphereGrid.forEach((point, index) => {
      const distance = Math.sqrt(
        Math.pow(point.latitude - latitude, 2) + Math.pow(point.longitude - longitude, 2)
      );
      if (distance < minDistance) {
        minDistance = distance;
        closestIndex = index;
      }
    });

    if (closestIndex === -1 || sphereGrid[closestIndex].captured) return;

    setBusy(true);
    const point = sphereGrid[closestIndex];
    const saved = await onCapture(point);
    setBusy(false);
    if (!saved) return;

    const nextCount = capturedCount + 1;
    setSphereGrid((grid) =>
      grid.map((p, index) => (index === closestIndex ? { ...p, captured: true } : p))
    );
    setCapturedCount(nextCount);

    if (nextCount >= totalGridPoints) {
      onComplete();
    }
  };

  const animatedStyle = useAnimatedStyle(() => ({
    transform: [{ rotateZ: `${sphereRotation.value}deg` }],
  }));

  const progress = (capturedCount / totalGridPoints) * 100;

  return (
    <View style={styles.container}>
      <Animated.View style={[styles.sphereContainer, animatedStyle]}>
        <View style={styles.sphere}>
          {sphereGrid.map((point) => {
            const xPercent = (point.longitude / 360) * 100;
            const yPercent = ((point.latitude + 90) / 180) * 100;

            return (
              <View
                key={point.id}
                style={[
                  styles.gridPoint,
                  {
                    left: `${xPercent}%`,
                    top: `${yPercent}%`,
                    backgroundColor: point.captured ? '#4CAF50' : '#e0e0e0',
                  },
                ]}
              />
            );
          })}
        </View>
      </Animated.View>

      <View style={styles.controls}>
        <Text style={styles.progressText}>
          Progreso: {Math.round(progress)}% ({capturedCount}/{totalGridPoints})
        </Text>

        {!isCalibrated ? (
          <TouchableOpacity style={styles.button} onPress={handleCalibrate}>
            <Text style={styles.buttonText}>Calibrar</Text>
          </TouchableOpacity>
        ) : (
          <TouchableOpacity style={styles.captureButton} onPress={handleCapture}>
            <Text style={styles.buttonText}>Capturar Punto</Text>
          </TouchableOpacity>
        )}

        <View style={styles.orientationDisplay}>
          <Text style={styles.smallText}>
            Azimut: {Math.round(orientation.alpha)}°
          </Text>
          <Text style={styles.smallText}>
            Elevación: {Math.round(orientation.beta)}°
          </Text>
        </View>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
    justifyContent: 'space-between',
  },
  sphereContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  sphere: {
    width: 300,
    height: 300,
    borderRadius: 150,
    backgroundColor: 'rgba(200, 200, 200, 0.1)',
    borderWidth: 2,
    borderColor: '#bbb',
    position: 'relative',
    overflow: 'hidden',
  },
  gridPoint: {
    width: 12,
    height: 12,
    borderRadius: 6,
    position: 'absolute',
    marginLeft: -6,
    marginTop: -6,
    borderWidth: 1,
    borderColor: '#fff',
  },
  controls: {
    backgroundColor: '#fff',
    padding: 20,
    borderTopWidth: 1,
    borderTopColor: '#eee',
  },
  progressText: {
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 15,
    textAlign: 'center',
  },
  button: {
    backgroundColor: '#2196F3',
    padding: 15,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 10,
  },
  captureButton: {
    backgroundColor: '#FF5722',
    padding: 15,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 10,
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
  orientationDisplay: {
    backgroundColor: '#f0f0f0',
    padding: 10,
    borderRadius: 6,
    marginTop: 10,
  },
  smallText: {
    fontSize: 12,
    color: '#666',
    marginVertical: 4,
  },
});
