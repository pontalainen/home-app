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

const users = ref([]);
const userLoading = ref(users.value.reduce((acc, u) => ({ ...acc, [u.id]: false }), {}));
const userHover = ref(users.value.reduce((acc, u) => ({ ...acc, [u.id]: false }), {}));

const searchInput = ref('');
const searchLoading = ref(false);
const hasSearched = ref(false);

const search = () => {
    if (!searchInput.value.length) {
        return;
    }
    searchLoading.value = true;
    getUsers();
};

const getUsers = async () => {
    const resp = await axios.post(route('friends::getUsers'), { search: searchInput.value });
    users.value = resp.data;

    await nextTick();
    searchLoading.value = false;
    hasSearched.value = true;
};

const toggleUser = (u) => {
    userLoading.value[u.id] = true;
    const type = areFriends(u) ? 'remove' : 'add';
    toggleFriendship(u, type);
};

const toggleFriendship = async (friend, type) => {
    const resp = await axios.post(route('friends::toggleFriendship', { user: friend }), { type });

    await nextTick();
    userLoading.value[friend.id] = false;
    if (resp) {
        getUsers();
    }
};

const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};

const userOptions = ['View profile', 'Block user'];

const areFriends = (u) => {
    return u.friends.some((friend) => friend.id === user.value.id);
};

const openChat = async (u) => {
    try {
        const resp = await axios.post(route('chat::checkChat'), { user: u.id });
        window.location.href = route('chat::chat', { chat: resp.data });
    } catch (error) {
        console.error(error);
    }
};
</script>
<template>
    <div>
        <Head title="Discover friends" />

        <AuthenticatedLayout>
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 discovery-container">
                    <div class="search-container flex">
                        <input
                            v-model="searchInput"
                            placeholder="Search for user..."
                            class="bg-white rounded-lg w-full mb-4"
                            @keyup.enter="search"
                        />
                        <v-progress-circular
                            v-if="searchLoading"
                            indeterminate
                            color="white ml-2"
                        ></v-progress-circular>
                    </div>

                    <v-divider :thickness="4"></v-divider>

                    <div class="shadow-sm sm:rounded-lg flex flex-col">
                        <div v-if="!users.length || !hasSearched" class="m-12">
                            <p class="text-center text-gray-400">
                                <span v-if="!hasSearched">Search for a name or an email address!</span>
                                <span v-else>No users found, sorry!</span>
                            </p>
                        </div>

                        <div v-else>
                            <div
                                v-for="(u, index) in users"
                                :key="index"
                                class="flex bg-white shadow-sm sm:rounded-lg p-3 my-8 justify-between"
                            >
                                <p>
                                    {{ u.name }} <br />
                                    <span class="text-xs">
                                        <v-icon size="small" icon="mdi-calendar-clock" /> Member since:
                                        {{ formatDate(u.created_at) }}
                                    </span>
                                </p>
                                <div class="user-buttons flex items-center">
                                    <v-btn
                                        density="compact"
                                        variant="text"
                                        size="regular"
                                        class="user-button"
                                        :loading="userLoading[u.id]"
                                        @mouseover="userHover[u.id] = true"
                                        @focus="userHover[u.id] = true"
                                        @blur="userHover[u.id] = false"
                                        @click="toggleUser(u)"
                                    >
                                        <template #default>
                                            <div v-if="areFriends(u) && !userHover[u.id]">
                                                <v-icon icon="mdi-check" />
                                            </div>
                                            <div v-else-if="areFriends(u) && userHover[u.id]">
                                                <v-icon icon="mdi-close" />
                                                <v-tooltip activator="parent" location="top">Remove friend</v-tooltip>
                                            </div>
                                            <div v-else>
                                                <v-icon icon="mdi-plus" />
                                                <v-tooltip activator="parent" location="top">Add friend</v-tooltip>
                                            </div>
                                        </template>
                                        <template #loading>
                                            <v-progress-linear indeterminate></v-progress-linear>
                                        </template>
                                    </v-btn>

                                    <v-btn
                                        density="compact"
                                        variant="text"
                                        size="regular"
                                        class="user-button"
                                        @click="openChat(u)"
                                    >
                                        <v-icon icon="mdi-message-outline"></v-icon>
                                        <v-tooltip activator="parent" location="top">Open chat</v-tooltip>
                                    </v-btn>

                                    <v-menu location="top">
                                        <template #activator="{ p }">
                                            <v-btn
                                                density="compact"
                                                variant="text"
                                                size="regular"
                                                class="user-button"
                                                v-bind="p"
                                            >
                                                <v-icon icon="mdi-dots-horizontal"></v-icon>
                                            </v-btn>
                                        </template>

                                        <v-list width="150">
                                            <v-list-item v-for="(option, i) in userOptions" :key="i">
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
    </div>
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
