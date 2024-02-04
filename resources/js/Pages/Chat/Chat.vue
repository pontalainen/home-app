<script setup>
// 1. Imports and Component Setup
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { toRefs, ref, onMounted, nextTick, watch, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

const { Echo } = window;

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    chatProp: {
        type: Object,
        required: false,
        default: null,
    },
    lastMessageIdProp: {
        type: Number,
        required: false,
        default: 1,
    },
});
const { user, chatProp, lastMessageIdProp } = toRefs(props);

// 2. Reactive State
const chat = ref(chatProp.value);
const lastMessageId = ref(lastMessageIdProp.value);
const newMessage = ref('');
const chatScroll = ref(null);
const chatInput = ref(null);
const isAtBottom = ref(true);
const loading = ref(false);
const drawer = ref(false);
const noMoreMessages = ref(false);
const chatOptions = ref(['Change bubble color']);
const colorModalOpen = ref(false);
const selectedUser = ref(chat.value ? chat.value.users.find((u) => u.id === user.value.id) : null);
const prevUserValues = ref([]);
const nameHover = ref(false);
const editingName = ref(false);
const nicknameInputEl = ref('');

if (chat.value) {
    chat.value.messages = chat.value.messages.reverse();
}

// 3. Computed Properties and Watchers
watch(selectedUser, () => {
    revertUserChanges();
});

const updateUserUrl = computed(() => {
    return route('chat::updateUser', { chat: chat.value.id, user: selectedUser.value.id });
});

const otherUser = computed(() => {
    return chat.value ? chat.value.users.find((u) => u.id !== user.value.id) : null;
});

// 4. Lifecycle Hooks
onMounted(() => {
    if (chat.value) {
        chatScroll.value.addEventListener('scroll', () => {
            const { scrollTop, scrollHeight, clientHeight } = chatScroll.value;
            isAtBottom.value = scrollTop + clientHeight + 800 >= scrollHeight;
        });
        scrollToBottom();

        Echo.private(`chat.${chat.value.id}`).listen('MessageSent', async (e) => {
            chat.value.messages.push(e.message);
            await nextTick();
            scrollToBottom();
        });
    }
});

// 5. Methods
const sendMessage = () => {
    if (loading.value || newMessage.value === '') {
        return;
    }
    loading.value = true;
    saveMessage(newMessage.value);
    newMessage.value = '';
};

const saveMessage = async (content) => {
    try {
        await axios.post(route('chat::sendMessage', { chat: chat.value, user: user.value }), { content });
        await nextTick();
        scrollToBottom();
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const loadMessages = async ({ done }) => {
    if (noMoreMessages.value) {
        done('empty');
        return;
    }
    try {
        const previousScrollHeight = chatScroll.value.scrollHeight;
        const previousScrollTop = chatScroll.value.scrollTop;
        const newMessages = await axios.post(route('chat::loadMessages', { chat: chat.value }), {
            lastMessageId: lastMessageId.value,
        });
        if (typeof newMessages.data.messages === 'object') {
            chat.value.messages.unshift(...newMessages.data.messages);
            lastMessageId.value = newMessages.data.lastMessageId;
            await nextTick();
            const newScrollHeight = chatScroll.value.scrollHeight;
            chatScroll.value.scrollTop = previousScrollTop + (newScrollHeight - previousScrollHeight);
            done('ok');
        } else {
            noMoreMessages.value = true;
            done('empty');
        }
    } catch (error) {
        console.error(error);
        done('error');
    }
};

const getChat = async (newChat) => {
    try {
        const resp = await axios.get(route('chat::getChat', { chat: newChat }));
        chat.value = resp.data.chat;
        chat.value.messages = chat.value.messages.reverse();
        lastMessageId.value = resp.data.lastMessageId;
    } catch (error) {
        console.error(error);
    } finally {
        // eslint-disable-next-line no-restricted-globals
        history.pushState({}, '', `/chat/${newChat}`);
        await nextTick();
        scrollToBottom();
    }
};

const switchChat = (newChat) => {
    getChat(newChat);
};

const setUserPivotValues = () => {
    prevUserValues.value = chat.value.users.map((u) => ({
        id: u.id,
        color: u.pivot.bubble_color,
        nickname: u.pivot.nickname ?? u.name,
    }));
};

const revertUserChanges = () => {
    chat.value.users.map((u) => {
        const prevValues = prevUserValues.value.find((c) => c.id === u.id);
        if (prevValues) {
            u.pivot.bubble_color = prevValues.color;
            u.pivot.nickname = prevValues.nickname;
        }
        return u;
    });
};

const openColorModal = () => {
    colorModalOpen.value = true;
    setUserPivotValues();
};

const closeColorModal = () => {
    colorModalOpen.value = false;
    revertUserChanges();
};

const saveColor = async () => {
    colorModalOpen.value = false;
    try {
        await axios.put(updateUserUrl.value, {
            bubble_color: selectedUser.value.pivot.bubble_color,
        });

        // Use below when status messages are implemented
        // await nextTick();
        // scrollToBottom();
    } catch {
        revertUserChanges();
    }
};

const editName = () => {
    selectedUser.value = otherUser.value;
    setUserPivotValues();

    if (!selectedUser.value.pivot.nickname) {
        selectedUser.value.pivot.nickname = selectedUser.value.name;
    }

    editingName.value = true;
    nextTick(() => {
        nicknameInputEl.value.focus();
    });
};

const cancelNameEdit = () => {
    editingName.value = false;
    revertUserChanges();
};

const saveNickname = async () => {
    try {
        await axios.put(updateUserUrl.value, {
            nickname: selectedUser.value.pivot.nickname,
        });
        editingName.value = false;
    } catch {
        revertUserChanges();
    }
};

// 6. External or Helper Functions
const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};

const scrollToBottom = () => {
    chatScroll.value.scrollTop = chatScroll.value.scrollHeight;
};

const getBubbleColor = (senderId) => {
    const sender = chat.value.users.find((u) => u.id === senderId);
    return sender.pivot.bubble_color;
};

const getTextColor = (backgroundColor) => {
    if (!backgroundColor) {
        return '#000000';
    }

    const color = backgroundColor.charAt(0) === '#' ? backgroundColor.substring(1) : backgroundColor;
    const r = parseInt(color.substring(0, 2), 16);
    const g = parseInt(color.substring(2, 4), 16);
    const b = parseInt(color.substring(4, 6), 16);
    // Using the YIQ color space formula to determine brightness
    const yiq = (r * 299 + g * 587 + b * 114) / 1000;
    return yiq >= 128 ? '#000000' : '#FFFFFF'; // dark text for light backgrounds and vice versa
};
</script>
<template>
    <div>
        <Head title="Chat" />

        <AuthenticatedLayout :current-chat="chat" :open-drawer="drawer" @switch-chat="switchChat">
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div class="chat-container bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg flex flex-col">
                        <div class="w-full -mb-12 flex justify-end">
                            <v-menu location="bottom">
                                <!-- eslint-disable-next-line vue/no-template-shadow -->
                                <template #activator="{ props }">
                                    <v-btn class="mt-6 mr-8" variant="text" size="regular" color="white" v-bind="props">
                                        <v-icon icon="mdi-cog-outline" size="x-large" />
                                    </v-btn>
                                </template>

                                <v-list width="210">
                                    <v-list-item
                                        v-for="(option, i) in chatOptions"
                                        :key="i"
                                        :value="i"
                                        class="!h-min !min-h-0"
                                        @click="openColorModal"
                                    >
                                        <v-list-item-title class="!min-h-0">
                                            {{ option }}
                                        </v-list-item-title>
                                    </v-list-item>
                                </v-list>
                            </v-menu>
                        </div>
                        <div v-if="chat" class="chat-window p-6 rounded-lg">
                            <div
                                class="flex justify-center -mt-8 mb-4"
                                @mouseover="nameHover = true"
                                @mouseleave="nameHover = false"
                                @focus="nameHover = true"
                                @focusout="nameHover = false"
                            >
                                <div v-if="editingName" class="relative w-full">
                                    <div class="flex justify-center nickname-container">
                                        <input
                                            ref="nicknameInputEl"
                                            v-model="selectedUser.pivot.nickname"
                                            placeholder="User nickname"
                                            class="nickname-input text-blue-100 font-bold text-xl text-center p-0"
                                            variant="solo"
                                            maxlength="25"
                                            @blur="cancelNameEdit"
                                            @keyup.enter="saveNickname"
                                        />
                                    </div>
                                    <v-btn
                                        variant="text"
                                        size="regular"
                                        class="!absolute top-0 bottom-0 right-2"
                                        @click="saveNickname"
                                    >
                                        <v-icon icon="mdi-send-check-outline" class="text-blue-100"></v-icon>
                                    </v-btn>
                                </div>
                                <p v-else class="text-blue-100 font-bold text-xl relative">
                                    <span v-if="chat.users.length > 2">
                                        {{ chat.name }}
                                    </span>
                                    <span v-else>
                                        {{ otherUser.pivot.nickname ?? otherUser.name }}
                                    </span>
                                    <v-btn
                                        variant="text"
                                        size="regular"
                                        class="ml-2 !absolute top-0 bottom-0"
                                        :class="{ '!hidden': !nameHover }"
                                        @click="editName"
                                    >
                                        <v-icon icon="mdi-pencil" size="x-small" class="text-blue-100"></v-icon>
                                    </v-btn>
                                </p>
                            </div>

                            <div
                                ref="chatScroll"
                                class="content-container bg-blue-100 text-black rounded-lg chat-scroll"
                            >
                                <v-infinite-scroll v-if="chat.messages.length" side="start" @load="loadMessages">
                                    <div v-for="(message, index) in chat.messages" :key="index" class="m-4">
                                        <p
                                            class="p-4 rounded-lg message break-all flex flex-col"
                                            :class="
                                                message.user_id === user.id ? 'bg-blue-600 ml-20' : 'bg-green-600 mr-20'
                                            "
                                            :style="{
                                                backgroundColor: getBubbleColor(message.user_id),
                                                color: getTextColor(getBubbleColor(message.user_id)),
                                            }"
                                        >
                                            <span v-if="chat.users.length > 2" class="text-xs mb-1">
                                                {{ message.user ? message.user.name : '[User deleted]' }}<br />
                                            </span>
                                            <span> {{ message.content }} <br /> </span>
                                            <span class="text-xs mt-4">
                                                {{ formatDate(message.sent_at) }}
                                            </span>
                                        </p>
                                    </div>
                                    <div class="fixed-wrapper">
                                        <v-btn v-if="!isAtBottom" class="to-bottom-btn" @click="scrollToBottom">
                                            scroll to bottom
                                        </v-btn>
                                    </div>
                                    <template #empty></template>
                                </v-infinite-scroll>

                                <div v-else class="empty-message-container">
                                    <p class="empty-message">
                                        There are no messages in this chat yet. Why don't you start a conversation?
                                    </p>
                                </div>
                            </div>

                            <div class="input-container flex mt-4">
                                <input
                                    ref="chatInput"
                                    v-model="newMessage"
                                    placeholder="Chat..."
                                    class="chat-input bg-blue-100 rounded-lg"
                                    @keyup.enter="sendMessage"
                                />
                                <v-progress-circular
                                    v-if="loading"
                                    indeterminate
                                    color="white ml-2"
                                ></v-progress-circular>
                            </div>
                        </div>

                        <div v-else class="start-chat-container w-full flex flex-col">
                            <p class="text-gray-300 my-2">Start a chat with someone!</p>
                            <v-btn compact variant="text" class="my-2 see-chats" @click="drawer = !drawer">
                                See chats
                            </v-btn>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>

        <v-dialog v-model="colorModalOpen" width="500" transition="dialog-bottom-transition">
            <template #default>
                <v-card class="!bg-gray-800 flex flex-col">
                    <v-select
                        v-model="selectedUser"
                        :items="chat.users"
                        item-title="name"
                        item-value="name"
                        return-object
                        density="comfortable"
                        variant="no-border"
                        class="!bg-blue-50 mb-4"
                    ></v-select>
                    <div class="w-full flex justify-center p-2">
                        <v-color-picker
                            v-model="selectedUser.pivot.bubble_color"
                            class="!bg-blue-50 flex"
                            :modes="['hexa']"
                        ></v-color-picker>
                    </div>

                    <v-card-actions class="mt-4">
                        <v-btn color="white" text="Cancel" @click="closeColorModal"></v-btn>

                        <v-spacer></v-spacer>

                        <v-btn color="white" text="Save" @click="saveColor"></v-btn>
                    </v-card-actions>
                </v-card>
            </template>
        </v-dialog>
    </div>
</template>
<style>
.nickname-input:focus {
    box-shadow: none;
}

.nickname-container {
    border-bottom: 1px solid white;
    margin-bottom: -1px;
}

.v-list-item__content {
    min-height: 0;
    padding: 0.5rem;
}

.see-chats {
    color: rgb(209 213 219);
    text-decoration: underline;
}

.chat-container {
    height: 80vh;
}

.chat-window {
    min-width: 30rem;
    max-width: 50%;
    width: 50vw;
    margin: 2em auto;
    height: 72vh;
}

.content-container {
    height: 90%;
    overflow-y: scroll;
}

.chat-input {
    width: 100%;
}

.chat-scroll {
    position: relative;
}

.fixed-wrapper {
    width: 220px;
    position: absolute;
    bottom: 3rem;
    left: 0;
    right: 0;
    margin: auto;
}

.to-bottom-btn {
    width: 220px;
    position: fixed;
    opacity: 0.8;
}

.to-bottom-btn:hover {
    opacity: 1;
}

.empty-message-container {
    height: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 4rem;
}

.start-chat-container {
    height: 80%;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 4rem;
}

.empty-message {
    text-align: center;
}

.message {
    box-shadow: 0 2px 6px 0 rgb(0 0 0 / 0.6);
}

.v-input__details {
    display: none;
}
</style>
