import { watch } from 'vue';
import { state } from './state';

export function useAdminHead(title) {
    watch(
        () => title?.value ?? title ?? '',
        (t) => {
            const app = state.chrome?.appName || 'LMS';
            document.title = t ? `${t} — ${app}` : app;
        },
        { immediate: true }
    );
}
