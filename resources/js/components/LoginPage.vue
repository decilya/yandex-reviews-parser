<script setup>
import { ref } from 'vue';
import { useAuth } from '../composables/useAuth';

const { login, error, loading } = useAuth();
const email = ref('admin@test.com');
const password = ref('password');
const localError = ref('');

async function handleLogin() {
    localError.value = '';
    try {
        await login(email.value, password.value);
    } catch (e) {
        localError.value = e.response?.data?.message || 'Ошибка авторизации';
    }
}
</script>

<template>
    <div class="login-page">
        <div class="login-card">
            <h2>Вход в систему</h2>
            <p class="subtitle">Парсер отзывов Яндекс.Карт</p>
            <form @submit.prevent="handleLogin" class="login-form">
                <div class="form-group">
                    <label>Email</label>
                    <input v-model="email" type="email" placeholder="admin@test.com" required />
                </div>
                <div class="form-group">
                    <label>Пароль</label>
                    <input v-model="password" type="password" placeholder="••••••••" required />
                </div>
                <div v-if="localError || error" class="error-message">{{ localError || error }}</div>
                <button type="submit" :disabled="loading" class="btn-primary">
                    {{ loading ? 'Вход...' : 'Войти' }}
                </button>
            </form>
            <div class="hint">
                <p>Тестовый пользователь:</p>
                <code>admin@test.com / password</code>
            </div>
        </div>
    </div>
</template>

<style scoped>
.login-page { display: flex; align-items: center; justify-content: center; min-height: 60vh; }
.login-card { background: white; border-radius: 16px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
.login-card h2 { font-size: 1.5rem; font-weight: 700; margin-bottom: 4px; }
.subtitle { color: #64748b; font-size: 0.875rem; margin-bottom: 24px; }
.login-form { display: flex; flex-direction: column; gap: 16px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 0.875rem; font-weight: 500; color: #374151; }
.form-group input { padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9375rem; }
.error-message { padding: 10px 14px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #dc2626; font-size: 0.875rem; }
.btn-primary { padding: 12px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 0.9375rem; font-weight: 600; cursor: pointer; }
.btn-primary:disabled { opacity: 0.7; cursor: not-allowed; }
.hint { margin-top: 24px; padding: 12px; background: #f8fafc; border-radius: 8px; font-size: 0.8125rem; color: #64748b; }
.hint code { display: block; margin-top: 4px; color: #1a1a2e; }
</style>
