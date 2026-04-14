<!-- resources/js/pages/AdminDashboard.vue -->
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppPageLayout from '@/layouts/AppPageLayout.vue';

defineOptions({ layout: AppPageLayout });
</script>

<template>

    <Head title="Admin Dashboard" />

    <div class="content-wrap">
        <div class="content-bg"></div>
        <div class="content-overlay"></div>

        <div class="content-foreground">
            <div class="container-main section-spacing">
                <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-foreground">
                    Admin Dashboard
                </h1>
                <p class="mt-2 text-base text-muted-foreground">
                    Admin-only overview.
                </p>

                <div class="mt-10 rounded-2xl bg-card/70 backdrop-blur-sm border border-border p-6">
                    <p>Content to be added.</p>
                </div>
            </div>
        </div>
    </div>
</template>