<!-- resources/js/pages/DoctorDashboard.vue -->
<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

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
const cancellingAppointmentIds = ref<Set<number>>(new Set())
const hiddenFutureAppointmentIds = ref<Set<number>>(new Set())
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
const visibleFutureAppointments = computed<DoctorAppointmentBox[]>(() =>
    appointments.value.future.filter((a) => !hiddenFutureAppointmentIds.value.has(a.appointment_id)),
)

function genderAgeLine(a: DoctorAppointmentBox): string {
    const g = (a.patient_gender || '').trim()
    const age = a.patient_age ?? null

    if (g && age !== null) return `${g}, ${age}`
    if (g) return g
    if (age !== null) return `${age}`
    return ''
}

function cancelAppointment(id: number) {
    if (cancellingAppointmentIds.value.has(id)) {
        return
    }

    hiddenFutureAppointmentIds.value = new Set(hiddenFutureAppointmentIds.value).add(id)
    cancellingAppointmentIds.value = new Set(cancellingAppointmentIds.value).add(id)

    router.patch(`/appointments/${id}/cancel`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({
                only: ['appointments'],
            })
        },
        onError: () => {
            const hiddenNext = new Set(hiddenFutureAppointmentIds.value)
            hiddenNext.delete(id)
            hiddenFutureAppointmentIds.value = hiddenNext
        },
        onFinish: () => {
            const next = new Set(cancellingAppointmentIds.value)
            next.delete(id)
            cancellingAppointmentIds.value = next
        },
    })
}

function isCancellingAppointment(id: number): boolean {
    return cancellingAppointmentIds.value.has(id)
}

function statusLabel(status?: string): string {
    const value = (status ?? '').trim().toLowerCase()

    if (value === 'completed') return 'Completed'
    if (value === 'cancelled') return 'Cancelled'
    if (value === 'no_show') return "Didn't show up"
    return 'Scheduled'
}

function setAppointmentStatus(id: number, status: string) {
    router.patch(
        `/appointments/${id}/status`,
        { status },
        {
            preserveScroll: true,
        },
    )
}
</script>

<template>
    <div class="space-y-6">
                    <div>
                        <div class="mb-3 text-lg font-semibold text-foreground">Current</div>

                        <div v-if="appointments.current"
                            class="flex items-stretch overflow-hidden rounded-lg bg-card/85 border border-border shadow-sm backdrop-blur-sm">
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
                                <div class="mt-1 text-sm text-muted-foreground">
                                    Status: {{ statusLabel(appointments.current.status) }}
                                </div>
                            </div>
                        </div>

                        <div v-else
                            class="rounded-lg bg-card/70 backdrop-blur-sm border border-border p-6 text-muted-foreground">
                            No current appointment.
                        </div>
                    </div>

                    <details class="rounded-lg bg-card/70 backdrop-blur-sm border border-border">
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
                                class="rounded-lg bg-background/70 p-5 border border-border flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="text-base font-semibold text-foreground">
                                        {{ a.start_time }} - {{ a.patient_name }}
                                    </div>
                                    <div class="mt-1 text-sm text-muted-foreground">
                                        {{ genderAgeLine(a) }}
                                    </div>
                                    <div class="mt-1 text-sm text-muted-foreground">
                                        Status: {{ statusLabel(a.status) }}
                                    </div>
                                </div>

                                <div class="shrink-0">
                                    <select v-if="(a.status ?? 'scheduled') === 'scheduled'"
                                        class="rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
                                        :value="a.status ?? 'scheduled'"
                                        @change="setAppointmentStatus(a.appointment_id, String(($event.target as HTMLSelectElement).value))">
                                        <option disabled value="scheduled">Set status...</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                        <option value="no_show">Didn't show up</option>
                                    </select>

                                    <div v-else class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white">
                                        {{ statusLabel(a.status) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </details>

                    <div class="rounded-lg bg-card/70 backdrop-blur-sm border border-border p-6">
                        <div class="text-lg font-semibold text-foreground">
                            Future appointments
                            <span class="ml-2 text-sm text-muted-foreground">
                                ({{ visibleFutureAppointments.length }})
                            </span>
                        </div>

                        <div class="mt-5 space-y-4">
                            <div v-if="visibleFutureAppointments.length === 0" class="text-muted-foreground">
                                No future appointments today.
                            </div>

                            <div v-for="a in visibleFutureAppointments" :key="a.appointment_id"
                                class="rounded-lg bg-background/70 p-5 flex items-center justify-between gap-4 border border-border">
                                <div>
                                    <div class="text-base font-semibold text-foreground">
                                        {{ a.start_time }} - {{ a.patient_name }}
                                    </div>
                                    <div class="mt-1 text-sm text-muted-foreground">
                                        {{ genderAgeLine(a) }}
                                    </div>
                                    <div class="mt-1 text-sm text-muted-foreground">
                                        Status: {{ statusLabel(a.status) }}
                                    </div>
                                </div>

                                <button type="button"
                                    class="rounded-md bg-secondary px-4 py-2 text-sm font-semibold text-secondary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="isCancellingAppointment(a.appointment_id)"
                                    @click="cancelAppointment(a.appointment_id)">
                                    {{ isCancellingAppointment(a.appointment_id) ? 'Cancelling...' : 'Cancel' }}
                                </button>
                            </div>
                        </div>
                    </div>
    </div>
</template>
