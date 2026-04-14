<!-- resources/js/pages/Dashboard.vue -->
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppPageLayout from '@/layouts/AppPageLayout.vue';

import AdminDashboard from '@/pages/AdminDashboard.vue';
import DoctorDashboard from '@/pages/DoctorDashboard.vue';
import PatientDashboard from '@/pages/PatientDashboard.vue';

defineOptions({ layout: AppPageLayout });

type Props = {
  dashboard_type: 'admin' | 'doctor' | 'patient' | 'default';
  appointments?: any;
  notice?: string;
};

const props = defineProps<Props>();
</script>

<template>

  <Head title="Dashboard" />

  <div v-if="props.notice" class="mb-6 rounded-xl border border-border bg-card/70 p-4 text-foreground">
    {{ props.notice }}
  </div>

  <AdminDashboard v-if="props.dashboard_type === 'admin'" />

  <DoctorDashboard v-else-if="props.dashboard_type === 'doctor'" :appointments="props.appointments" />

  <PatientDashboard v-else-if="props.dashboard_type === 'patient'" :appointments="props.appointments" />

  <div v-else class="content-wrap">
    <div class="content-bg"></div>
    <div class="content-overlay"></div>

    <div class="content-foreground">
      <div class="container-main section-spacing">
        <p>Dashboard not available for this account.</p>
      </div>
    </div>
  </div>
</template>