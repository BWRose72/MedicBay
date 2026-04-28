<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppPageLayout from '@/layouts/AppPageLayout.vue';

defineOptions({ layout: AppPageLayout });

type DoctorPublic = {
    doctor_id: number;
    user_id: number;
    name: string;
    display_name: string;
    specialisation: { specialisation_id: number; name: string };
    phone: string;
    bio: string;
    rating: {
        attitude_avg: number | null;
        professionalism_avg: number | null;
        reviews_count: number;
    } | null;
};

type AuthUser = {
    id?: number;
    roles?: string[];
    is_admin?: boolean;
};

type AppointmentSlot = {
    appointment_id: number | null;
    start: string;
    end: string;
    time: string;
    taken: boolean;
    bookable: boolean;
    patient_id: number | null;
    patient_name: string | null;
    patient_gender: string | null;
    patient_dob: string | null;
    patient_phone: string | null;
    patient_personal_identification_number: string | null;
};

type DoctorRatingItem = {
    review_id: number;
    attitude: number;
    professionalism: number;
    appointment_date: string | null;
    patient_name: string;
};

const props = defineProps<{
    doctor: DoctorPublic;
    can_edit: boolean;
    selected_date: string;
    slots: AppointmentSlot[];
    ratings: DoctorRatingItem[];
}>();

const page = usePage();

const canEditDoctor = computed(() => {
    const user = page.props.auth?.user as AuthUser | null | undefined;
    const roles = Array.isArray(user?.roles) ? user.roles : [];

    if (!props.can_edit || !user) {
        return false;
    }

    return (
        user.is_admin === true ||
        roles.includes('admin') ||
        (roles.includes('doctor') &&
            Number(user.id) === Number(props.doctor.user_id))
    );
});

const authUser = computed(
    () => page.props.auth?.user as AuthUser | null | undefined,
);
const authRoles = computed(() =>
    Array.isArray(authUser.value?.roles) ? authUser.value.roles : [],
);
const isAdmin = computed(
    () =>
        authUser.value?.is_admin === true || authRoles.value.includes('admin'),
);
const isDoctor = computed(() => authRoles.value.includes('doctor'));
const isPatient = computed(() => authRoles.value.includes('patient'));
const canSeePatientNames = computed(() => isAdmin.value || isDoctor.value);
const expandedSlotKey = ref<string | null>(null);
const slotError = computed(() => {
    const errors = page.props.errors as Record<string, string> | undefined;

    return errors?.slot ?? null;
});

function onDateChange(event: Event) {
    const date = (event.target as HTMLInputElement).value;

    router.get(
        `/doctors/${props.doctor.doctor_id}`,
        { date },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}

function bookSlot(slot: AppointmentSlot) {
    if (!isPatient.value || !slot.bookable || slot.taken) {
        return;
    }

    router.post(
        `/doctors/${props.doctor.doctor_id}/appointments`,
        { start: slot.start },
        {
            preserveScroll: true,
        },
    );
}

function slotStatus(slot: AppointmentSlot): string {
    if (slot.taken) {
        return 'Taken';
    }

    if (!slot.bookable) {
        return 'Closed';
    }

    return 'Open';
}

function slotClasses(slot: AppointmentSlot): string {
    const base =
        'w-full rounded-md border px-4 py-3 text-left transition flex items-center justify-between gap-3';

    if (slot.taken) {
        return `${base} border-border bg-muted/70 text-muted-foreground`;
    }

    if (!slot.bookable) {
        return `${base} border-border bg-background/40 text-muted-foreground`;
    }

    if (isPatient.value) {
        return `${base} border-primary/40 bg-background/80 text-foreground hover:bg-primary/20`;
    }

    return `${base} border-primary/30 bg-background/70 text-foreground`;
}

function doctorImageUrl(doctorId: number): string {
    return `/storage/doctors/${doctorId}.jpg`;
}
function fallbackDoctorImage(): string {
    return `/images/default-doctor.png`;
}

function togglePatientSummary(slot: AppointmentSlot): void {
    if (!canSeePatientNames.value || !slot.taken) {
        return;
    }

    expandedSlotKey.value =
        expandedSlotKey.value === slot.start ? null : slot.start;
}

function isPatientSummaryOpen(slot: AppointmentSlot): boolean {
    return expandedSlotKey.value === slot.start;
}

function isSlotInteractive(slot: AppointmentSlot): boolean {
    return (
        (isPatient.value && slot.bookable && !slot.taken) ||
        (slot.taken && canSeePatientNames.value)
    );
}

function handleSlotClick(slot: AppointmentSlot): void {
    if (slot.taken && canSeePatientNames.value) {
        togglePatientSummary(slot);
        return;
    }

    bookSlot(slot);
}

function patientAge(slot: AppointmentSlot): string {
    if (!slot.patient_dob) {
        return '-';
    }

    const dob = new Date(slot.patient_dob);

    if (Number.isNaN(dob.getTime())) {
        return '-';
    }

    const now = new Date();
    let age = now.getFullYear() - dob.getFullYear();
    const monthDiff = now.getMonth() - dob.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < dob.getDate())) {
        age--;
    }

    return age >= 0 ? String(age) : '-';
}
</script>

<template>
    <Head :title="props.doctor.display_name" />

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
                            {{ props.doctor.display_name }}
                        </h1>
                        <p class="mt-2 text-base text-muted-foreground">
                            {{ props.doctor.specialisation?.name || '—' }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link
                            v-if="canEditDoctor"
                            :href="`/doctors/${props.doctor.doctor_id}/edit`"
                            class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90"
                        >
                            Edit
                        </Link>

                        <Link href="/doctors" class="nav-link"
                            >Back to doctors</Link
                        >
                    </div>
                </div>

                <div
                    class="mt-10 grid items-start gap-8 lg:grid-cols-[1fr_480px]"
                >
                    <!-- LEFT COLUMN -->
                    <div class="space-y-6">
                        <!-- Larger photo -->
                        <img
                            :src="doctorImageUrl(props.doctor.doctor_id)"
                            :alt="`Doctor ${props.doctor.name}`"
                            class="mx-auto h-60 w-60 rounded-lg object-cover sm:h-72 sm:w-72"
                            loading="lazy"
                            @error="
                                (ev) =>
                                    ((ev.target as HTMLImageElement).src =
                                        fallbackDoctorImage())
                            "
                        />

                        <!-- Larger white information box -->
                        <div
                            class="rounded-lg border border-border bg-card p-8"
                        >
                            <div class="mt-6 space-y-6 text-base">
                                <div>
                                    <div
                                        class="text-lg font-semibold text-foreground"
                                    >
                                        Average ratings
                                    </div>
                                    <div
                                        class="mt-2 text-muted-foreground"
                                        v-if="props.doctor.rating"
                                    >
                                        Professionalism:
                                        {{
                                            props.doctor.rating
                                                .professionalism_avg !== null
                                                ? props.doctor.rating.professionalism_avg.toFixed(
                                                      1,
                                                  )
                                                : '-'
                                        }},
                                        <br />
                                        Attitude:
                                        {{
                                            props.doctor.rating.attitude_avg !==
                                            null
                                                ? props.doctor.rating.attitude_avg.toFixed(
                                                      1,
                                                  )
                                                : '-'
                                        }}
                                        <span class="ml-2 text-sm"
                                            >({{
                                                props.doctor.rating
                                                    .reviews_count
                                            }}
                                            reviews)</span
                                        >
                                    </div>
                                    <div
                                        class="mt-2 text-muted-foreground"
                                        v-else
                                    >
                                        Not enough ratings.
                                    </div>
                                </div>
                                <div>
                                    <div
                                        class="text-lg font-semibold text-foreground"
                                    >
                                        Phone
                                    </div>
                                    <div class="mt-2 text-muted-foreground">
                                        <span v-if="props.doctor.phone">{{
                                            props.doctor.phone
                                        }}</span>
                                        <span v-else>—</span>
                                    </div>
                                </div>

                                <div>
                                    <div
                                        class="text-lg font-semibold text-foreground"
                                    >
                                        Bio
                                    </div>
                                    <div
                                        class="mt-3 leading-7 text-muted-foreground"
                                    >
                                        <span v-if="props.doctor.bio">{{
                                            props.doctor.bio
                                        }}</span>
                                        <span v-else>No bio provided.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN -->
                    <div
                        class="flex min-h-[520px] flex-col rounded-lg border border-border bg-card/80 p-8 backdrop-blur-sm"
                    >
                        <div
                            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <div
                                    class="text-2xl font-semibold text-foreground"
                                >
                                    Appointment slots
                                </div>
                                <div class="mt-2 text-sm text-foreground/80">
                                    Slots close two hours before their start
                                    time.
                                </div>
                            </div>

                            <label
                                class="text-sm font-semibold text-foreground"
                            >
                                Date
                                <input
                                    type="date"
                                    class="mt-2 block rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
                                    :value="props.selected_date"
                                    @change="onDateChange"
                                />
                            </label>
                        </div>

                        <div
                            v-if="slotError"
                            class="mt-4 rounded-md border border-destructive/40 bg-destructive/10 px-4 py-3 text-sm text-destructive"
                        >
                            {{ slotError }}
                        </div>

                        <div class="mt-8 flex-1">
                            <template v-if="props.slots.length">
                                <div
                                    class="max-h-[456px] space-y-3 overflow-y-auto pr-2"
                                >
                                    <div
                                        v-for="slot in props.slots"
                                        :key="slot.start"
                                        :class="slotClasses(slot)"
                                        :role="
                                            isSlotInteractive(slot)
                                                ? 'button'
                                                : undefined
                                        "
                                        :tabindex="
                                            isSlotInteractive(slot)
                                                ? 0
                                                : undefined
                                        "
                                        :aria-disabled="
                                            !isSlotInteractive(slot)
                                        "
                                        @click="handleSlotClick(slot)"
                                        @keydown.enter.prevent="
                                            handleSlotClick(slot)
                                        "
                                        @keydown.space.prevent="
                                            handleSlotClick(slot)
                                        "
                                    >
                                        <div
                                            class="flex items-center justify-between gap-3"
                                        >
                                            <span class="font-semibold">
                                                {{ slot.time }}
                                            </span>

                                            <span
                                                class="min-w-0 text-right text-sm"
                                            >
                                                <template
                                                    v-if="
                                                        slot.taken &&
                                                        canSeePatientNames
                                                    "
                                                >
                                                    <span
                                                        v-if="
                                                            !isPatientSummaryOpen(
                                                                slot,
                                                            )
                                                        "
                                                        class="font-semibold"
                                                    >
                                                        {{
                                                            slot.patient_name ??
                                                            'Unknown patient'
                                                        }}
                                                    </span>
                                                </template>

                                                <template v-else>
                                                    {{ slotStatus(slot) }}
                                                </template>
                                            </span>
                                        </div>

                                        <div
                                            v-if="isPatientSummaryOpen(slot)"
                                            class="mt-3 border-t border-border/70 pt-3 text-sm text-muted-foreground"
                                        >
                                            <div
                                                class="grid grid-cols-1 gap-2 sm:grid-cols-2"
                                            >
                                                <div>
                                                    <span
                                                        class="font-semibold text-foreground"
                                                        >Name:</span
                                                    >
                                                    {{
                                                        slot.patient_name ??
                                                        'Unknown patient'
                                                    }}
                                                </div>
                                                <div>
                                                    <span
                                                        class="font-semibold text-foreground"
                                                        >Gender:</span
                                                    >
                                                    {{
                                                        slot.patient_gender ??
                                                        '-'
                                                    }}
                                                </div>
                                                <div>
                                                    <span
                                                        class="font-semibold text-foreground"
                                                        >Age:</span
                                                    >
                                                    {{ patientAge(slot) }}
                                                </div>
                                                <div>
                                                    <span
                                                        class="font-semibold text-foreground"
                                                        >Phone:</span
                                                    >
                                                    {{
                                                        slot.patient_phone ??
                                                        '-'
                                                    }}
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <span
                                                        class="font-semibold text-foreground"
                                                        >Personal identification
                                                        number:</span
                                                    >
                                                    {{
                                                        slot.patient_personal_identification_number ??
                                                        '-'
                                                    }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div
                                v-else
                                class="flex min-h-[260px] items-center justify-center rounded-lg bg-background/40 p-6 text-center text-base text-muted-foreground"
                            >
                                No appointment slots are available for this
                                date.
                            </div>
                        </div>
                    </div>
                </div>

                <details
                    v-if="isAdmin"
                    class="mt-10 rounded-lg border border-border bg-card/70 backdrop-blur-sm"
                >
                    <summary
                        class="cursor-pointer px-6 py-4 text-lg font-semibold text-foreground select-none"
                    >
                        All ratings
                        <span class="ml-2 text-sm text-muted-foreground">
                            ({{ props.ratings.length }})
                        </span>
                    </summary>

                    <div class="px-6 pb-6">
                        <div
                            v-if="props.ratings.length === 0"
                            class="text-muted-foreground"
                        >
                            No ratings yet.
                        </div>

                        <div
                            v-else
                            class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
                        >
                            <div
                                v-for="rating in props.ratings"
                                :key="rating.review_id"
                                class="rounded-lg border border-border bg-background/70 p-5"
                            >
                                <div class="text-sm text-muted-foreground">
                                    {{
                                        rating.appointment_date ??
                                        'Unknown date'
                                    }}
                                </div>
                                <div
                                    class="mt-2 text-base font-semibold text-foreground"
                                >
                                    {{ rating.patient_name }}
                                </div>
                                <div class="mt-3 text-sm text-muted-foreground">
                                    Professionalism:
                                    {{ rating.professionalism }}
                                    <br />
                                    Attitude: {{ rating.attitude }}
                                </div>
                            </div>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </div>
</template>
