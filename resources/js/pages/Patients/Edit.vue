<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
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
}>();

const form = useForm({
    name: props.patient.name,
    email: props.patient.email,
    gender: props.patient.gender,
    personal_identification_number:
        props.patient.personal_identification_number,
    date_of_birth: props.patient.date_of_birth ?? '',
    phone: props.patient.phone ?? '',
});

function submit() {
    form.patch(`/patients/${props.patient.patient_id}`, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit(`/patients/${props.patient.patient_id}`);
        },
    });
}
</script>

<template>
    <Head title="Edit Patient Profile" />

    <div class="content-wrap">
        <div class="content-bg"></div>
        <div class="content-overlay"></div>

        <div class="content-foreground">
            <div class="container-main section-spacing">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1
                            class="text-3xl font-semibold tracking-tight text-foreground sm:text-4xl"
                        >
                            Edit profile
                        </h1>
                        <p class="mt-2 text-base text-muted-foreground">
                            {{ props.patient.name }} ·
                            {{ props.patient.email }}
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        <button
                            type="submit"
                            form="patient-editor-form"
                            class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            Save
                        </button>

                        <Link
                            :href="`/patients/${props.patient.patient_id}`"
                            class="nav-link"
                        >
                            Back
                        </Link>
                    </div>
                </div>

                <form
                    id="patient-editor-form"
                    class="mt-10 space-y-8"
                    @submit.prevent="submit"
                >
                    <section
                        class="space-y-5 rounded-lg border border-border bg-card/70 p-6 backdrop-blur-sm"
                    >
                        <h2
                            class="text-xl font-semibold tracking-tight text-foreground"
                        >
                            Account details
                        </h2>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label
                                    class="block text-sm font-semibold text-foreground"
                                    for="patient-name"
                                >
                                    Name
                                </label>
                                <input
                                    id="patient-name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                />
                                <div
                                    v-if="form.errors.name"
                                    class="mt-2 text-sm text-destructive"
                                >
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-semibold text-foreground"
                                    for="patient-email"
                                >
                                    Email
                                </label>
                                <input
                                    id="patient-email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                />
                                <div
                                    v-if="form.errors.email"
                                    class="mt-2 text-sm text-destructive"
                                >
                                    {{ form.errors.email }}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        class="space-y-5 rounded-lg border border-border bg-card/70 p-6 backdrop-blur-sm"
                    >
                        <h2
                            class="text-xl font-semibold tracking-tight text-foreground"
                        >
                            Patient details
                        </h2>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label
                                    class="block text-sm font-semibold text-foreground"
                                    for="patient-phone"
                                >
                                    Phone
                                </label>
                                <input
                                    id="patient-phone"
                                    v-model="form.phone"
                                    type="text"
                                    class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                />
                                <div
                                    v-if="form.errors.phone"
                                    class="mt-2 text-sm text-destructive"
                                >
                                    {{ form.errors.phone }}
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-semibold text-foreground"
                                    for="patient-gender"
                                >
                                    Gender
                                </label>
                                <input
                                    id="patient-gender"
                                    v-model="form.gender"
                                    type="text"
                                    class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                />
                                <div
                                    v-if="form.errors.gender"
                                    class="mt-2 text-sm text-destructive"
                                >
                                    {{ form.errors.gender }}
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-semibold text-foreground"
                                    for="patient-date-of-birth"
                                >
                                    Date of birth
                                </label>
                                <input
                                    id="patient-date-of-birth"
                                    v-model="form.date_of_birth"
                                    type="date"
                                    class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                />
                                <div
                                    v-if="form.errors.date_of_birth"
                                    class="mt-2 text-sm text-destructive"
                                >
                                    {{ form.errors.date_of_birth }}
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-semibold text-foreground"
                                    for="patient-pin"
                                >
                                    Personal identification number
                                </label>
                                <input
                                    id="patient-pin"
                                    v-model="
                                        form.personal_identification_number
                                    "
                                    type="text"
                                    class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                />
                                <div
                                    v-if="
                                        form.errors
                                            .personal_identification_number
                                    "
                                    class="mt-2 text-sm text-destructive"
                                >
                                    {{
                                        form.errors
                                            .personal_identification_number
                                    }}
                                </div>
                            </div>
                        </div>
                    </section>
                </form>
            </div>
        </div>
    </div>
</template>
