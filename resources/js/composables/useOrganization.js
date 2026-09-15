import { ref } from 'vue';
import api from '../api';

const organization = ref(null);
const loading = ref(false);
const error = ref(null);

export function useOrganization() {
    async function fetchOrganization() {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get('/api/organization');
            organization.value = response.data;
        } catch (e) {
            if (e.response?.status !== 404) {
                error.value = e.response?.data?.message || 'Ошибка загрузки';
            }
            organization.value = null;
        } finally {
            loading.value = false;
        }
    }

    async function saveUrl(yandexUrl) {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post('/api/organization', { yandex_url: yandexUrl });
            organization.value = response.data.organization;
            return response.data;
        } catch (e) {
            const errors = e.response?.data?.errors;
            if (errors) {
                error.value = Object.values(errors).flat().join(', ');
            } else {
                error.value = e.response?.data?.message || 'Ошибка сохранения';
            }
            throw e;
        } finally {
            loading.value = false;
        }
    }

    return { organization, loading, error, fetchOrganization, saveUrl };
}
