<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppPageLayout from '@/layouts/AppPageLayout.vue';

defineOptions({ layout: AppPageLayout });

type DoctorEditPayload = {
    doctor_id: number;
    display_name: string;
    name: string;
    phone: string;
    bio: string;
    specialisation: { specialisation_id: number; name: string };
};

const props = defineProps<{ doctor: DoctorEditPayload }>();

const form = useForm({
    name: props.doctor.name,
    phone: props.doctor.phone,
    bio: props.doctor.bio ?? '',
});

function submit() {
    form.patch(`/doctors/${props.doctor.doctor_id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Edit Doctor Profile" />

    <div class="content-wrap">
        <div class="content-bg"></div>
        <div class="content-overlay"></div>

        <div class="content-foreground">
            <div class="container-main section-spacing">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-foreground">
                            Edit profile
                        </h1>
                        <p class="mt-2 text-base text-muted-foreground">
                            {{ props.doctor.display_name }} · {{ props.doctor.specialisation?.name || '—' }}
                        </p>
                    </div>

                    <Link :href="`/doctors/${props.doctor.doctor_id}`" class="nav-link">
                        Back
                    </Link>
                </div>

                <form class="mt-10 max-w-2xl space-y-6" @submit.prevent="submit">
                    <div class="rounded-2xl bg-card/70 backdrop-blur-sm border border-border p-6 space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-foreground">Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                            />
                            <div v-if="form.errors.name" class="mt-2 text-sm text-destructive">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-foreground">Phone</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                            />
                            <div v-if="form.errors.phone" class="mt-2 text-sm text-destructive">
                                {{ form.errors.phone }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-foreground">Bio</label>
                            <textarea
                                v-model="form.bio"
                                rows="7"
                                class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                            ></textarea>
                            <div v-if="form.errors.bio" class="mt-2 text-sm text-destructive">
                                {{ form.errors.bio }}
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                type="submit"
                                class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:opacity-60"
                                :disabled="form.processing"
                            >
                                Save
                            </button>

                            <div v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">
                                Saved.
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>