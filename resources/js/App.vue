<script setup>
import { ref, computed } from 'vue';
import { useAuth } from './composables/useAuth';
import LoginPage from './components/LoginPage.vue';
import SettingsPage from './components/SettingsPage.vue';
import ReviewsPage from './components/ReviewsPage.vue';

const { isAuthenticated, loading: authLoading } = useAuth();
const currentView = ref('settings');

const showLogin = computed(() => !isAuthenticated.value && !authLoading.value);

function onParsed() {
    currentView.value = 'reviews';
}
</script>

<template>
    <div class="app">
        <header v-if="isAuthenticated" class="app-header">
            <h1>Парсер отзывов Яндекс.Карт</h1>
            <nav>
                <button :class="{ active: currentView === 'settings' }" @click="currentView = 'settings'">Настройки</button>
                <button :class="{ active: currentView === 'reviews' }" @click="currentView = 'reviews'">Отзывы</button>
            </nav>
        </header>
        <main class="app-main">
            <LoginPage v-if="showLogin" />
            <SettingsPage v-else-if="currentView === 'settings'" @parsed="onParsed" />
            <ReviewsPage v-else-if="currentView === 'reviews'" />
        </main>
    </div>
</template>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; color: #1a1a2e; min-height: 100vh; }
.app { max-width: 1200px; margin: 0 auto; padding: 20px; }
.app-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 24px; }
.app-header h1 { font-size: 1.25rem; font-weight: 600; }
.app-header nav { display: flex; gap: 8px; }
.app-header nav button { padding: 8px 16px; border: none; border-radius: 8px; background: transparent; color: #64748b; font-size: 0.875rem; font-weight: 500; cursor: pointer; }
.app-header nav button:hover { background: #f1f5f9; color: #1a1a2e; }
.app-header nav button.active { background: #3b82f6; color: white; }
.app-main { min-height: 400px; }
</style>
