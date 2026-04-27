<template>
    <section class="rounded-lg border border-border bg-card/70 p-6 backdrop-blur-sm">
        <h2 class="text-lg font-semibold text-foreground">Administrative workspace</h2>
        <p class="mt-2 text-sm text-muted-foreground">
            Review user access, doctor profiles, and daily operational activity from the admin tools.
        </p>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <a
                href="/admin/users"
                class="rounded-md border border-border bg-background/70 p-4 text-sm font-semibold text-foreground hover:bg-muted"
            >
                Manage users
            </a>
            <a
                href="/doctors"
                class="rounded-md border border-border bg-background/70 p-4 text-sm font-semibold text-foreground hover:bg-muted"
            >
                Review doctors
            </a>
        </div>
    </section>
</template>
