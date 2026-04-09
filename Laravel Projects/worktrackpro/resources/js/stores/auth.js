import { defineStore } from 'pinia';
import api from '../lib/axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('access_token') || null,
        loading: false,
        error: null,
    }),
    
    getters: {
        isAuthenticated: (state) => !!state.token,
        hasRole: (state) => (role) => {
             return state.user?.roles?.includes(role) || false;
        }
    },
    
    actions: {
        async login(email, password) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.post('/auth/login', { email, password });
                
                this.token = response.data.access_token;
                this.user = response.data.user;
                
                // Persist the token
                localStorage.setItem('access_token', this.token);
                
                return true; // Used by UI to trigger redirect
            } catch (err) {
                this.error = err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Login failed. Please try again.';
                return false;
            } finally {
                this.loading = false;
            }
        },
        
        async fetchUser() {
            if (!this.token) return;
            
            try {
                const response = await api.get('/auth/me');
                this.user = response.data.data;
            } catch (err) {
                // If fetching user fails (e.g., token expired/invalid), wipe the state
                this.logout();
            }
        },
        
        async logout() {
            if (this.token) {
                try {
                    await api.post('/auth/logout');
                } catch (e) {
                    // Ignore errors on logout, we clear local state anyway
                }
            }
            
            this.user = null;
            this.token = null;
            localStorage.removeItem('access_token');
        }
    }
});
