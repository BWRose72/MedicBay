<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import type { BreadcrumbItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
    canRegister?: boolean;
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    canRegister: true,
});
</script>

<template>
    <AppShell class="flex-col">
        <!-- Header (theme-driven via CSS variables + Tailwind tokens) -->
        <header class="sticky top-0 z-50 w-full bg-secondary text-secondary-foreground">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                <div class="flex items-center gap-6">
                    <!-- Brand -->
                    <Link
                        :href="$page.props.auth.user ? dashboard() : '/'"
                        class="inline-flex items-center gap-2 rounded-md px-2 py-1 text-base font-semibold tracking-tight hover:bg-primary/80"
                    >
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-foreground/15">
                            M
                        </span>
                        <span>MedicBay</span>
                    </Link>

                    <!-- Left nav -->
                    <nav class="hidden items-center gap-1 sm:flex">
                        <Link href="/doctors" class="rounded-md px-3 py-2 text-sm font-medium hover:bg-primary/80">
                            Doctors
                        </Link>
                        <Link href="/about" class="rounded-md px-3 py-2 text-sm font-medium hover:bg-primary/80">
                            About
                        </Link>
                    </nav>
                </div>

                <!-- Right auth -->
                <div class="flex items-center gap-2">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-md bg-secondary-foreground px-4 py-2 text-sm font-semibold text-foreground hover:bg-secondary-foreground/90"
                    >
                        Dashboard
                    </Link>

                    <template v-else>
                        <Link :href="login()" class="rounded-md px-4 py-2 text-sm font-semibold hover:bg-primary/80">
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-md bg-primary-foreground px-4 py-2 text-sm font-semibold text-foreground hover:bg-primary-foreground/90"
                        >
                            Sign up
                        </Link>
                    </template>
                </div>
            </div>

            <!-- Mobile nav -->
            <div class="border-t border-primary-foreground/15 sm:hidden">
                <div class="mx-auto flex max-w-6xl items-center gap-2 px-4 py-2">
                    <Link href="/doctors" class="flex-1 rounded-md px-3 py-2 text-center text-sm font-medium hover:bg-primary/80">
                        Doctors
                    </Link>
                    <Link href="/about" class="flex-1 rounded-md px-3 py-2 text-center text-sm font-medium hover:bg-primary/80">
                        About
                    </Link>
                </div>
            </div>
        </header>

        <AppContent>
            <slot />
        </AppContent>
    </AppShell>
</template>