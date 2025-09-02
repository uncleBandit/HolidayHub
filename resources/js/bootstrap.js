/**
 * bootstrap.js
 *
 * Production-ready setup for Axios, CSRF, error logging, and Laravel API integration.
 */

import axios from 'axios'

// --- Axios Base Config ---
axios.defaults.baseURL = import.meta.env.VITE_API_BASE_URL || '/api' // Use API base from env
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.timeout = 10000 // 10s timeout for requests

// --- CSRF Token for Laravel ---
const token = document.querySelector('meta[name="csrf-token"]')
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
} else {
    console.warn('⚠️ CSRF token not found. Ensure <meta name="csrf-token" content="{{ csrf_token() }}"> is in your <head>.')
}

// --- Request Interceptor ---
axios.interceptors.request.use(
    (config) => {
        // Example: Attach auth token if stored
        const authToken = localStorage.getItem('auth_token')
        if (authToken) {
            config.headers.Authorization = `Bearer ${authToken}`
        }
        return config
    },
    (error) => {
        console.error('❌ Request error:', error)
        return Promise.reject(error)
    }
)

// --- Response Interceptor ---
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response) {
            console.error(`❌ API Error [${error.response.status}]:`, error.response.data)

            // Example: redirect on 401 Unauthorized
            if (error.response.status === 401) {
                // Optionally clear auth & redirect
                localStorage.removeItem('auth_token')
                window.location.href = '/login'
            }
        } else if (error.request) {
            console.error('⚠️ No response from API:', error.request)
        } else {
            console.error('⚠️ Request setup error:', error.message)
        }

        // If you use external logging (Sentry, Bugsnag), hook it here
        // Sentry.captureException(error)

        return Promise.reject(error)
    }
)

export default axios
