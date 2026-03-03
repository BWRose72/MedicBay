<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppPageLayout from '@/layouts/AppPageLayout.vue';

defineOptions({
    layout: AppPageLayout,
});

type RatingSummary = {
    attitude_avg: number | null;
    professionalism_avg: number | null;
    reviews_count: number;
};

type DoctorListItem = {
    doctor_id: number;
    name: string;
    specialisation_label: string | null;
    rating: RatingSummary | null;
};

type SpecialisationItem = {
    specialisation_id: number;
    name: string;
};

const props = defineProps<{
    doctors: DoctorListItem[];
    specialisations: SpecialisationItem[];
    selectedSpecialisationId: number | null;
}>();

function doctorImageUrl(doctorId: number): string {
    return `/storage/doctors/${doctorId}.jpg`;
}

function fallbackDoctorImage(): string {
    return `/storage/doctors/0.jpg`;
}

function fmtXof10(v: number | null): string {
    if (v === null || Number.isNaN(v)) return '—/10';
    const n = Math.round(v * 10) / 10;
    return `${n}/10`;
}

function onSpecialisationChange(e: Event) {
    const value = (e.target as HTMLSelectElement).value;
    const specialisationId = value ? Number(value) : null;

    router.get(
        '/doctors',
        specialisationId ? { specialisation_id: specialisationId } : {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}
</script>

<template>

    <Head title="Doctors" />

    <div class="content-wrap">
        <div class="content-bg"></div>
        <div class="content-overlay"></div>

        <div class="content-foreground">
            <div class="container-main section-spacing">
                <div class="flex items-center justify-between gap-4">
                    <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-foreground">Doctors</h1>
                    <Link href="/" class="nav-link">Back</Link>
                </div>

                <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <label class="text-sm font-semibold text-foreground" for="specFilter">
                        Filter by specialisation
                    </label>

                    <select id="specFilter"
                        class="rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
                        :value="props.selectedSpecialisationId ?? ''" @change="onSpecialisationChange">
                        <option value="">All specialisations</option>
                        <option v-for="s in props.specialisations" :key="s.specialisation_id"
                            :value="s.specialisation_id">
                            {{ s.name }}
                        </option>
                    </select>
                </div>

                <div class="mt-10">
                    <div class="doctors-scroll space-y-4 pr-2">
                        <template v-if="props.doctors?.length">
                            <Link v-for="d in props.doctors" :key="d.doctor_id" :href="`/doctors/${d.doctor_id}`"
                                class="block rounded-2xl bg-primary/75 text-foreground backdrop-blur-sm p-5 sm:p-6 hover:bg-primary/80 transition">
                                <div class="grid gap-4 sm:gap-6 sm:grid-cols-[1fr_220px] items-center">

                                    <div class="flex items-center gap-4 sm:gap-6">
                                        <img :src="doctorImageUrl(d.doctor_id)" :alt="`Doctor ${d.name}`"
                                            class="h-24 w-24 sm:h-28 sm:w-28 rounded-2xl object-cover shrink-0"
                                            loading="lazy"
                                            @error="(ev) => ((ev.target as HTMLImageElement).src = fallbackDoctorImage())" />

                                        <div class="min-w-0">
                                            <div class="text-2xl font-semibold tracking-tight truncate">
                                                Dr. {{ d.name }}
                                            </div>
                                            <div class="mt-1 text-base text-foreground/80 truncate">
                                                {{ d.specialisation_label ?? '—' }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center sm:text-right">
                                        <template v-if="d.rating">
                                            <div class="text-sm font-semibold">Attitude</div>
                                            <div class="mt-1 text-2xl font-semibold">
                                                {{ fmtXof10(d.rating.attitude_avg) }}
                                            </div>

                                            <div class="mt-4 text-sm font-semibold">Professionalism</div>
                                            <div class="mt-1 text-2xl font-semibold">
                                                {{ fmtXof10(d.rating.professionalism_avg) }}
                                            </div>
                                        </template>

                                        <template v-else>
                                            <div class="text-sm font-semibold">Ratings</div>
                                            <div class="mt-2 text-base text-foreground/80">
                                                Not enough reviews
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </Link>
                        </template>

                        <template v-else>
                            <div class="flex items-center justify-center rounded-2xl bg-primary/40 p-5 sm:p-6 text-center text-foreground"
                                style="height: 140px">
                                No doctors found for this filter.
                            </div>

                            <div v-for="n in 3" :key="n" class="rounded-2xl bg-primary/0 p-5 sm:p-6 opacity-60"
                                style="height: 140px"></div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>