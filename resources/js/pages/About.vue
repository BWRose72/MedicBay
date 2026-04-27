<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppPageLayout from '@/layouts/AppPageLayout.vue';

defineOptions({
    layout: AppPageLayout,
});
</script>

<template>
    <Head title="About" />

    <div class="content-wrap">
        <div class="content-bg"></div>
        <div class="content-overlay"></div>

        <div class="content-foreground">
            <div class="container-main section-spacing">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-foreground">
                            About MedicBay
                        </h1>
                        <p class="mt-2 max-w-2xl text-base leading-7 text-muted-foreground">
                            Coordinated care, clearer schedules, and a calmer way to manage appointments.
                        </p>
                    </div>
                </div>

                <section class="mt-10 grid items-center gap-10 lg:grid-cols-2">
                    <div class="rounded-lg bg-background/85 p-6 shadow-sm ring-1 ring-border sm:p-8">
                        <h2 class="text-2xl font-semibold tracking-tight text-foreground">
                            Care that stays organized
                        </h2>

                        <p class="mt-4 text-base leading-7 text-foreground/80">
                            MedicBay helps patients find doctors, review public doctor information, and keep appointments
                            easier to follow. It gives medical staff a shared place to manage schedules, patient activity,
                            and day-to-day clinic work.
                        </p>

                        <p class="mt-4 text-base leading-7 text-foreground/80">
                            The goal is simple: reduce confusion, support reliable communication, and make each visit feel
                            more predictable from booking to follow-up.
                        </p>
                    </div>

                    <div class="overflow-hidden rounded-lg">
                        <img
                            src="/images/doctor.jpg"
                            alt="Medical professional"
                            class="h-[320px] w-full object-cover sm:h-[420px]"
                        />
                    </div>
                </section>

                <section class="mt-10 grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg bg-card/80 p-6 backdrop-blur-sm border border-border">
                        <div class="text-xl font-semibold text-foreground">
                            For patients
                        </div>
                        <p class="mt-3 text-sm leading-6 text-muted-foreground">
                            Browse doctors, view specialisations, and keep appointment information in one place.
                        </p>
                    </div>

                    <div class="rounded-lg bg-card/80 p-6 backdrop-blur-sm border border-border">
                        <div class="text-xl font-semibold text-foreground">
                            For doctors
                        </div>
                        <p class="mt-3 text-sm leading-6 text-muted-foreground">
                            Manage profile details, working hours, and patient appointments with fewer manual steps.
                        </p>
                    </div>

                    <div class="rounded-lg bg-card/80 p-6 backdrop-blur-sm border border-border">
                        <div class="text-xl font-semibold text-foreground">
                            For admins
                        </div>
                        <p class="mt-3 text-sm leading-6 text-muted-foreground">
                            Maintain users, doctor roles, and clinic information from a protected admin area.
                        </p>
                    </div>
                </section>

                <section class="mt-10 grid items-center gap-10 lg:grid-cols-2">
                    <div class="overflow-hidden rounded-lg">
                        <img
                            src="/images/equipment.jpg"
                            alt="Medical equipment"
                            class="h-[300px] w-full object-cover sm:h-[360px]"
                        />
                    </div>

                    <div>
                        <h2 class="section-title text-left">
                            Built around everyday clinic flow
                        </h2>

                        <p class="section-text lg:text-left text-foreground">
                            The platform brings together patient access, doctor profiles, schedules, and administrative
                            controls so a clinic can keep its essential work moving without unnecessary clutter.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <Link href="/doctors" class="btn-primary">
                                View doctors
                            </Link>
                            <Link :href="$page.props.auth.user ? '/dashboard' : '/login'" class="nav-link">
                                Go to dashboard
                            </Link>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
