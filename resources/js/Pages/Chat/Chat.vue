<script setup>
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { toRefs } from 'vue';
    import { Head } from '@inertiajs/vue3';

    const props = defineProps({
        user: {
            type: Object,
            required: true,
        },
        chat: {
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
                <div class="chat-container bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg flex">
                    <div class="chat-window p-6 bg-pink-600 rounded-lg">
                        <div class="content-container bg-blue-100 text-black">
                            <p v-for="(message, index) in chat.messages" :key="index">
                                {{ message.content }} <br><br>
                            </p>
                        </div>

                        <!-- <v-btn></v-btn> -->

                        <div class="input-container">
                            <input placeholder="Chat..." class="chat-input bg-white rounded-lg" />
                        </div>
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
    .chat-window {
        min-width: 30rem;
        max-width: 50%;
        width: 50vw;
        margin: 2em auto;
    }
    .content-container {
        height: 90%;
        margin: 1rem 0;
        overflow-y: scroll;
    }
    .chat-input {
        width: 100%;
    }
</style>
