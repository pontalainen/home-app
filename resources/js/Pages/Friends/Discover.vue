<script setup>
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { toRefs, ref, nextTick } from 'vue';
    import { Head } from '@inertiajs/vue3';
    import axios from 'axios';

    const props = defineProps({
        user: {
            type: Object,
            required: true,
        },
    });

    const { user } = toRefs(props);

    const searchInput = ref('');
    const searchLoading = ref(false);

    const users = ref([
        {
            id: 2,
            name: 'Lightning McQueen',
            friends: [1, 3],
            created_at: '2022-01-01 00:00:00'
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
    const userLoading = ref(users.value.reduce((acc, user) => ({ ...acc, [user.id]: false }), {}));

    const toggleUser = (friend, type) => {
        userLoading.value[friend.id] = true;
        toggleFriendship(friend, type);
    }

    const toggleFriendship = async (friend, type) => {
        const resp = await axios.post(
            route('friends::toggleFriendship', {user: friend}),
            { type }
        );

        await nextTick();
        userLoading.value[friend.id] = false;
    }

    const formatDate = (dateString) => {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }

    const userOptions = ['View profile', 'Block user']
</script>
<template>
    <Head title="Discover friends" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 discovery-container">
                <div class="search-container flex">
                    <input placeholder="Search for user..." v-model="searchInput" :disabled="searchLoading" @keyup.enter="sendMessage" class="bg-white rounded-lg w-full mb-4" />
                    <v-progress-circular v-if="searchLoading" indeterminate color="white ml-2"></v-progress-circular>
                </div>

                <div class="bg-gray-800 shadow-sm sm:rounded-lg flex flex-col">
                    <div class="p-6 rounded-lg">
                        <div v-for="(user, index) in users" :key="index" class="flex bg-white shadow-sm sm:rounded-lg p-3 my-8 justify-between">
                            <p>
                                {{ user.name }} <br>
                                <span class="text-xs">
                                    <v-icon size="small" icon="mdi-calendar-clock" /> Member since: {{ formatDate(user.created_at) }}
                                </span>
                            </p>
                            <div class="user-buttons flex items-center">
                                <v-btn density="compact" variant="text" size="regular" class="user-button" :loading="userLoading[user.id]" @click="toggleUser(user, 'add')">
                                    <template v-slot:default>
                                        <v-icon icon="mdi-plus" />
                                    </template>
                                    <template v-slot:loading>
                                        <v-progress-linear indeterminate></v-progress-linear>
                                    </template>
                                </v-btn>
                                <v-menu location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            density="compact"
                                            variant="text"
                                            size="regular"
                                            class="user-button"
                                            v-bind="props"
                                        >
                                            <v-icon icon="mdi-dots-horizontal"></v-icon>
                                        </v-btn>
                                    </template>

                                    <v-list width="150">
                                        <v-list-item
                                            v-for="(option, index) in userOptions"
                                            :key="index"
                                        >
                                            <v-list-item-title @click="console.log('tja')">
                                                <v-btn variant="text">
                                                    {{ option }}
                                                </v-btn>
                                            </v-list-item-title>
                                        </v-list-item>
                                    </v-list>
                                </v-menu>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<style>
    .discovery-container {
        min-width: 30rem;
        max-width: 50%;
        width: 50vw;
        margin: 2em auto;
    }
    .user-button {
        padding: 0 !important;
        min-width: 1rem !important;
        margin: 0.5rem;
    }
</style>
