<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppPageLayout from '@/layouts/AppPageLayout.vue';

defineOptions({ layout: AppPageLayout });

type PatientPayload = {
    patient_id: number;
    user_id: number;
    name: string;
    email: string;
    gender: string;
    personal_identification_number: string;
    date_of_birth: string | null;
    age: number | null;
    phone: string;
};

const props = defineProps<{
    patient: PatientPayload;
    can_edit: boolean;
}>();
</script>

<template>
    <Head :title="props.patient.name || 'Patient Profile'" />

    <div class="content-wrap">
        <div class="content-bg"></div>
        <div class="content-overlay"></div>

        <div class="content-foreground">
            <div class="container-main section-spacing">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1
                            class="text-3xl font-semibold tracking-tight text-foreground sm:text-4xl"
                        >
                            {{ props.patient.name || 'Patient Profile' }}
                        </h1>
                        <p class="mt-2 text-base text-muted-foreground">
                            {{ props.patient.email }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link
                            v-if="props.can_edit"
                            :href="`/patients/${props.patient.patient_id}/edit`"
                            class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90"
                        >
                            Edit
                        </Link>

                        <Link href="/dashboard" class="nav-link">Back</Link>
                    </div>
                </div>

                <div class="mt-10 grid gap-6 lg:grid-cols-[1fr_360px]">
                    <section
                        class="rounded-lg border border-border bg-card/80 p-8 backdrop-blur-sm"
                    >
                        <h2
                            class="text-xl font-semibold tracking-tight text-foreground"
                        >
                            Profile details
                        </h2>

                        <div class="mt-6 grid gap-5 text-base sm:grid-cols-2">
                            <div>
                                <div
                                    class="text-sm font-semibold text-muted-foreground"
                                >
                                    Full name
                                </div>
                                <div class="mt-1 text-foreground">
                                    {{ props.patient.name || '-' }}
                                </div>
                            </div>

                            <div>
                                <div
                                    class="text-sm font-semibold text-muted-foreground"
                                >
                                    Email
                                </div>
                                <div class="mt-1 break-words text-foreground">
                                    {{ props.patient.email || '-' }}
                                </div>
                            </div>

                            <div>
                                <div
                                    class="text-sm font-semibold text-muted-foreground"
                                >
                                    Phone
                                </div>
                                <div class="mt-1 text-foreground">
                                    {{ props.patient.phone || '-' }}
                                </div>
                            </div>

                            <div>
                                <div
                                    class="text-sm font-semibold text-muted-foreground"
                                >
                                    Gender
                                </div>
                                <div class="mt-1 text-foreground">
                                    {{ props.patient.gender || '-' }}
                                </div>
                            </div>

                            <div>
                                <div
                                    class="text-sm font-semibold text-muted-foreground"
                                >
                                    Date of birth
                                </div>
                                <div class="mt-1 text-foreground">
                                    {{ props.patient.date_of_birth || '-' }}
                                </div>
                            </div>

                            <div>
                                <div
                                    class="text-sm font-semibold text-muted-foreground"
                                >
                                    Age
                                </div>
                                <div class="mt-1 text-foreground">
                                    {{ props.patient.age ?? '-' }}
                                </div>
                            </div>
                        </div>
                    </section>

                    <aside
                        class="rounded-lg border border-border bg-card/70 p-6 backdrop-blur-sm"
                    >
                        <h2
                            class="text-xl font-semibold tracking-tight text-foreground"
                        >
                            Identifier
                        </h2>
                        <div class="mt-4 text-sm text-muted-foreground">
                            Personal identification number
                        </div>
                        <div
                            class="mt-2 rounded-md border border-border bg-background/70 px-3 py-2 break-words text-foreground"
                        >
                            {{
                                props.patient.personal_identification_number ||
                                '-'
                            }}
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</template>
