import { usePropertyStore } from '@/stores/propertyStore';

export const useProperties = () => {
  const properties = usePropertyStore();

  return {
    properties: properties.properties,
    myProperties: properties.myProperties,
    selectedProperty: properties.selectedProperty,
    isLoading: properties.isLoading,
    error: properties.error,
    fetchProperties: properties.fetchProperties,
    fetchMyProperties: properties.fetchMyProperties,
    selectProperty: properties.selectProperty,
    uploadVideo: properties.uploadVideo,
    clearError: properties.clearError,
  };
};
