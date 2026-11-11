import { useState, useEffect, useCallback } from 'react';
import { DeviceMotion } from 'expo-sensors';
import * as ScreenOrientation from 'expo-screen-orientation';
import { DeviceOrientation, SphereGridPoint } from '@/types/sensors';

export const useSensors = () => {
  const [orientation, setOrientation] = useState<DeviceOrientation>({
    alpha: 0,
    beta: 0,
    gamma: 0,
  });

  const [isListening, setIsListening] = useState(false);
  const [calibration, setCalibration] = useState<DeviceOrientation>({
    alpha: 0,
    beta: 0,
    gamma: 0,
  });

  useEffect(() => {
    if (!isListening) return;

    const subscription = DeviceMotion.addListener((data) => {
      const { rotation } = data;
      if (rotation) {
        setOrientation({
          alpha: (rotation.alpha * 180) / Math.PI,
          beta: (rotation.beta * 180) / Math.PI,
          gamma: (rotation.gamma * 180) / Math.PI,
        });
      }
    });

    return () => {
      subscription.remove();
    };
  }, [isListening]);

  const startListening = useCallback(async () => {
    try {
      await ScreenOrientation.lockAsync(ScreenOrientation.OrientationLock.LANDSCAPE);
      DeviceMotion.setUpdateInterval(100);
      setIsListening(true);
    } catch (error) {
      console.error('Error al iniciar sensores:', error);
    }
  }, []);

  const stopListening = useCallback(async () => {
    setIsListening(false);
    await ScreenOrientation.lockAsync(ScreenOrientation.OrientationLock.PORTRAIT);
  }, []);

  const calibrate = useCallback(() => {
    setCalibration(orientation);
  }, [orientation]);

  const getRelativeOrientation = useCallback((): DeviceOrientation => {
    return {
      alpha: orientation.alpha - calibration.alpha,
      beta: orientation.beta - calibration.beta,
      gamma: orientation.gamma - calibration.gamma,
    };
  }, [orientation, calibration]);

  return {
    orientation,
    isListening,
    startListening,
    stopListening,
    calibrate,
    getRelativeOrientation,
  };
};
