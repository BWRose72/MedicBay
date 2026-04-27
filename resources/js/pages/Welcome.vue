<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppPageLayout from '@/layouts/AppPageLayout.vue';
import { dashboard, login, register } from '@/routes';

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

    <main class="min-h-screen bg-background text-foreground">
        <section class="relative min-h-[calc(100vh-4rem)] overflow-hidden border-b border-border">
            <img
                src="/images/hero.jpg"
                alt="MedicBay clinical reception"
                class="absolute inset-0 h-full w-full object-cover object-top"
            />
            <div class="absolute inset-0 bg-white/82"></div>
            <div class="absolute inset-y-0 left-0 w-full bg-gradient-to-r from-background via-background/92 to-background/25"></div>

            <div class="container-main relative grid min-h-[calc(100vh-4rem)] gap-10 py-12 sm:py-16 lg:grid-cols-[1fr_380px] lg:items-center">
                <div class="max-w-3xl">
                    <div class="mb-5 inline-flex rounded-md border border-border bg-background/85 px-3 py-1 text-sm font-semibold text-secondary shadow-sm">
                        Family medicine and specialist appointments
                    </div>

                    <h1 class="text-4xl font-semibold tracking-tight text-foreground sm:text-5xl lg:text-6xl">
                        Reliable medical care, arranged around your day
                    </h1>

                    <p class="mt-5 max-w-2xl text-lg leading-8 text-muted-foreground">
                        Book consultations with trusted doctors, review available appointment times, and keep your visits organized from one calm place.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <Link
                            :href="$page.props.auth.user ? dashboard() : login()"
                            class="inline-flex items-center justify-center rounded-md bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground hover:opacity-90"
                        >
                            Make an appointment
                        </Link>
                        <Link
                            href="/doctors"
                            class="inline-flex items-center justify-center rounded-md border border-border bg-background/80 px-5 py-3 text-sm font-semibold text-foreground hover:bg-muted"
                        >
                            Find a doctor
                        </Link>
                        <Link
                            v-if="canRegister && !$page.props.auth.user"
                            :href="register()"
                            class="inline-flex items-center justify-center rounded-md px-5 py-3 text-sm font-semibold text-foreground hover:bg-muted"
                        >
                            Create account
                        </Link>
                    </div>

                    <div class="mt-10 grid max-w-2xl gap-4 border-t border-border pt-6 sm:grid-cols-3">
                        <div>
                            <div class="text-2xl font-semibold text-foreground">Same day</div>
                            <div class="mt-1 text-sm text-muted-foreground">appointments when available</div>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold text-foreground">20+</div>
                            <div class="mt-1 text-sm text-muted-foreground">medical specialities</div>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold text-foreground">Online</div>
                            <div class="mt-1 text-sm text-muted-foreground">booking and visit history</div>
                        </div>
                    </div>
                </div>

                <aside class="rounded-lg border border-border bg-card/90 p-5 shadow-sm backdrop-blur-sm">
                    <div class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Today at MedicBay</div>
                    <div class="mt-5 space-y-4">
                        <div class="rounded-lg border border-border bg-background/80 p-4">
                            <div class="text-sm font-semibold text-foreground">General consultations</div>
                            <p class="mt-1 text-sm leading-6 text-muted-foreground">
                                Routine checkups, referrals, prescriptions, and follow-up visits.
                            </p>
                        </div>
                        <div class="rounded-lg border border-border bg-background/80 p-4">
                            <div class="text-sm font-semibold text-foreground">Specialist appointments</div>
                            <p class="mt-1 text-sm leading-6 text-muted-foreground">
                                Cardiology, dermatology, endocrinology, orthopedics, and more.
                            </p>
                        </div>
                        <div class="rounded-lg border border-border bg-background/80 p-4">
                            <div class="text-sm font-semibold text-foreground">Opening hours</div>
                            <p class="mt-1 text-sm leading-6 text-muted-foreground">
                                Monday to Friday, 08:00-18:00. Limited Saturday appointments.
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="container-main py-14">
            <div class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
                <div>
                    <div class="text-sm font-semibold text-secondary">Care that starts clearly</div>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight text-foreground">
                        Choose the right doctor before you book
                    </h2>
                    <p class="mt-4 max-w-xl text-base leading-7 text-muted-foreground">
                        See each doctor's speciality, profile details, available appointment times, and patient reviews before choosing a visit.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-lg border border-border bg-card/85 p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-foreground">Primary care</h3>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            First consultations, ongoing health concerns, documentation, and treatment planning.
                        </p>
                    </div>
                    <div class="rounded-lg border border-border bg-card/85 p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-foreground">Specialist care</h3>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            Focused appointments for specific symptoms, chronic conditions, and second opinions.
                        </p>
                    </div>
                    <div class="rounded-lg border border-border bg-card/85 p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-foreground">Follow-up visits</h3>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            Keep treatment progress visible with clear past and upcoming appointment records.
                        </p>
                    </div>
                    <div class="rounded-lg border border-border bg-card/85 p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-foreground">Patient feedback</h3>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            Leave a review after completed appointments and read real feedback from other patients.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y border-border bg-muted/40">
            <div class="container-main grid gap-10 py-16 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                <div>
                    <div class="text-sm font-semibold text-secondary">What to expect</div>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight text-foreground">
                        A calmer way to plan a medical visit
                    </h2>
                    <p class="mt-4 max-w-xl text-base leading-7 text-muted-foreground">
                        MedicBay keeps the appointment process simple: pick a doctor, choose a time, arrive prepared, and keep a record of completed visits.
                    </p>

                    <div class="mt-7 space-y-4">
                        <div class="flex gap-4 rounded-lg border border-border bg-background/85 p-4">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary text-sm font-semibold text-primary-foreground">1</div>
                            <div>
                                <div class="font-semibold text-foreground">Search by speciality</div>
                                <p class="mt-1 text-sm leading-6 text-muted-foreground">Compare doctors and choose a consultation that fits your concern.</p>
                            </div>
                        </div>
                        <div class="flex gap-4 rounded-lg border border-border bg-background/85 p-4">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary text-sm font-semibold text-primary-foreground">2</div>
                            <div>
                                <div class="font-semibold text-foreground">Book an available time</div>
                                <p class="mt-1 text-sm leading-6 text-muted-foreground">Select a future appointment and keep the visit visible in your account.</p>
                            </div>
                        </div>
                        <div class="flex gap-4 rounded-lg border border-border bg-background/85 p-4">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary text-sm font-semibold text-primary-foreground">3</div>
                            <div>
                                <div class="font-semibold text-foreground">Manage your visits</div>
                                <p class="mt-1 text-sm leading-6 text-muted-foreground">Review upcoming appointments, past consultations, and completed visit feedback.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <img
                    src="/images/doctor.jpg"
                    alt="Doctor consulting with patient"
                    class="h-[420px] w-full rounded-lg object-cover"
                />
            </div>
        </section>

        <section class="container-main grid gap-10 py-16 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
            <img
                src="/images/equipment.jpg"
                alt="Modern clinical equipment"
                class="h-[380px] w-full rounded-lg object-cover"
            />

            <div>
                <div class="text-sm font-semibold text-secondary">Modern clinic standards</div>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-foreground">
                    Practical care, without unnecessary waiting-room friction
                </h2>
                <p class="mt-4 max-w-xl text-base leading-7 text-muted-foreground">
                    The page gives patients enough information to act confidently: who they are booking with, when they can be seen, and what happens after the visit.
                </p>

                <div class="mt-7 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg border border-border bg-card/85 p-4 shadow-sm">
                        <div class="text-sm font-semibold text-foreground">Transparent scheduling</div>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">Appointment availability is shown before booking.</p>
                    </div>
                    <div class="rounded-lg border border-border bg-card/85 p-4 shadow-sm">
                        <div class="text-sm font-semibold text-foreground">Visit continuity</div>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">Past visits and reviews stay connected to your care history.</p>
                    </div>
                </div>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <Link
                        :href="$page.props.auth.user ? dashboard() : login()"
                        class="inline-flex items-center justify-center rounded-md bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Book your visit
                    </Link>
                    <Link
                        href="/about"
                        class="inline-flex items-center justify-center rounded-md border border-border bg-background/80 px-5 py-3 text-sm font-semibold text-foreground hover:bg-muted"
                    >
                        About MedicBay
                    </Link>
                </div>
            </div>
        </section>
    </main>
</template>
