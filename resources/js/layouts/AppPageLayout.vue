<script setup lang="ts">
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import AppFooterLayout from '@/layouts/app/AppFooterLayout.vue';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
        canRegister?: boolean;
    }>(),
    {
        breadcrumbs: () => [],
        canRegister: true,
    },
);
</script>

<template>
    <AppHeaderLayout :breadcrumbs="breadcrumbs" :can-register="canRegister">
        <AppFooterLayout>
            <slot />
        </AppFooterLayout>
    </AppHeaderLayout>
</template>