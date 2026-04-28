<!-- resources/js/pages/PatientDashboard.vue -->
<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type PatientAppointmentRow = {
    appointment_id: number;
    doctor_id: number;
    doctor_name: string;
    start_time: string; // "Y-m-d H:i"
    status: string;
    has_left_review: boolean;
    can_review: boolean;
    can_cancel: boolean;
    review_attitude: number | null;
    review_professionalism: number | null;
};

type PatientPayload = {
    current: PatientAppointmentRow | null;
    past: PatientAppointmentRow[];
    future: PatientAppointmentRow[];
};

const props = defineProps<{
    appointments?: PatientPayload;
}>();

const page = usePage();
const appointments = computed<PatientPayload>(() => {
    return (
        props.appointments ??
        (page.props as any)?.appointments ?? {
            current: null,
            past: [],
            future: [],
        }
    );
});
const cancellingAppointmentIds = ref<Set<number>>(new Set());
const hiddenFutureAppointmentIds = ref<Set<number>>(new Set());
const visibleFutureAppointments = computed<PatientAppointmentRow[]>(() =>
    appointments.value.future.filter(
        (a) => !hiddenFutureAppointmentIds.value.has(a.appointment_id),
    ),
);
const reviewError = computed<string | null>(
    () => (page.props as any)?.errors?.review ?? null,
);
const reviewFormForAppointmentId = ref<number | null>(null);
const submittingReviewForAppointmentId = ref<number | null>(null);
const reviewForm = ref({
    attitude: 0,
    professionalism: 0,
});
const reviewValidationError = ref<string | null>(null);

function cancelAppointment(appointmentId: number) {
    if (cancellingAppointmentIds.value.has(appointmentId)) {
        return;
    }

    hiddenFutureAppointmentIds.value = new Set(
        hiddenFutureAppointmentIds.value,
    ).add(appointmentId);
    cancellingAppointmentIds.value = new Set(
        cancellingAppointmentIds.value,
    ).add(appointmentId);

    router.patch(
        `/appointments/${appointmentId}/patient-cancel`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({
                    only: ['appointments'],
                });
            },
            onError: () => {
                const hiddenNext = new Set(hiddenFutureAppointmentIds.value);
                hiddenNext.delete(appointmentId);
                hiddenFutureAppointmentIds.value = hiddenNext;
            },
            onFinish: () => {
                const next = new Set(cancellingAppointmentIds.value);
                next.delete(appointmentId);
                cancellingAppointmentIds.value = next;
            },
        },
    );
}

function isCancellingAppointment(id: number): boolean {
    return cancellingAppointmentIds.value.has(id);
}

function statusLabel(status?: string): string {
    const value = (status ?? '').trim().toLowerCase();

    if (value === 'completed') return 'Completed';
    if (value === 'cancelled') return 'Cancelled';
    if (value === 'no_show') return "Didn't show up";
    return 'Scheduled';
}

function isReviewFormOpen(appointmentId: number): boolean {
    return reviewFormForAppointmentId.value === appointmentId;
}

function openReviewForm(appointmentId: number): void {
    reviewFormForAppointmentId.value = appointmentId;
    reviewForm.value = {
        attitude: 0,
        professionalism: 0,
    };
    reviewValidationError.value = null;
}

function closeReviewForm(): void {
    reviewFormForAppointmentId.value = null;
    reviewValidationError.value = null;
}

function setReviewRating(
    field: 'attitude' | 'professionalism',
    rating: number,
): void {
    reviewForm.value[field] = rating;
    reviewValidationError.value = null;
}

function submitReview(appointmentId: number): void {
    if (submittingReviewForAppointmentId.value !== null) {
        return;
    }

    if (
        reviewForm.value.attitude < 1 ||
        reviewForm.value.attitude > 10 ||
        reviewForm.value.professionalism < 1 ||
        reviewForm.value.professionalism > 10
    ) {
        reviewValidationError.value =
            'Both ratings are required and must be between 1 and 10 stars.';
        return;
    }

    submittingReviewForAppointmentId.value = appointmentId;
    reviewValidationError.value = null;

    router.post(
        `/appointments/${appointmentId}/review`,
        {
            attitude: reviewForm.value.attitude,
            professionalism: reviewForm.value.professionalism,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeReviewForm();
                router.reload({
                    only: ['appointments'],
                });
            },
            onFinish: () => {
                submittingReviewForAppointmentId.value = null;
            },
        },
    );
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <div class="mb-3 text-lg font-semibold text-foreground">
                Current
            </div>

            <div
                v-if="appointments.current"
                class="flex items-center justify-between gap-4 rounded-lg border border-border bg-card/85 p-5 shadow-sm backdrop-blur-sm"
            >
                <div>
                    <div class="text-base font-semibold text-foreground">
                        {{ appointments.current.start_time }} -
                        {{ appointments.current.doctor_name }}
                    </div>
                    <div class="mt-1 text-sm text-muted-foreground">
                        Status: {{ statusLabel(appointments.current.status) }}
                    </div>
                </div>
            </div>

            <div
                v-else
                class="rounded-lg border border-border bg-card/70 p-6 text-muted-foreground backdrop-blur-sm"
            >
                No current appointment.
            </div>
        </div>

        <details
            class="rounded-lg border border-border bg-card/70 backdrop-blur-sm"
        >
            <summary
                class="cursor-pointer px-6 py-4 text-lg font-semibold text-foreground select-none"
            >
                Past appointments
                <span class="ml-2 text-sm text-muted-foreground">
                    ({{ appointments.past.length }})
                </span>
            </summary>

            <div class="space-y-4 px-6 pb-6">
                <div
                    v-if="appointments.past.length === 0"
                    class="text-muted-foreground"
                >
                    No past appointments.
                </div>

                <div
                    v-for="a in appointments.past"
                    :key="a.appointment_id"
                    class="rounded-lg border border-border bg-background/70 p-5"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div
                                class="text-base font-semibold text-foreground"
                            >
                                {{ a.start_time }} - {{ a.doctor_name }}
                            </div>
                            <div class="mt-1 text-sm text-muted-foreground">
                                Status: {{ statusLabel(a.status) }}
                            </div>
                        </div>

                        <button
                            type="button"
                            class="rounded-md px-4 py-2 text-sm font-semibold"
                            v-if="!a.has_left_review"
                            :class="
                                a.can_review
                                    ? 'bg-secondary text-secondary-foreground hover:opacity-90'
                                    : 'cursor-not-allowed bg-muted text-muted-foreground'
                            "
                            :disabled="
                                !a.can_review ||
                                submittingReviewForAppointmentId ===
                                    a.appointment_id
                            "
                            @click="openReviewForm(a.appointment_id)"
                        >
                            {{
                                a.status === 'no_show'
                                    ? "Can't Review"
                                    : 'Review'
                            }}
                        </button>

                        <div
                            v-else
                            class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white"
                        >
                            [Pr: {{ a.review_professionalism ?? '-' }} Att:
                            {{ a.review_attitude ?? '-' }}]
                        </div>
                    </div>

                    <form
                        v-if="isReviewFormOpen(a.appointment_id)"
                        class="mt-4 space-y-4 rounded-lg border border-border bg-card/60 p-4"
                        @submit.prevent="submitReview(a.appointment_id)"
                    >
                        <div>
                            <div class="text-sm font-medium text-foreground">
                                Professionalism (0-10)
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-1">
                                <button
                                    type="button"
                                    class="rounded border border-border px-2 py-1 text-xs text-muted-foreground hover:bg-muted/50"
                                    @click="
                                        setReviewRating('professionalism', 0)
                                    "
                                >
                                    0
                                </button>
                                <button
                                    v-for="n in 10"
                                    :key="`professionalism-${a.appointment_id}-${n}`"
                                    type="button"
                                    class="text-xl leading-none"
                                    :class="
                                        n <= reviewForm.professionalism
                                            ? 'text-amber-500'
                                            : 'text-muted-foreground/40'
                                    "
                                    @click="
                                        setReviewRating('professionalism', n)
                                    "
                                >
                                    ★
                                </button>
                                <span class="ml-2 text-sm text-muted-foreground"
                                    >{{ reviewForm.professionalism }}/10</span
                                >
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-foreground">
                                Attitude (0-10)
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-1">
                                <button
                                    type="button"
                                    class="rounded border border-border px-2 py-1 text-xs text-muted-foreground hover:bg-muted/50"
                                    @click="setReviewRating('attitude', 0)"
                                >
                                    0
                                </button>
                                <button
                                    v-for="n in 10"
                                    :key="`attitude-${a.appointment_id}-${n}`"
                                    type="button"
                                    class="text-xl leading-none"
                                    :class="
                                        n <= reviewForm.attitude
                                            ? 'text-amber-500'
                                            : 'text-muted-foreground/40'
                                    "
                                    @click="setReviewRating('attitude', n)"
                                >
                                    ★
                                </button>
                                <span class="ml-2 text-sm text-muted-foreground"
                                    >{{ reviewForm.attitude }}/10</span
                                >
                            </div>
                        </div>

                        <div v-if="reviewError" class="text-sm text-red-500">
                            {{ reviewError }}
                        </div>
                        <div
                            v-if="reviewValidationError"
                            class="text-sm text-red-500"
                        >
                            {{ reviewValidationError }}
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="submit"
                                class="rounded-md bg-secondary px-4 py-2 text-sm font-semibold text-secondary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="
                                    submittingReviewForAppointmentId ===
                                    a.appointment_id
                                "
                            >
                                {{
                                    submittingReviewForAppointmentId ===
                                    a.appointment_id
                                        ? 'Submitting...'
                                        : 'Submit review'
                                }}
                            </button>
                            <button
                                type="button"
                                class="rounded-md border border-border px-4 py-2 text-sm text-muted-foreground hover:bg-muted/40"
                                @click="closeReviewForm"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </details>

        <div
            class="rounded-lg border border-border bg-card/70 p-6 backdrop-blur-sm"
        >
            <div class="text-lg font-semibold text-foreground">
                Future appointments
                <span class="ml-2 text-sm text-muted-foreground">
                    ({{ visibleFutureAppointments.length }})
                </span>
            </div>

            <div class="mt-5 space-y-4">
                <div
                    v-if="visibleFutureAppointments.length === 0"
                    class="text-muted-foreground"
                >
                    No future appointments.
                </div>

                <div
                    v-for="a in visibleFutureAppointments"
                    :key="a.appointment_id"
                    class="flex items-center justify-between gap-4 rounded-lg border border-border bg-background/70 p-5"
                >
                    <div>
                        <div class="text-base font-semibold text-foreground">
                            {{ a.start_time }} - {{ a.doctor_name }}
                        </div>
                        <div class="mt-1 text-sm text-muted-foreground">
                            Status: {{ statusLabel(a.status) }}
                        </div>
                    </div>

                    <button
                        v-if="a.can_cancel"
                        type="button"
                        class="rounded-md bg-secondary px-4 py-2 text-sm font-semibold text-secondary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="isCancellingAppointment(a.appointment_id)"
                        @click="cancelAppointment(a.appointment_id)"
                    >
                        {{
                            isCancellingAppointment(a.appointment_id)
                                ? 'Cancelling...'
                                : 'Cancel'
                        }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
