<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <div class="flex-1">
            <slot />
        </div>

        <footer class="border-t border-border bg-background">
            <div class="mx-auto max-w-6xl px-4 py-10 text-sm text-muted-foreground sm:px-6">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="font-semibold text-foreground">MedicBay</p>
                        <p class="mt-2">© {{ new Date().getFullYear() }} MedicBay. All rights reserved.</p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <p class="font-semibold text-foreground"></p>
                        <div class="flex flex-col gap-2 sm:flex-row sm:gap-4">
                            <Link href="/about" class="hover:text-foreground">About</Link>
                            <Link href="/doctors" class="hover:text-foreground">Doctors</Link>
                            <Link href="/terms" class="hover:text-foreground">Terms &amp; Conditions</Link>
                            <Link href="/privacy" class="hover:text-foreground">Privacy Policy</Link>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <p class="font-semibold text-foreground">Contact Us</p>
                        <p>123 Wellness Avenue, Razgrad 1000, Bulgaria</p>
                        <p>Phone: +359 2 123 4567</p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>