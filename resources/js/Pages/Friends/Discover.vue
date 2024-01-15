<script setup>
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { toRefs, ref } from 'vue';
    import { Head } from '@inertiajs/vue3';

    const props = defineProps({
        user: {
            type: Object,
            required: true,
        },
    });

    const { user } = toRefs(props);

    const searchInput = ref('');
    const loading = ref(false);

    const users = ref([
        {
            id: 2,
            name: 'Lightning McQueen',
            friends: [1, 3]
        },
        {
            id: 3,
            name: 'Mater',
            friends: [2]
        },
        {
            id: 4,
            name: 'Sally',
            friends: [3]
        },
    ]);
</script>
<template>
    <Head title="Discover friends" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="search-container flex">
                    <input placeholder="Search for users..." v-model="searchInput" :disabled="loading" @keyup.enter="sendMessage" class="bg-white rounded-lg w-full mb-4" />
                    <v-progress-circular v-if="loading" indeterminate color="white ml-2"></v-progress-circular>
                </div>

                <div class="bg-gray-800 shadow-sm sm:rounded-lg flex flex-col">
                    <div class="chat-window p-6 rounded-lg">
                        <div v-for="(user, index) in users" :key="index" class="flex bg-gray-400 shadow-sm sm:rounded-lg p-3 my-8">
                            <p>
                                {{ user.name }}
                            </p>
                            <div class="user-buttons">
                                <v-col cols="auto">
                                    <v-btn density="compact" icon="mdi-plus" />
                                </v-col>
                            </div>
                        </div>
                    </div> 
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>