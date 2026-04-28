import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value?: Appearance) => void;
};

const appearance = ref<Appearance>('light');

const setLightPreference = () => {
    if (typeof document !== 'undefined') {
        document.documentElement.classList.remove('dark');
        document.cookie =
            'appearance=light;path=/;max-age=31536000;SameSite=Lax';
    }

    if (typeof window !== 'undefined') {
        localStorage.setItem('appearance', 'light');
    }
};

export function updateTheme(): void {
    setLightPreference();
}

export function initializeTheme(): void {
    setLightPreference();
}

export function useAppearance(): UseAppearanceReturn {
    const resolvedAppearance = computed<ResolvedAppearance>(() => 'light');

    function updateAppearance() {
        appearance.value = 'light';
        setLightPreference();
    }

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
    };
}
