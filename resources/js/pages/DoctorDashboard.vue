<!-- resources/js/pages/DoctorDashboard.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppPageLayout from '@/layouts/AppPageLayout.vue'

defineOptions({ layout: AppPageLayout })

type DoctorAppointmentBox = {
    appointment_id: number
    start_time: string // "Y-m-d H:i"
    time: string // "H:i"
    ends_at: string // "Y-m-d H:i"
    status?: string
    patient_name: string
    patient_gender: string
    patient_age: number | null | undefined
}

type DoctorPayload = {
    past: DoctorAppointmentBox[]
    current: DoctorAppointmentBox | null
    future: DoctorAppointmentBox[]
}

const props = defineProps<{
    appointments?: DoctorPayload
}>()

const page = usePage()
const appointments = computed<DoctorPayload>(() => {
    return (
        props.appointments ??
        (page.props as any)?.appointments ?? {
            past: [],
            current: null,
            future: [],
        }
    )
})

function genderAgeLine(a: DoctorAppointmentBox): string {
    const g = (a.patient_gender || '').trim()
    const age = a.patient_age ?? null

    if (g && age !== null) return `${g}, ${age}`
    if (g) return g
    if (age !== null) return `${age}`
    return ''
}

function cancelAppointment(id: number) {
    router.patch(`/appointments/${id}/cancel`, {}, { preserveScroll: true })
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
                        Today’s appointments
                    </p>
                </div>

                <div class="mt-10 space-y-10">
                    <!-- Current -->
                    <div>
                        <div class="mb-3 text-lg font-semibold text-foreground">Current</div>

                        <div v-if="appointments.current"
                            class="flex items-stretch overflow-hidden rounded-2xl bg-primary/50 border border-border">
                            <div class="flex w-28 items-center justify-center border-r border-border px-3 py-5">
                                <div class="text-xl font-semibold text-foreground">
                                    {{ appointments.current.time }}
                                </div>
                            </div>

                            <div class="flex flex-1 flex-col justify-center px-5 py-5">
                                <div class="text-base font-semibold text-foreground">
                                    {{ appointments.current.patient_name }}
                                </div>
                                <div class="mt-1 text-sm text-foreground/80">
                                    {{ genderAgeLine(appointments.current) }}
                                </div>
                            </div>
                        </div>

                        <div v-else
                            class="rounded-2xl bg-card/70 backdrop-blur-sm border border-border p-6 text-muted-foreground">
                            No current appointment.
                        </div>
                    </div>

                    <!-- Future -->
                    <div>
                        <div class="mb-3 text-lg font-semibold text-foreground">Future</div>

                        <div v-if="appointments.future.length" class="space-y-3">
                            <div v-for="a in appointments.future" :key="a.appointment_id"
                                class="flex items-stretch overflow-hidden rounded-2xl bg-primary/50 border border-border">
                                <div
                                    class="flex w-28 flex-col items-center justify-center gap-3 border-r border-border px-3 py-5">
                                    <div class="text-xl font-semibold text-foreground">{{ a.time }}</div>

                                    <!-- Cancel button (per future appointment) -->
                                    <button type="button" class="btn-secondary !px-3 !py-2 text-xs"
                                        @click="cancelAppointment(a.appointment_id)">
                                        Cancel
                                    </button>
                                </div>

                                <div class="flex flex-1 flex-col justify-center px-5 py-5">
                                    <div class="text-base font-semibold text-foreground">{{ a.patient_name }}</div>
                                    <div class="mt-1 text-sm text-foreground/80">
                                        {{ genderAgeLine(a) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else
                            class="rounded-2xl bg-card/70 backdrop-blur-sm border border-border p-6 text-muted-foreground">
                            No future appointments today.
                        </div>
                    </div>

                    <!-- Past -->
                    <div>
                        <div class="mb-3 text-lg font-semibold text-foreground">Past</div>

                        <div v-if="appointments.past.length" class="space-y-3">
                            <div v-for="a in appointments.past" :key="a.appointment_id"
                                class="flex items-stretch overflow-hidden rounded-2xl bg-primary/50 border border-border">
                                <div class="flex w-28 items-center justify-center border-r border-border px-3 py-5">
                                    <div class="text-xl font-semibold text-foreground">{{ a.time }}</div>
                                </div>

                                <div class="flex flex-1 flex-col justify-center px-5 py-5">
                                    <div class="text-base font-semibold text-foreground">{{ a.patient_name }}</div>
                                    <div class="mt-1 text-sm text-foreground/80">
                                        {{ genderAgeLine(a) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else
                            class="rounded-2xl bg-card/70 backdrop-blur-sm border border-border p-6 text-muted-foreground">
                            No past appointments today.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>