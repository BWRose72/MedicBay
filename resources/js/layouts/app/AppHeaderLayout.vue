<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import type { BreadcrumbItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { dashboard, login, logout, register } from '@/routes';

import { edit as profileEdit } from '@/routes/profile';
import { edit as passwordEdit } from '@/routes/user-password';
import { edit as appearanceEdit } from '@/routes/appearance';
import { show as twoFactorShow } from '@/routes/two-factor';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
    canRegister?: boolean;
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    canRegister: true,
});

const page = usePage();

type AuthUser = {
    id?: number;
    name?: string;
    email?: string;
    roles?: string[]; // may be a plain array depending on Inertia serialization
    is_admin?: boolean;
};

const user = page.props.auth?.user as AuthUser | undefined;

const isAdmin =
    user?.is_admin === true ||
    (Array.isArray(user?.roles) && user!.roles.includes('admin'));

function initials(name?: string): string {
    if (!name) return 'U';
    const parts = name.trim().split(/\s+/).filter(Boolean);
    const a = parts[0]?.[0] ?? 'U';
    const b = parts.length > 1 ? (parts[parts.length - 1]?.[0] ?? '') : '';
    return (a + b).toUpperCase();
}
</script>

<template>
    <AppShell class="flex-col">
        <header class="sticky top-0 z-50 w-full bg-secondary text-secondary-foreground">
            <div class="container-main flex h-16 items-center justify-between">
                <div class="flex items-center gap-6">
                    <Link :href="$page.props.auth.user ? dashboard() : '/'"
                        class="inline-flex items-center gap-2 rounded-md px-2 py-1 text-base font-semibold tracking-tight hover:bg-primary/20">
                        <span
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-foreground/15">
                            M
                        </span>
                        <span>MedicBay</span>
                    </Link>

                    <nav class="hidden items-center gap-1 sm:flex">
                        <Link href="/doctors" class="rounded-md px-3 py-2 text-sm font-medium hover:bg-primary/20">
                            Doctors
                        </Link>
                        <Link href="/about" class="rounded-md px-3 py-2 text-sm font-medium hover:bg-primary/20">
                            About
                        </Link>

                        <Link v-if="$page.props.auth.user && isAdmin" href="/admin/users"
                            class="rounded-md px-3 py-2 text-sm font-medium hover:bg-primary/20">
                            All Users
                        </Link>
                    </nav>
                </div>

                <div class="flex items-center gap-2">
                    <div v-if="$page.props.auth.user" class="relative">
                        <!-- Wrapper creates a hover bridge -->
                        <div class="group relative pt-2 -mt-2">
                            <!-- Trigger button -->
                            <div
                                class="cursor-default select-none rounded-md border border-border bg-background/70 px-3 py-2 text-sm font-semibold text-foreground hover:bg-muted">
                                <span class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-primary/25 text-foreground font-semibold">
                                        {{ initials(user?.name) }}
                                    </span>
                                    <span class="hidden sm:inline max-w-[180px] truncate">
                                        {{ user?.name ?? 'Account' }}
                                    </span>
                                    <span class="opacity-70">▾</span>
                                </span>
                            </div>

                            <!-- Menu (hover only, no gap) -->
                            <div class="invisible absolute right-0 top-full w-64 rounded-xl bg-card shadow-lg border border-border p-2 z-50
                                       opacity-0 transition-opacity duration-150 delay-75
                                       group-hover:visible group-hover:opacity-100 group-hover:delay-0">
                                <div class="px-3 py-2">
                                    <div class="text-sm font-semibold text-foreground truncate">
                                        {{ user?.name ?? 'User' }}
                                    </div>
                                    <div class="text-xs text-muted-foreground truncate">
                                        {{ user?.email ?? '' }}
                                    </div>
                                </div>

                                <div class="my-2 h-px bg-border"></div>

                                <Link :href="dashboard()"
                                    class="block rounded-md px-3 py-2 text-sm text-foreground hover:bg-muted">
                                    Dashboard
                                </Link>

                                <Link v-if="isAdmin" href="/admin/users"
                                    class="block rounded-md px-3 py-2 text-sm text-foreground hover:bg-muted">
                                    All Users
                                </Link>

                                <div class="my-2 h-px bg-border"></div>

                                <Link :href="profileEdit().url"
                                    class="block rounded-md px-3 py-2 text-sm text-foreground hover:bg-muted">
                                    Profile settings
                                </Link>
                                <Link :href="passwordEdit().url"
                                    class="block rounded-md px-3 py-2 text-sm text-foreground hover:bg-muted">
                                    Password settings
                                </Link>
                                <Link :href="twoFactorShow.url()"
                                    class="block rounded-md px-3 py-2 text-sm text-foreground hover:bg-muted">
                                    Two-factor authentication
                                </Link>
                                <Link :href="appearanceEdit().url"
                                    class="block rounded-md px-3 py-2 text-sm text-foreground hover:bg-muted">
                                    Appearance settings
                                </Link>

                                <div class="my-2 h-px bg-border"></div>

                                <Link :href="logout()" as="button"
                                    class="w-full text-left rounded-md px-3 py-2 text-sm text-foreground hover:bg-muted">
                                    Sign out
                                </Link>
                            </div>
                        </div>
                    </div>

                    <template v-else>
                        <Link :href="login()" class="rounded-md px-4 py-2 text-sm font-semibold hover:bg-primary/20">
                            Log in
                        </Link>
                        <Link v-if="canRegister" :href="register()"
                            class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
                            Sign up
                        </Link>
                    </template>
                </div>
            </div>

            <div class="border-t border-primary-foreground/15 sm:hidden">
                <div class="container-main flex items-center gap-2 py-2">
                    <Link href="/doctors"
                        class="flex-1 rounded-md px-3 py-2 text-center text-sm font-medium hover:bg-primary/20">
                        Doctors
                    </Link>
                    <Link href="/about"
                        class="flex-1 rounded-md px-3 py-2 text-center text-sm font-medium hover:bg-primary/20">
                        About
                    </Link>

                    <Link v-if="$page.props.auth.user && isAdmin" href="/admin/users"
                        class="flex-1 rounded-md px-3 py-2 text-center text-sm font-medium hover:bg-primary/20">
                        All Users
                    </Link>
                </div>
            </div>
        </header>

        <AppContent>
            <slot />
        </AppContent>
    </AppShell>
</template>