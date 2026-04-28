<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
</script>

<template>
    <AuthBase
        title="Create an account"
        description="Enter your details below to create your account"
    >
        <Head title="Register" />

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name"
                        >Name <span class="text-red-500">*</span></Label
                    >
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="Full name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email"
                        >Email address
                        <span class="text-red-500">*</span></Label
                    >
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="gender"
                        >Gender <span class="text-red-500">*</span></Label
                    >
                    <Input
                        id="gender"
                        type="text"
                        required
                        :tabindex="3"
                        autocomplete="sex"
                        name="gender"
                        placeholder="male / female / other"
                    />
                    <InputError :message="errors.gender" />
                </div>

                <div class="grid gap-2">
                    <Label for="personal_identification_number"
                        >Personal identification number
                        <span class="text-red-500">*</span></Label
                    >
                    <Input
                        id="personal_identification_number"
                        type="text"
                        required
                        :tabindex="4"
                        name="personal_identification_number"
                        placeholder="Personal identification number"
                    />
                    <InputError
                        :message="errors.personal_identification_number"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="date_of_birth"
                        >Date of birth
                        <span class="text-red-500">*</span></Label
                    >
                    <Input
                        id="date_of_birth"
                        type="date"
                        required
                        :tabindex="5"
                        autocomplete="bday"
                        name="date_of_birth"
                    />
                    <InputError :message="errors.date_of_birth" />
                </div>

                <div class="grid gap-2">
                    <Label for="phone">Phone (optional)</Label>
                    <Input
                        id="phone"
                        type="text"
                        :tabindex="6"
                        autocomplete="tel"
                        name="phone"
                        placeholder="+359..."
                    />
                    <InputError :message="errors.phone" />
                </div>

                <div class="grid gap-2">
                    <Label for="password"
                        >Password <span class="text-red-500">*</span></Label
                    >
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="7"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation"
                        >Confirm password
                        <span class="text-red-500">*</span></Label
                    >
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="8"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    tabindex="9"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink
                    :href="login()"
                    class="underline underline-offset-4"
                    :tabindex="10"
                    >Log in</TextLink
                >
            </div>
        </Form>
    </AuthBase>
</template>
