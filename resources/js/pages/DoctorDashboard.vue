<!-- resources/js/pages/DoctorDashboard.vue -->
<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
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

                    <details class="rounded-2xl bg-card/70 backdrop-blur-sm border border-border">
                        <summary class="cursor-pointer select-none px-6 py-4 text-lg font-semibold text-foreground">
                            Past appointments
                            <span class="ml-2 text-sm text-muted-foreground">
                                ({{ appointments.past.length }})
                            </span>
                        </summary>

                        <div class="px-6 pb-6 space-y-4">
                            <div v-if="appointments.past.length === 0" class="text-muted-foreground">
                                No past appointments today.
                            </div>

                            <div v-for="a in appointments.past" :key="a.appointment_id"
                                class="rounded-2xl bg-background/70 p-5 border border-border">
                                <div>
                                    <div class="text-base font-semibold text-foreground">
                                        {{ a.start_time }} — {{ a.patient_name }}
                                    </div>
                                    <div class="mt-1 text-sm text-muted-foreground">
                                        {{ genderAgeLine(a) }}
                                    </div>
                                </div>
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
                                No future appointments today.
                            </div>

                            <div v-for="a in appointments.future" :key="a.appointment_id"
                                class="rounded-2xl bg-background/70 p-5 flex items-center justify-between gap-4 border border-border">
                                <div>
                                    <div class="text-base font-semibold text-foreground">
                                        {{ a.start_time }} — {{ a.patient_name }}
                                    </div>
                                    <div class="mt-1 text-sm text-muted-foreground">
                                        {{ genderAgeLine(a) }}
                                    </div>
                                </div>

                                <button type="button"
                                    class="rounded-md bg-secondary px-4 py-2 text-sm font-semibold text-secondary-foreground hover:opacity-90"
                                    @click="cancelAppointment(a.appointment_id)">
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
