<script setup>
import { ref, defineEmits, onMounted, toRefs, watch } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import axios from 'axios';

const showingNavigationDropdown = ref(false);
const drawer = ref(false);
const chats = ref([]);

const props = defineProps({
    currentChat: {
        type: Object,
        required: false,
        default: () => ({}),
    },
});

const { currentChat } = toRefs(props);

const emit = defineEmits(['switch-chat']);
const switchChat = (newChat) => {
    drawer.value = false;
    emit('switch-chat', newChat);
};

const getChats = async () => {
    const resp = await axios.get(route('chat::getChats'));
    chats.value = resp.data;
};

onMounted(() => {
    getChats();
});

watch(drawer, (newVal) => {
    if (newVal === true) {
        getChats();
    }
});
</script>

<template>
    <!-- eslint-disable max-len -->
    <v-app>
        <div>
            <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto">
                    <div class="flex justify-between h-16">
                        <!-- Vuetify App Bar -->
                        <v-app-bar class="app-bar flex">
                            <div class="flex">
                                <v-app-bar-nav-icon
                                    variant="text"
                                    class="hamburger"
                                    @click.stop="drawer = !drawer"
                                ></v-app-bar-nav-icon>

                                <!-- Navigation Links -->
                                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                    <NavLink :href="route('chat::index')" :active="route().current('chat::index')">
                                        Chat
                                    </NavLink>
                                    <NavLink
                                        :href="route('friends::discover')"
                                        :active="route().current('friends::discover')"
                                    >
                                        Friends
                                    </NavLink>
                                </div>
                            </div>

                            <div class="me-4 hidden sm:flex sm:items-center sm:ms-6">
                                <!-- Settings Dropdown -->
                                <div class="ms-3 relative">
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                            <span class="inline-flex rounded-md">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150"
                                                >
                                                    {{ $page.props.auth.user.name }}

                                                    <svg
                                                        class="ms-2 -me-0.5 h-4 w-4"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor"
                                                    >
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>

                                        <template #content>
                                            <DropdownLink :href="route('profile::edit')"> Profile </DropdownLink>
                                            <DropdownLink :href="route('logout')" method="post" as="button">
                                                Log Out
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>

                            <!-- Hamburger -->
                            <div class="flex items-center sm:hidden">
                                <button type="button" @click="showingNavigationDropdown = !showingNavigationDropdown">
                                    <v-app-bar-nav-icon variant="text" class="hamburger"></v-app-bar-nav-icon>
                                </button>
                            </div>
                        </v-app-bar>
                    </div>
                </div>

                <!-- Vuetify Navigation Drawer -->
                <v-navigation-drawer v-model="drawer" class="nav-drawer" location="left" temporary>
                    <v-list>
                        <v-list-item
                            v-for="(chat, i) in chats"
                            :key="i"
                            :items="chats"
                            :title="chat.users.length > 2 ? chat.name : chat.otherUser.name"
                            link
                            class="py-2 my-2 min-h-16"
                            :class="{ 'active-chat': chat.id === currentChat.id }"
                            @click="switchChat(chat.id)"
                        >
                            <p class="text-xs overflow-hidden text-ellipsis mt-2 quick-text">
                                <span class="font-bold"> {{ chat.latest_message.user.name }}: </span>
                                <span>{{ chat.latest_message.content }}</span>
                            </p>
                        </v-list-item>
                    </v-list>
                </v-navigation-drawer>

                <!-- Responsive Navigation Menu -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }">
                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                        <div class="px-4">
                            <div class="font-medium text-base text-gray-800 dark:text-gray-200">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="font-medium text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                        </div>

                        <v-divider :thickness="2" class="my-2 mt-4"></v-divider>

                        <ResponsiveNavLink :href="route('chat::index')" :active="route().current('chat::index')">
                            Chat
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('friends::discover')"
                            :active="route().current('friends::discover')"
                        >
                            Friends
                        </ResponsiveNavLink>

                        <v-divider :thickness="2" class="my-2"></v-divider>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink
                                :href="route('profile::edit')"
                                :active="route().current('profile::edit')"
                            >
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')"> Log Out </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
                <!-- </nav> -->

                <!-- Page Heading -->
                <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <!-- Page Content -->
                <main>
                    <slot />
                </main>
            </div>
        </div>
    </v-app>
</template>
<style>
.app-bar {
    padding: 0 !important;
    --tw-bg-opacity: 1 !important;
    background-color: rgb(31 41 55 / var(--tw-bg-opacity)) !important;
    overflow: visible;
}

.app-bar > * {
    justify-content: space-between !important;
}

.nav-drawer {
    position: absolute !important;
    width: 300px !important;
}

.hamburger {
    --tw-bg-opacity: 1 !important;
    color: rgb(223 224 226 / var(--tw-bg-opacity));
}
.active-chat {
    background-color: rgb(66, 126, 255);
}
.quick-text {
    display: -webkit-box;
    -webkit-line-clamp: 2; /* number of lines to show */
    line-clamp: 2;
    -webkit-box-orient: vertical;
}
.v-list-item__content {
    min-height: 64px;
}
</style>
