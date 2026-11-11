import { useState, useCallback } from 'react';
import { CameraView, useCameraPermissions } from 'expo-camera';

export const useCamera = () => {
  const [cameraPermission, requestCameraPermission] = useCameraPermissions();
  const [cameraRef, setCameraRef] = useState<CameraView | null>(null);
  const [isCapturing, setIsCapturing] = useState(false);

  const takePicture = useCallback(async () => {
    if (!cameraRef || isCapturing) return null;

    try {
      setIsCapturing(true);
      const photo = await cameraRef.takePictureAsync({
        quality: 0.8,
        base64: false,
        skipProcessing: false,
      });
      return photo?.uri ?? null;
    } catch (error) {
      console.error('Error al capturar foto:', error);
      return null;
    } finally {
      setIsCapturing(false);
    }
  }, [cameraRef, isCapturing]);

  const requestPermissions = useCallback(async () => {
    const { granted } = await requestCameraPermission();
    return granted;
  }, [requestCameraPermission]);

  return {
    cameraPermission,
    cameraRef,
    setCameraRef,
    takePicture,
    requestPermissions,
    isCapturing,
  };
};
