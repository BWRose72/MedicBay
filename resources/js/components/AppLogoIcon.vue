<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <Link href="/" class="text-3xl font-bold text-primary tracking-tight">
    MedicBay
</Link>
</template>
