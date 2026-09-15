<script setup>
import { ref, onMounted, computed } from 'vue';
import { useReviews } from '../composables/useReviews';
import ReviewCard from './ReviewCard.vue';

const { reviews, meta, organization, loading, error, fetchReviews } = useReviews();
const currentPage = ref(1);

onMounted(async () => { await fetchReviews(currentPage.value); });

async function changePage(page) {
    currentPage.value = page;
    await fetchReviews(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

const pages = computed(() => {
    if (!meta.value) return [];
    const total = meta.value.last_page;
    const current = meta.value.current_page;
    const range = [];
    let start = Math.max(1, current - 2);
    let end = Math.min(total, current + 2);
    if (start > 1) range.push(1);
    if (start > 2) range.push('...');
    for (let i = start; i <= end; i++) range.push(i);
    if (end < total - 1) range.push('...');
    if (end < total) range.push(total);
    return range;
});
</script>

<template>
    <div class="reviews-page">
        <div v-if="organization" class="card org-summary">
            <div class="org-header">
                <h2>{{ organization.name || 'Организация' }}</h2>
                <div class="rating-badge">★ {{ organization.rating }}</div>
            </div>
            <div class="counters">
                <div class="counter"><span class="counter-value">{{ organization.rating_count }}</span><span class="counter-label">Оценок</span></div>
                <div class="counter"><span class="counter-value">{{ organization.review_count }}</span><span class="counter-label">Отзывов</span></div>
                <div class="counter"><span class="counter-value">{{ meta?.total || 0 }}</span><span class="counter-label">Загружено</span></div>
            </div>
        </div>
        <div v-if="loading" class="card loading-state"><p>Загрузка...</p></div>
        <div v-else-if="error" class="card error-state">
            <p class="error-text">{{ error }}</p>
            <button @click="fetchReviews(currentPage)" class="btn-secondary">Попробовать снова</button>
        </div>
        <div v-else-if="reviews.length === 0" class="card empty-state">
            <p>Отзывов пока нет</p>
        </div>
        <div v-else class="reviews-list">
            <ReviewCard v-for="review in reviews" :key="review.id" :review="review" />
        </div>
        <div v-if="meta && meta.last_page > 1" class="pagination">
            <button :disabled="currentPage <= 1" @click="changePage(currentPage - 1)" class="page-btn">← Назад</button>
            <template v-for="(page, idx) in pages" :key="idx">
                <span v-if="page === '...'" class="page-dots">...</span>
                <button v-else :class="['page-btn', { active: page === currentPage }]" @click="changePage(page)">{{ page }}</button>
            </template>
            <button :disabled="currentPage >= meta.last_page" @click="changePage(currentPage + 1)" class="page-btn">Вперёд →</button>
        </div>
    </div>
</template>

<style scoped>
.reviews-page { display: flex; flex-direction: column; gap: 16px; }
.card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.org-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.rating-badge { padding: 6px 14px; background: #fef3c7; border-radius: 20px; font-weight: 600; color: #d97706; }
.counters { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.counter { text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px; }
.counter-value { display: block; font-size: 1.25rem; font-weight: 700; }
.counter-label { display: block; font-size: 0.75rem; color: #64748b; }
.loading-state, .error-state, .empty-state { text-align: center; padding: 48px 24px; }
.btn-secondary { padding: 8px 16px; background: #f1f5f9; color: #374151; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; }
.reviews-list { display: flex; flex-direction: column; gap: 12px; }
.pagination { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 16px; background: white; border-radius: 12px; }
.page-btn { padding: 8px 14px; border: 1px solid #e2e8f0; border-radius: 8px; background: white; cursor: pointer; }
.page-btn.active { background: #3b82f6; color: white; border-color: #3b82f6; }
.page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.page-dots { padding: 8px 4px; color: #94a3b8; }
</style>
