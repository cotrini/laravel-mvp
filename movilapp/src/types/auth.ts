export interface User {
  id: number;
  name: string;
  email: string;
  phone_number: string | null;
  email_verified_at: string | null;
  role: 'user' | 'admin' | 'filmmaker' | 'appraiser';
}

// Respuesta de POST /auth/firebase y GET /me (UserApiController).
export interface AuthResponse {
  data: User;
  needs_phone_completion: boolean;
}

export interface PhoneVerificationPayload {
  phone_number: string;
  verification_code?: string;
}
