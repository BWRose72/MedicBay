<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import AppPageLayout from '@/layouts/AppPageLayout.vue';

defineOptions({ layout: AppPageLayout });

type SpecialisationItem = { specialisation_id: number; name: string };

type UserRow = {
    user_id: number;
    name: string;
    email: string;
    type: 'patient' | 'doctor' | 'unknown';
    doctor_id: number | null;
};

const props = defineProps<{
    q: string;
    users: UserRow[];
    specialisations: SpecialisationItem[];
}>();

const searchForm = useForm({ q: props.q ?? '' });

function submitSearch() {
    router.get('/admin/users', { q: searchForm.q || undefined }, { preserveState: true, preserveScroll: true, replace: true });
}

function clearSearch() {
    searchForm.q = '';
    submitSearch();
}

// “Make Doctor” requires required doctor fields.
const makeDoctorForm = useForm({
    user_id: 0,
    name: '',
    phone: '',
    bio: '',
    specialisation_id: 0,
});
const showMakeDoctor = useForm<{ open: boolean }>({ open: false });

function openMakeDoctor(u: UserRow) {
    makeDoctorForm.user_id = u.user_id;
    makeDoctorForm.name = u.name;
    makeDoctorForm.phone = '';
    makeDoctorForm.bio = '';
    makeDoctorForm.specialisation_id = props.specialisations[0]?.specialisation_id ?? 0;
    showMakeDoctor.open = true;
}

function closeMakeDoctor() {
    showMakeDoctor.open = false;
    makeDoctorForm.reset();
    makeDoctorForm.clearErrors();
}

function confirmMakeDoctor() {
    makeDoctorForm.patch(`/admin/users/${makeDoctorForm.user_id}/make-doctor`, {
        preserveScroll: true,
        onSuccess: () => closeMakeDoctor(),
    });
}

function fireUser(userId: number) {
    router.patch(`/admin/users/${userId}/fire`, {}, { preserveScroll: true });
}

function deleteUser(userId: number) {
    router.delete(`/admin/users/${userId}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="All Users" />

    <div class="content-wrap">
        <div class="content-bg"></div>
        <div class="content-overlay"></div>

        <div class="content-foreground">
            <div class="container-main section-spacing">
                <div class="flex items-center justify-between gap-4">
                    <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-foreground">All Users</h1>
                    <a href="/" class="nav-link">Back</a>
                </div>

                <!-- Search -->
                <form class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" @submit.prevent="submitSearch">
                    <label class="text-sm font-semibold text-foreground" for="nameSearch">Search by name</label>

                    <div class="flex w-full sm:w-auto items-center gap-2">
                        <input
                            id="nameSearch"
                            v-model="searchForm.q"
                            type="text"
                            class="w-full sm:w-80 rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
                            placeholder="Type a name..."
                        />
                        <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
                            Search
                        </button>
                        <button type="button" class="rounded-md border border-border bg-background/70 px-4 py-2 text-sm font-semibold text-foreground hover:bg-muted" @click="clearSearch">
                            Clear
                        </button>
                    </div>
                </form>

                <!-- List -->
                <div class="mt-10">
                    <div class="doctors-scroll space-y-4 pr-2">
                        <template v-if="props.users?.length">
                            <div
                                v-for="u in props.users"
                                :key="u.user_id"
                                class="block rounded-2xl bg-primary/40 text-foreground backdrop-blur-sm p-5 sm:p-6 border border-border"
                            >
                                <div class="flex items-center justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="text-xl font-semibold tracking-tight truncate">
                                            {{ u.name }}
                                        </div>
                                        <div class="mt-1 text-sm text-foreground/80 truncate">
                                            {{ u.email }} · {{ u.type }}
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <button
                                            v-if="u.type === 'patient'"
                                            type="button"
                                            class="rounded-md bg-secondary px-3 py-2 text-sm font-semibold text-secondary-foreground hover:opacity-90"
                                            @click="openMakeDoctor(u)"
                                        >
                                            Make Doctor
                                        </button>

                                        <button
                                            v-if="u.type === 'doctor'"
                                            type="button"
                                            class="rounded-md bg-secondary px-3 py-2 text-sm font-semibold text-secondary-foreground hover:opacity-90"
                                            @click="fireUser(u.user_id)"
                                        >
                                            Fire
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-md border border-border bg-background/70 px-3 py-2 text-sm font-semibold text-foreground hover:bg-muted"
                                            @click="deleteUser(u.user_id)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div class="flex items-center justify-center rounded-2xl bg-primary/20 p-5 sm:p-6 text-center text-foreground" style="height: 120px">
                                No users found.
                            </div>
                            <div v-for="n in 3" :key="n" class="rounded-2xl bg-primary/0 p-5 sm:p-6 opacity-60" style="height: 120px"></div>
                        </template>
                    </div>
                </div>

                <!-- Make Doctor modal -->
                <div v-if="showMakeDoctor.open" class="fixed inset-0 z-[60]">
                    <div class="absolute inset-0 bg-black/40"></div>

                    <div class="absolute inset-0 flex items-center justify-center p-4">
                        <div class="w-full max-w-xl rounded-2xl bg-card border border-border shadow-lg p-6">
                            <div class="flex items-center justify-between gap-4">
                                <div class="text-lg font-semibold text-foreground">Make Doctor</div>
                                <button type="button" class="nav-link" @click="closeMakeDoctor">Close</button>
                            </div>

                            <div class="mt-5 space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-foreground">Name</label>
                                    <input v-model="makeDoctorForm.name" class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground" />
                                    <div v-if="makeDoctorForm.errors.name" class="mt-2 text-sm text-destructive">
                                        {{ makeDoctorForm.errors.name }}
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-foreground">Phone</label>
                                    <input v-model="makeDoctorForm.phone" class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground" />
                                    <div v-if="makeDoctorForm.errors.phone" class="mt-2 text-sm text-destructive">
                                        {{ makeDoctorForm.errors.phone }}
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-foreground">Specialisation</label>
                                    <select
                                        v-model="makeDoctorForm.specialisation_id"
                                        class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                    >
                                        <option v-for="s in props.specialisations" :key="s.specialisation_id" :value="s.specialisation_id">
                                            {{ s.name }}
                                        </option>
                                    </select>
                                    <div v-if="makeDoctorForm.errors.specialisation_id" class="mt-2 text-sm text-destructive">
                                        {{ makeDoctorForm.errors.specialisation_id }}
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-foreground">Bio (optional)</label>
                                    <textarea
                                        v-model="makeDoctorForm.bio"
                                        rows="5"
                                        class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                    ></textarea>
                                    <div v-if="makeDoctorForm.errors.bio" class="mt-2 text-sm text-destructive">
                                        {{ makeDoctorForm.errors.bio }}
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:opacity-60"
                                        :disabled="makeDoctorForm.processing"
                                        @click="confirmMakeDoctor"
                                    >
                                        Confirm
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-md border border-border bg-background/70 px-4 py-2 text-sm font-semibold text-foreground hover:bg-muted"
                                        @click="closeMakeDoctor"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /modal -->
            </div>
        </div>
    </div>
</template>