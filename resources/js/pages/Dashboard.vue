<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppPageLayout from '@/layouts/AppPageLayout.vue';
import { type BreadcrumbItem } from '@/types';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

import { dashboard, logout } from '@/routes';
import { edit as profileEdit } from '@/routes/profile';
import { edit as passwordEdit } from '@/routes/user-password';
import { edit as appearanceEdit } from '@/routes/appearance';
import { show as twoFactorShow } from '@/routes/two-factor';

defineOptions({
    layout: AppPageLayout,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const page = usePage();
const user = page.props.auth?.user as { name?: string; email?: string } | undefined;

function initials(name?: string): string {
    if (!name) return 'U';
    const parts = name.trim().split(/\s+/).filter(Boolean);
    const a = parts[0]?.[0] ?? 'U';
    const b = parts.length > 1 ? (parts[parts.length - 1]?.[0] ?? '') : '';
    return (a + b).toUpperCase();
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="content-wrap">
        <div class="content-bg"></div>
        <div class="content-overlay"></div>

        <div class="content-foreground">
            <div class="container-main section-spacing">
                <!-- Title + Account management (top-right) -->
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-foreground">
                            Dashboard
                        </h1>
                        <p class="mt-2 text-base text-muted-foreground">
                            Account and application overview.
                        </p>
                    </div>

                    <!-- Account Management dropdown (no sidebar component) -->
                    <div class="relative">
                        <details class="group">
                            <summary
                                class="list-none cursor-pointer select-none rounded-md bg-card px-3 py-2 text-sm font-semibold text-foreground border border-border hover:bg-muted"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-primary/25 text-foreground font-semibold"
                                    >
                                        {{ initials(user?.name) }}
                                    </span>
                                    <span class="hidden sm:inline max-w-[180px] truncate">
                                        {{ user?.name ?? 'Account' }}
                                    </span>
                                    <span class="opacity-70">▾</span>
                                </span>
                            </summary>

                            <div
                                class="absolute right-0 mt-2 w-60 rounded-xl bg-card shadow-lg border border-border p-2 z-50"
                            >
                                <div class="px-3 py-2">
                                    <div class="text-sm font-semibold text-foreground truncate">
                                        {{ user?.name ?? 'User' }}
                                    </div>
                                    <div class="text-xs text-muted-foreground truncate">
                                        {{ user?.email ?? '' }}
                                    </div>
                                </div>

                                <div class="my-2 h-px bg-border"></div>

                                <Link
                                    :href="profileEdit().url"
                                    class="block rounded-md px-3 py-2 text-sm text-foreground hover:bg-muted"
                                >
                                    Profile settings
                                </Link>

                                <Link
                                    :href="passwordEdit().url"
                                    class="block rounded-md px-3 py-2 text-sm text-foreground hover:bg-muted"
                                >
                                    Password settings
                                </Link>

                                <Link
                                    :href="twoFactorShow.url()"
                                    class="block rounded-md px-3 py-2 text-sm text-foreground hover:bg-muted"
                                >
                                    Two-factor authentication
                                </Link>

                                <Link
                                    :href="appearanceEdit().url"
                                    class="block rounded-md px-3 py-2 text-sm text-foreground hover:bg-muted"
                                >
                                    Appearance settings
                                </Link>

                                <div class="my-2 h-px bg-border"></div>

                                <!-- Uses the same logout route helper you already use in VerifyEmail.vue -->
                                <Link
                                    :href="logout()"
                                    as="button"
                                    class="w-full text-left rounded-md px-3 py-2 text-sm text-foreground hover:bg-muted"
                                >
                                    Sign out
                                </Link>
                            </div>
                        </details>
                    </div>
                </div>

                <!-- Dashboard content (keeps your placeholders, but no sidebar styling tokens) -->
                <div class="mt-10 flex flex-col gap-4 overflow-x-auto rounded-2xl bg-card/70 backdrop-blur-sm p-6">
                    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                        <div class="relative aspect-video overflow-hidden rounded-2xl border border-border">
                            <PlaceholderPattern />
                        </div>
                        <div class="relative aspect-video overflow-hidden rounded-2xl border border-border">
                            <PlaceholderPattern />
                        </div>
                        <div class="relative aspect-video overflow-hidden rounded-2xl border border-border">
                            <PlaceholderPattern />
                        </div>
                    </div>

                    <div class="relative min-h-[60vh] rounded-2xl border border-border">
                        <PlaceholderPattern />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>