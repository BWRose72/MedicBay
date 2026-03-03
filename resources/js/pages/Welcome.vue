<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import AppPageLayout from '@/layouts/AppPageLayout.vue';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    { canRegister: true },
);

defineOptions({
    layout: AppPageLayout,
});
</script>

<template>
    <Head title="MedicBay" />

    <div class="min-h-screen text-slate-900">
        <!-- CONTENT AREA (background glued to bottom here) -->
        <main class="content-wrap">
            <!-- Bottom-glued background -->
            <div class="content-bg" aria-hidden="true"></div>
            <div class="content-foreground">
                <!-- HERO -->
                <section class="relative overflow-hidden">
                    <div class="container-main section-spacing">
                        <!-- Green box behind hero+label -->
                        <div class="rounded-3xl bg-primary/75 p-6 sm:p-10">
                            <div class="grid items-center gap-10 lg:grid-cols-2">
                                <!-- Left: slogan/label -->
                                <div class="text-center lg:text-left">
                                    <h1 class="mt-4 text-4xl font-semibold tracking-tight text-foreground sm:text-5xl">
                                        Care you can rely on, with scheduling that stays simple.
                                    </h1>

                                    <p class="mx-auto mt-4 max-w-xl text-base leading-7 text-foreground/80 lg:mx-0">
                                        MedicBay helps patients and clinicians manage appointments and workflows efficiently.
                                    </p>
                                </div>

                                <!-- Right: hero image -->
                                <div class="overflow-hidden rounded-2xl">
                                    <img src="/images/hero.jpg" alt="MedicBay hero" class="h-[360px] w-full object-cover sm:h-[420px]" />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- DOCTOR SECTION -->
                <section class="container-main section-spacing grid lg:grid-cols-2 gap-10 items-center">
                    <div>
                        <img src="/images/doctor.jpg" class="feature-image" />
                    </div>

                    <div>
                        <h2 class="section-title text-left">
                            Professionals you can trust
                        </h2>

                        <p class="section-text lg:text-left">
                            Our experienced medical staff ensures structured, reliable care.
                        </p>

                        <div class="mt-6">
                            <Link href="/appointments/create" class="btn-primary">
                                Make an appointment
                            </Link>
                        </div>
                    </div>
                </section>

                <!-- EQUIPMENT SECTION -->
                <section class="container-main section-spacing grid lg:grid-cols-2 gap-10 items-center">
                    <div>
                        <h2 class="section-title text-left">
                            State-of-the-art medical equipment
                        </h2>

                        <p class="section-text lg:text-left text-foreground">
                            Modern tools supporting high-quality diagnostics and treatment.
                        </p>
                    </div>

                    <div>
                        <img src="/images/equipment.jpg" class="feature-image" />
                    </div>
                </section>

            </div>
        </main>
    </div>
</template>