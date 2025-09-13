// resources/js/api/dashboard.js
import axios from 'axios';

/**
 * Base URL for API requests.
 * Reads from environment variable for flexibility.
 */
const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://holidayhub.test/api/v1';

/**
 * Axios instance with default settings
 * Useful for adding auth tokens, interceptors, or global headers
 */
const apiClient = axios.create({
  baseURL: API_BASE,
  timeout: 10000, // 10 seconds
  headers: {
    'Accept': 'application/json',
  },
});

/**
 * Fetch featured dashboard data (destinations, hotels, activities, offers, packages)
 * @returns {Promise<Object>} Dashboard data
 */
export const fetchDashboardData = async () => {
  try {
    const response = await apiClient.get('/dashboard');

    if (response.data.success) {
      return response.data.data;
    }

    throw new Error('Failed to fetch dashboard data.');
  } catch (error) {
    console.error('Dashboard API Error:', error);
    throw error; // Let caller handle UI errors
  }
};

/**
 * Optional: Add interceptors for auth, refresh tokens, or logging
 */
apiClient.interceptors.request.use(
  (config) => {
    // Example: Attach user auth token if available
    const token = localStorage.getItem('auth_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
  },
  (error) => Promise.reject(error)
);

export default apiClient;
