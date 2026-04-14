<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
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
};

const props = defineProps<{
    doctor: DoctorPublic;
    can_edit: boolean;
    selected_date: string;
    slots: AppointmentSlot[];
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
        (roles.includes('doctor') && Number(user.id) === Number(props.doctor.user_id))
    );
});

const authUser = computed(() => page.props.auth?.user as AuthUser | null | undefined);
const authRoles = computed(() => (Array.isArray(authUser.value?.roles) ? authUser.value.roles : []));
const isAdmin = computed(() => authUser.value?.is_admin === true || authRoles.value.includes('admin'));
const isDoctor = computed(() => authRoles.value.includes('doctor'));
const isPatient = computed(() => authRoles.value.includes('patient'));
const canSeePatientNames = computed(() => isAdmin.value || isDoctor.value);
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
    const base = 'w-full rounded-md border px-4 py-3 text-left transition flex items-center justify-between gap-3';

    if (slot.taken) {
        return `${base} border-border bg-muted/70 text-muted-foreground cursor-not-allowed`;
    }

    if (!slot.bookable) {
        return `${base} border-border bg-background/40 text-muted-foreground cursor-not-allowed`;
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
    return `/storage/doctors/0.jpg`;
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
                        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-foreground">
                            {{ props.doctor.display_name }}
                        </h1>
                        <p class="mt-2 text-base text-muted-foreground">
                            {{ props.doctor.specialisation?.name || '—' }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link v-if="canEditDoctor" :href="`/doctors/${props.doctor.doctor_id}/edit`"
                            class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
                            Edit
                        </Link>

                        <Link href="/doctors" class="nav-link">Back to doctors</Link>
                    </div>
                </div>

                <div class="mt-10 grid gap-8 lg:grid-cols-[1fr_480px] items-start">

                    <!-- LEFT COLUMN -->
                    <div class="space-y-6">

                        <!-- Larger photo -->
                        <img :src="doctorImageUrl(props.doctor.doctor_id)" :alt="`Doctor ${props.doctor.name}`"
                            class="mx-auto h-60 w-60 sm:h-72 sm:w-72 rounded-2xl object-cover" loading="lazy"
                            @error="(ev) => ((ev.target as HTMLImageElement).src = fallbackDoctorImage())" />

                        <!-- Larger white information box -->
                        <div class="rounded-2xl bg-card p-8">
                            <div class="mt-6 space-y-6 text-base">
                                <div>
                                    <div class="font-semibold text-foreground text-lg">Phone</div>
                                    <div class="mt-2 text-muted-foreground">
                                        <span v-if="props.doctor.phone">{{ props.doctor.phone }}</span>
                                        <span v-else>—</span>
                                    </div>
                                </div>

                                <div>
                                    <div class="font-semibold text-foreground text-lg">Bio</div>
                                    <div class="mt-3 text-muted-foreground leading-7">
                                        <span v-if="props.doctor.bio">{{ props.doctor.bio }}</span>
                                        <span v-else>No bio provided.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN -->
                    <div class="rounded-2xl bg-primary/25 backdrop-blur-sm p-8 min-h-[520px] flex flex-col">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-2xl font-semibold text-foreground">
                                    Appointment slots
                                </div>
                                <div class="mt-2 text-sm text-foreground/80">
                                    Slots close two hours before their start time.
                                </div>
                            </div>

                            <label class="text-sm font-semibold text-foreground">
                                Date
                                <input
                                    type="date"
                                    class="mt-2 block rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
                                    :value="props.selected_date"
                                    @change="onDateChange"
                                />
                            </label>
                        </div>

                        <div v-if="slotError" class="mt-4 rounded-md border border-destructive/40 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                            {{ slotError }}
                        </div>

                        <div class="mt-8 flex-1">
                            <template v-if="props.slots.length">
                                <div class="max-h-[456px] space-y-3 overflow-y-auto pr-2">
                                    <div
                                        v-for="slot in props.slots"
                                        :key="slot.start"
                                        :class="slotClasses(slot)"
                                        :role="isPatient && slot.bookable && !slot.taken ? 'button' : undefined"
                                        :tabindex="isPatient && slot.bookable && !slot.taken ? 0 : undefined"
                                        :aria-disabled="!isPatient || !slot.bookable || slot.taken"
                                        @click="bookSlot(slot)"
                                        @keydown.enter.prevent="bookSlot(slot)"
                                        @keydown.space.prevent="bookSlot(slot)"
                                    >
                                        <span class="font-semibold">
                                            {{ slot.time }}
                                        </span>

                                        <span class="min-w-0 text-right text-sm">
                                            <template v-if="slot.taken && canSeePatientNames">
                                                <span class="mr-2 text-muted-foreground">Patient</span>
                                                <a href="#" class="font-semibold underline underline-offset-4" @click.prevent>
                                                    {{ slot.patient_name ?? 'Unknown patient' }}
                                                </a>
                                            </template>

                                            <template v-else>
                                                {{ slotStatus(slot) }}
                                            </template>
                                        </span>
                                    </div>
                                </div>
                            </template>

                            <div
                                v-else
                                class="flex min-h-[260px] items-center justify-center rounded-xl bg-background/40 p-6 text-center text-base text-muted-foreground"
                            >
                                No appointment slots are available for this date.
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</template>
