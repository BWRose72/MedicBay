<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppPageLayout from '@/layouts/AppPageLayout.vue';

defineOptions({ layout: AppPageLayout });

type DoctorPublic = {
    doctor_id: number;
    name: string;
    display_name: string;
    specialisation: { specialisation_id: number; name: string };
    phone: string;
    bio: string;
};

const props = defineProps<{
    doctor: DoctorPublic;
    can_edit: boolean;
    slots: any[];
}>();

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
                        <Link v-if="props.can_edit" :href="`/doctors/${props.doctor.doctor_id}/edit`"
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
                            class="h-60 w-60 sm:h-72 sm:w-72 rounded-2xl object-cover" loading="lazy"
                            @error="(ev) => ((ev.target as HTMLImageElement).src = fallbackDoctorImage())" />

                        <!-- Larger white information box -->
                        <div class="rounded-2xl bg-card p-8">
                            <div class="text-2xl font-semibold text-foreground">
                                Public information
                            </div>

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
                        <div class="text-2xl font-semibold text-foreground">
                            Appointment slots
                        </div>

                        <div class="mt-4 text-base text-foreground/80 leading-7">
                            Booking functionality will be implemented later.
                        </div>

                        <!-- Extended placeholder area -->
                        <div class="mt-8 rounded-xl bg-background/40 p-6 flex-1 flex items-center justify-center">
                            <div class="text-muted-foreground text-base text-center">
                                {{ Array.isArray(props.slots)
                                    ? `${props.slots.length} schedule entries loaded (not displayed)`
                                    : 'Schedule loaded (not displayed)' }}
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</template>