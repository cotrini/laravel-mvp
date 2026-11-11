export const errorHandler = {
  getErrorMessage(error: any): string {
    if (error?.response?.data?.message) {
      return error.response.data.message;
    }
    if (error?.message) {
      return error.message;
    }
    if (typeof error === 'string') {
      return error;
    }
    return 'Ocurrió un error inesperado';
  },

  getErrorCode(error: any): string {
    return error?.response?.status?.toString() || 'UNKNOWN_ERROR';
  },

  isNetworkError(error: any): boolean {
    return error?.message?.includes('Network') || error?.code === 'ECONNABORTED';
  },

  isAuthenticationError(error: any): boolean {
    return error?.response?.status === 401;
  },

  isValidationError(error: any): boolean {
    return error?.response?.status === 422;
  },
};
