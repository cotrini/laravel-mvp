import axios, { AxiosInstance, InternalAxiosRequestConfig } from 'axios';
import { API_BASE_URL } from '@/constants/api.config';
import { firebaseAuth } from './firebase';
import { sessionStorage } from './session.storage';

const apiClient: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

// El backend valida el token en cada petición: token Sanctum de la cuenta de base de datos o, si no hay, Firebase (Google).
apiClient.interceptors.request.use(async (config: InternalAxiosRequestConfig) => {
  try {
    const token = (await sessionStorage.getToken()) ?? (await firebaseAuth.currentUser?.getIdToken());
    if (token && !config.headers.Authorization) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  } catch (error) {
    console.error('Error al recuperar token:', error);
  }
  return config;
});

export default apiClient;
