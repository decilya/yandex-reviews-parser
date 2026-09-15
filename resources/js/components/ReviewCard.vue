<script setup>
defineProps({ review: { type: Object, required: true } });

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('ru-RU', { year: 'numeric', month: 'long', day: 'numeric' });
}

function starsArray(rating) {
    return Array.from({ length: 5 }, (_, i) => i < rating);
}
</script>

<template>
    <div class="review-card">
        <div class="review-header">
            <div class="author-info">
                <div class="avatar">{{ review.author_name?.charAt(0)?.toUpperCase() || '?' }}</div>
                <div>
                    <div class="author-name">{{ review.author_name }}</div>
                    <div class="review-date">{{ formatDate(review.published_at) }}</div>
                </div>
            </div>
            <div class="review-rating">
                <span v-for="(filled, idx) in starsArray(review.rating)" :key="idx" :class="['star', { filled }]">★</span>
            </div>
        </div>
        <div v-if="review.text" class="review-text">{{ review.text }}</div>
        <div v-else class="review-text empty"><em>Без текста</em></div>
    </div>
</template>

<style scoped>
.review-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.review-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; }
.author-info { display: flex; align-items: center; gap: 12px; }
.avatar { width: 40px; height: 40px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 600; }
.author-name { font-weight: 600; font-size: 0.9375rem; }
.review-date { font-size: 0.8125rem; color: #94a3b8; margin-top: 2px; }
.star { color: #e2e8f0; font-size: 1rem; }
.star.filled { color: #f59e0b; }
.review-text { font-size: 0.9375rem; line-height: 1.6; color: #374151; }
.review-text.empty { color: #94a3b8; font-style: italic; }
</style>
