<script setup>
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { Head, Link } from '@inertiajs/vue3'

    defineProps({
        members: {
            type: Object,
            required: true,
        }
    })
</script>
<template>
    <Head title="Chat" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg flex chat-container">
                    <div class="p-6 bg-pink-600 m-24 rounded-lg w-full">

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<style>
    .chat-container {
        height: 80vh;
    }
</style>