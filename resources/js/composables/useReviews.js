import { ref } from 'vue';
import api from '../api';

const reviews = ref([]);
const meta = ref(null);
const organization = ref(null);
const loading = ref(false);
const error = ref(null);

export function useReviews() {
    async function fetchReviews(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get('/api/reviews', { params: { page, per_page: 50 } });
            reviews.value = response.data.data;
            meta.value = response.data.meta;
            organization.value = response.data.organization;
        } catch (e) {
            error.value = e.response?.data?.message || 'Ошибка загрузки отзывов';
        } finally {
            loading.value = false;
        }
    }

    return { reviews, meta, organization, loading, error, fetchReviews };
}
