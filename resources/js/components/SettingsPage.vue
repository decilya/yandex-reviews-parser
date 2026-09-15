<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useOrganization } from '../composables/useOrganization';
import { useJobStatus } from '../composables/useJobStatus';

const emit = defineEmits(['parsed']);
const { organization, loading: orgLoading, saveUrl, fetchOrganization } = useOrganization();
const { status, startPolling, stopPolling } = useJobStatus();

const yandexUrl = ref('');
const saveError = ref('');
const saveSuccess = ref(false);

onMounted(async () => {
    await fetchOrganization();
    if (organization.value) yandexUrl.value = organization.value.yandex_url || '';
});

async function handleSave() {
    saveError.value = '';
    saveSuccess.value = false;
    try {
        await saveUrl(yandexUrl.value);
        saveSuccess.value = true;
        startPolling(() => {
            if (status.value?.status === 'completed') {
                stopPolling();
                emit('parsed');
            }
        });
    } catch (e) {
        saveError.value = e.response?.data?.message || 'Ошибка сохранения';
    }
}

function statusLabel(s) {
    const labels = { queued: 'В очереди', processing: 'Парсинг...', completed: 'Завершено', failed: 'Ошибка', pending: 'Ожидание', parsed: 'Данные получены', error: 'Ошибка', requires_attention: 'Требует внимания' };
    return labels[s] || s;
}

onUnmounted(() => { stopPolling(); });
</script>

<template>
    <div class="settings-page">
        <div class="card">
            <h2>Настройки организации</h2>
            <form @submit.prevent="handleSave" class="settings-form">
                <div class="form-group">
                    <label>Ссылка на карточку Яндекс.Карт</label>
                    <input v-model="yandexUrl" type="url" placeholder="https://yandex.ru/maps/org/..." required />
                </div>
                <div v-if="saveError" class="error-message">{{ saveError }}</div>
                <div v-if="saveSuccess" class="success-message">✓ Ссылка сохранена, парсинг запущен</div>
                <button type="submit" :disabled="orgLoading" class="btn-primary">
                    {{ orgLoading ? 'Сохранение...' : 'Сохранить и парсить' }}
                </button>
            </form>
        </div>
        <div v-if="status" class="card status-card">
            <h3>Статус парсинга</h3>
            <div class="status-badge" :class="`status-${status.status}`">{{ statusLabel(status.status) }}</div>
            <div v-if="status.status === 'processing'" class="progress-bar">
                <div class="progress-fill" :style="{ width: `${status.progress || 0}%` }"></div>
            </div>
            <div v-if="status.processed > 0" class="progress-text">
                Обработано: {{ status.processed }} / {{ status.total || '...' }}
            </div>
            <div v-if="status.error" class="error-message">{{ status.error }}</div>
        </div>
        <div v-if="organization && organization.status === 'parsed'" class="card org-card">
            <h3>{{ organization.name || 'Организация' }}</h3>
            <div class="metrics">
                <div class="metric">
                    <span class="metric-value">{{ organization.rating }}</span>
                    <span class="metric-label">Средний рейтинг</span>
                </div>
                <div class="metric">
                    <span class="metric-value">{{ organization.rating_count }}</span>
                    <span class="metric-label">Оценок</span>
                </div>
                <div class="metric">
                    <span class="metric-value">{{ organization.review_count }}</span>
                    <span class="metric-label">Отзывов</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.settings-page { display: flex; flex-direction: column; gap: 20px; }
.card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.card h2, .card h3 { font-weight: 600; margin-bottom: 12px; }
.settings-form { display: flex; flex-direction: column; gap: 16px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group input { padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9375rem; }
.error-message { padding: 10px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #dc2626; }
.success-message { padding: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; color: #16a34a; }
.btn-primary { padding: 12px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; align-self: flex-start; }
.btn-primary:disabled { opacity: 0.7; cursor: not-allowed; }
.status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.8125rem; margin-bottom: 12px; }
.status-queued, .status-pending { background: #f1f5f9; color: #64748b; }
.status-processing { background: #dbeafe; color: #2563eb; }
.status-completed, .status-parsed { background: #dcfce7; color: #16a34a; }
.status-failed, .status-error { background: #fef2f2; color: #dc2626; }
.status-requires_attention { background: #fef3c7; color: #d97706; }
.progress-bar { height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; margin-bottom: 8px; }
.progress-fill { height: 100%; background: #3b82f6; transition: width 0.3s ease; }
.progress-text { font-size: 0.875rem; color: #64748b; }
.metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.metric { text-align: center; padding: 16px; background: #f8fafc; border-radius: 8px; }
.metric-value { display: block; font-size: 1.5rem; font-weight: 700; }
.metric-label { display: block; font-size: 0.75rem; color: #64748b; margin-top: 4px; }
</style>
