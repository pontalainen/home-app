<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    currentMode: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
        <div>
            <Link href="/">
                <ApplicationLogo class="w-20 h-20 fill-current text-gray-500" />
            </Link>
        </div>

        <div
            class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg"
        >
            <slot />
        </div>
        <div class="flex justify-center mt-12 login-register">
            <p v-if="currentMode === 'register'">
                <span> Already have an account? </span>
                <Link href="/login" class="underline"> Login </Link>
            </p>
            <p v-else>
                <span> Don't have an account yet? </span>
                <Link href="/register" class="underline"> Register </Link>
            </p>
        </div>
    </div>
</template>

<style>
.login-register {
    color: white;
}
</style>
