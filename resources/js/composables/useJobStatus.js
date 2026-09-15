import { ref } from 'vue';
import api from '../api';

const status = ref(null);
const polling = ref(false);
let pollInterval = null;

export function useJobStatus() {
    async function fetchStatus() {
        try {
            const response = await api.get('/api/organization/status');
            status.value = response.data;
            return response.data;
        } catch (e) {}
    }

    function startPolling(onUpdate) {
        polling.value = true;
        pollInterval = setInterval(async () => {
            const result = await fetchStatus();
            if (onUpdate) onUpdate(result);
            if (result && (result.status === 'completed' || result.status === 'failed')) {
                stopPolling();
            }
        }, 2000);
    }

    function stopPolling() {
        polling.value = false;
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    return { status, polling, fetchStatus, startPolling, stopPolling };
}
