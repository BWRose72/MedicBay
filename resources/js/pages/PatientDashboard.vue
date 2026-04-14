<!-- resources/js/pages/PatientDashboard.vue -->
<script setup lang="ts">
import { router } from '@inertiajs/vue3';

type PatientAppointmentRow = {
    appointment_id: number;
    doctor_id: number;
    doctor_name: string;
    start_time: string; // "Y-m-d H:i"
    status: string;
    has_left_review: boolean;
    can_review: boolean;
    can_cancel: boolean;
};

type PatientPayload = {
    past: PatientAppointmentRow[];
    future: PatientAppointmentRow[];
};

const props = defineProps<{
    appointments?: PatientPayload;
}>();

const appointments: PatientPayload = props.appointments ?? { past: [], future: [] };

function cancelAppointment(appointmentId: number) {
    router.patch(`/appointments/${appointmentId}/patient-cancel`, {}, { preserveScroll: true });
}
</script>

<template>
    <div class="content-wrap">
        <div class="content-bg"></div>
        <div class="content-overlay"></div>

        <div class="content-foreground">
            <div class="container-main section-spacing">
                <div>
                    <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-foreground">
                        Dashboard
                    </h1>
                    <p class="mt-2 text-base text-muted-foreground">
                        Your appointments
                    </p>
                </div>

                <div class="mt-10 space-y-8">
                    <details class="rounded-2xl bg-card/70 backdrop-blur-sm border border-border">
                        <summary class="cursor-pointer select-none px-6 py-4 text-lg font-semibold text-foreground">
                            Past appointments
                            <span class="ml-2 text-sm text-muted-foreground">
                                ({{ appointments.past.length }})
                            </span>
                        </summary>

                        <div class="px-6 pb-6 space-y-4">
                            <div v-if="appointments.past.length === 0" class="text-muted-foreground">
                                No past appointments.
                            </div>

                            <div v-for="a in appointments.past" :key="a.appointment_id"
                                class="rounded-2xl bg-background/70 p-5 flex items-center justify-between gap-4 border border-border">
                                <div>
                                    <div class="text-base font-semibold text-foreground">
                                        {{ a.start_time }} — {{ a.doctor_name }}
                                    </div>
                                    <div class="mt-1 text-sm text-muted-foreground">
                                        Status: {{ a.status }}
                                    </div>
                                </div>

                                <button type="button" class="rounded-md px-4 py-2 text-sm font-semibold"
                                    :class="a.can_review ? 'bg-secondary text-secondary-foreground hover:opacity-90' : 'bg-muted text-muted-foreground cursor-not-allowed'"
                                    :disabled="!a.can_review">
                                    Review
                                </button>
                            </div>
                        </div>
                    </details>

                    <div class="rounded-2xl bg-card/70 backdrop-blur-sm border border-border p-6">
                        <div class="text-lg font-semibold text-foreground">
                            Future appointments
                            <span class="ml-2 text-sm text-muted-foreground">
                                ({{ appointments.future.length }})
                            </span>
                        </div>

                        <div class="mt-5 space-y-4">
                            <div v-if="appointments.future.length === 0" class="text-muted-foreground">
                                No future appointments.
                            </div>

                            <div v-for="a in appointments.future" :key="a.appointment_id"
                                class="rounded-2xl bg-background/70 p-5 flex items-center justify-between gap-4 border border-border">
                                <div>
                                    <div class="text-base font-semibold text-foreground">
                                        {{ a.start_time }} — {{ a.doctor_name }}
                                    </div>
                                    <div class="mt-1 text-sm text-muted-foreground">
                                        Status: {{ a.status }}
                                    </div>
                                </div>

                                <button
                                    v-if="a.can_cancel"
                                    type="button"
                                    class="rounded-md bg-secondary px-4 py-2 text-sm font-semibold text-secondary-foreground hover:opacity-90"
                                    @click="cancelAppointment(a.appointment_id)"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
