import { reactive, ref, toRaw } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { applyPayload, bumpRefresh, hasErrors, pathname, setErrors, setPending } from './state';

/**
 * Local replacement for Inertia's `useForm`. Submits through axios, collects
 * validation errors, follows the server redirects that Laravel produces and
 * drives the SPA navigation / refresh through the shared state store.
 */
export function useAdminForm(initial = {}) {
    const router = useRouter();
    const dataKeys = Object.keys(initial);
    const transformFn = ref(null);

    const form = reactive({
        ...initial,
        errors: {},
        processing: false,
    });

    function buildPayload() {
        const raw = {};
        dataKeys.forEach((k) => {
            raw[k] = toRaw(form[k]);
        });
        return transformFn.value ? transformFn.value(raw) : raw;
    }

    function reset() {
        dataKeys.forEach((k) => {
            form[k] = initial[k];
        });
        form.errors = {};
    }

    function clearErrors() {
        form.errors = {};
    }

    function transform(fn) {
        transformFn.value = fn;
        return form;
    }

    async function submit(method, url) {
        form.processing = true;

        try {
            const res = await axios[method](url, buildPayload());
            const payload = res.data ?? {};
            const props = payload.props ?? {};

            if (hasErrors(props)) {
                form.errors = props.errors ?? {};
                applyPayload(payload);
                return { ok: false, redirected: false };
            }

            form.errors = {};
            applyPayload(payload);

            const target = pathname(payload.url || url);
            const current = pathname(window.location.href);

            setPending(payload);

            if (target === current) {
                bumpRefresh();
                return { ok: true, redirected: false };
            }

            await router.push(target);
            return { ok: true, redirected: true };
        } catch (e) {
            if (e?.response?.status === 422 && e.response.data?.errors) {
                form.errors = e.response.data.errors;
                setErrors(e.response.data.errors);
            } else if (e?.response?.status === 401) {
                window.location.href = '/admin/login';
            }

            return { ok: false, redirected: false };
        } finally {
            form.processing = false;
        }
    }

    form.post = (url) => submit('post', url);
    form.put = (url) => submit('put', url);
    form.patch = (url) => submit('patch', url);
    form.delete = (url) => submit('delete', url);
    form.reset = reset;
    form.clearErrors = clearErrors;
    form.transform = transform;

    return form;
}
