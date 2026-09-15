import { ref, computed } from 'vue';
import api from '../api';

const user = ref(null);
const token = ref(localStorage.getItem('token') || null);
const loading = ref(false);
const error = ref(null);

if (token.value) {
    api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
}

export function useAuth() {
    const isAuthenticated = computed(() => !!token.value);

    async function login(email, password) {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post('/api/login', { email, password });
            token.value = response.data.token;
            user.value = response.data.user;
            localStorage.setItem('token', token.value);
            api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
        } catch (e) {
            error.value = e.response?.data?.message || 'Ошибка входа';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function logout() {
        try {
            await api.post('/api/logout');
        } catch (e) {}
        token.value = null;
        user.value = null;
        localStorage.removeItem('token');
        delete api.defaults.headers.common['Authorization'];
    }

    return { user, token, isAuthenticated, loading, error, login, logout };
}
