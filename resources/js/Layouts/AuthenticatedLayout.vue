<script setup>
// 1. Imports and Component Setup
import { ref, defineEmits, onMounted, toRefs, watch, computed } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import axios from 'axios';
import { getChatName, getCurrentChatUser, getStatusMessageContent } from '@/helpers';

const props = defineProps({
    currentChat: {
        type: Object,
        required: false,
        default: () => ({}),
    },
    user: {
        type: Object,
        required: false,
        default: () => ({}),
    },
    openDrawer: {
        type: Boolean,
        required: false,
        default: false,
    },
});

const { currentChat, openDrawer, user } = toRefs(props);

// 2. Reactive State
const showingNavigationDropdown = ref(false);
const drawer = ref(false);
const chats = ref([]);

// 3. Computed Properties and Watchers
const activeColor = computed(() => {
    if (currentChat.value && currentChat.value.users) {
        return currentChat.value
            ? currentChat.value.users.find((u) => u.id === user.value.id)?.pivot?.bubble_color
            : '#123456';
    }
    return 'gray';
});

watch(openDrawer, () => {
    drawer.value = true;
});

watch(drawer, (newVal) => {
    if (newVal === true) {
        getChats();
    }
});

// 4. Lifecycle Hooks
onMounted(() => {
    getChats();
});

// 5. Methods
const emit = defineEmits(['switch-chat']);

const getChats = async () => {
    const resp = await axios.get(route('chat::getChats'));
    chats.value = resp.data;
};

const switchChat = (newChat) => {
    drawer.value = false;
    emit('switch-chat', newChat);
};
</script>

<template>
    <!-- eslint-disable max-len -->
    <!-- eslint-disable vue/html-indent -->
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
                                    <NavLink
                                        :href="route('chat::index')"
                                        :active="route().current('chat::index') || route().current('chat::chat')"
                                        :active-color="activeColor"
                                    >
                                        Chat
                                    </NavLink>
                                    <NavLink
                                        :href="route('friends::myFriends')"
                                        :active="
                                            route().current('friends::myFriends') ||
                                            route().current('friends::discover')
                                        "
                                        :active-color="activeColor"
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
                                            <DropdownLink :href="route('profile::edit')" class="dropdown-link">
                                                Profile
                                            </DropdownLink>
                                            <DropdownLink
                                                :href="route('logout')"
                                                method="post"
                                                as="button"
                                                class="dropdown-link"
                                            >
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
                            :title="getChatName(chat)"
                            link
                            class="py-2 my-2"
                            :class="{ 'active-chat': currentChat && chat.id === currentChat.id }"
                            @click="switchChat(chat.id)"
                        >
                            <div v-if="chat.latest_message">
                                <p
                                    v-if="chat.latest_message.type === 'status'"
                                    class="text-xs overflow-hidden text-ellipsis mt-2 quick-text"
                                >
                                    <!-- eslint-disable-next-line vue/no-v-html -->
                                    <span v-html="getStatusMessageContent(chat.latest_message)" />
                                </p>
                                <p v-else class="text-xs overflow-hidden text-ellipsis mt-2 quick-text">
                                    <span
                                        v-if="chat.latest_message.user.id === $page.props.auth.user.id"
                                        class="font-bold"
                                    >
                                        You:
                                    </span>
                                    <span v-else class="font-bold"> {{ chat.latest_message.user.name }}: </span>
                                    <span>{{ chat.latest_message.content }}</span>
                                </p>
                            </div>
                            <div v-else>
                                <p>
                                    <span class="text-xs overflow-hidden text-ellipsis mt-2 quick-text font-italic">
                                        Say hello to {{ chat.otherUser.name }}!
                                    </span>
                                </p>
                            </div>

                            <v-divider
                                class="my-2 mt-4 border-opacity-100"
                                :thickness="2"
                                :class="{ 'default-divider': currentChat && chat.id === currentChat.id }"
                                :style="{
                                    borderColor:
                                        getCurrentChatUser(chat, user.id).pivot.bubble_color ?? 'rgb(66, 126, 255)',
                                }"
                            ></v-divider>
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
.dropdown-link {
    color: black;
}

.dropdown-link:hover {
    color: white;
}

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
    background-color: rgb(176, 192, 207);
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
.v-list-item-title {
    font-weight: bold;
}
</style>
