<script setup>
// 1. Imports and Component Setup
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { toRefs, ref, onMounted, nextTick, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { getStatusMessageContent, formatDate, genTempId } from '@/helpers';

const { Echo } = window;

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    userFriends: {
        type: Array,
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
const { user, chatProp, lastMessageIdProp, userFriends } = toRefs(props);

// Constant/non-reactive variables
const groupModalText = `Creating a <span class="font-bold"> group chat </span>
                            will not remove this chat, but will create a new chat with up to
                            <span class="font-bold"> 10 </span>
                            friends.`;

const groupModalFailedText = `Failed to create group chat.<br>
                                Please refresh the page and try again.`;

const groupInviteMembersText = `Add members to this group chat by selecting them below.<br>
                                    You can add up to 10 members to a group chat.`;

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
const colorModalOpen = ref(false);
const groupModalOpen = ref(false);
const membersModalOpen = ref(false);
const groupCreationFailed = ref(false);
const selectedUser = ref(chat.value ? chat.value.users.find((u) => u.id === user.value.id) : null);
const prevUserValues = ref([]);
const nameHover = ref(false);
const editingName = ref(false);
const nicknameInputEl = ref('');
const availableMembers = ref([]);
const selectedMembers = ref([]);
const addingMembers = ref(false);
const viewingMembers = ref(true);

if (chat.value) {
    chat.value.messages = chat.value.messages.reverse();
}

// 3. Computed Properties and Watchers
watch(selectedUser, () => {
    revertUserChanges();
});

watch(groupModalOpen, (newValue) => {
    if (!newValue) {
        selectedMembers.value = [];
        addingMembers.value = false;
        viewingMembers.value = false;
    }
});

const chatOptions = computed(() => {
    return chat.value.is_group
        ? ['Change bubble colors', 'Add members']
        : ['Change bubble colors', 'Create group chat'];
});

const updateUserUrl = computed(() => {
    return route('chat::updateUser', { chat: chat.value.id, user: selectedUser.value.id });
});

const updateChatUrl = computed(() => {
    return route('chat::update', { chat: chat.value.id });
});

const otherUser = computed(() => {
    return chat.value ? chat.value.users.find((u) => u.id !== user.value.id) : null;
});

const nicknameModel = computed({
    get() {
        return chat.value.is_group ? chat.value.name : selectedUser.value.pivot.nickname;
    },
    set(value) {
        if (chat.value.is_group) {
            chat.value.name = value;
        } else {
            selectedUser.value.pivot.nickname = value;
        }
    },
});

// 4. Lifecycle Hooks
onMounted(async () => {
    if (chat.value) {
        chatScroll.value.addEventListener('scroll', () => {
            const { scrollTop, scrollHeight, clientHeight } = chatScroll.value;
            isAtBottom.value = scrollTop + clientHeight + 800 >= scrollHeight;
        });
        scrollToBottom();

        Echo.private(`chat.${chat.value.id}`).listen('MessageSent', async (e) => {
            if (e.tempId && e.message.user_id === user.value.id) {
                const tempMessage = chat.value.messages.find((message) => message.tempId === e.tempId);
                if (tempMessage) tempMessage.status = 'sent';
            } else {
                chat.value.messages.push(e.message);
            }

            if (e.message.type === 'status') {
                updateFromStatusMessage(e.message);
            }

            await nextTick();
            scrollToBottom();
        });

        await getAvailableMembers();
    }
});

// 5. Methods
const sendMessage = (content = null) => {
    let messageContent = content;

    // If the content is an event, it means the function was triggered by an event listener
    // and the actual message content should be taken from newMessage.value
    if (content instanceof Event) {
        messageContent = newMessage.value;
    }

    if (loading.value || !messageContent) {
        return;
    }

    const tempId = genTempId();
    const tempMessage = {
        tempId,
        user_id: user.value.id,
        user: {
            name: user.value.name,
        },
        content: messageContent,
        type: 'text',
        sent_at: new Date(),
        status: 'sending',
    };
    chat.value.messages.push(tempMessage);

    loading.value = true;
    saveMessage(messageContent, tempId);
    newMessage.value = '';
};

const resendMessage = (index) => {
    const message = chat.value.messages[index];
    sendMessage(message.content);
    chat.value.messages.splice(index, 1);
};

const saveMessage = async (content, tempId) => {
    try {
        const resp = await axios.post(route('chat::sendMessage', { chat: chat.value.id }), { content, tempId });
        if (resp.status === 200) {
            const tempMessage = chat.value.messages.find((message) => message.tempId === tempId);
            if (tempMessage) tempMessage.status = 'sent';
        }
    } catch (error) {
        console.error(error);
        const tempMessage = chat.value.messages.find((message) => message.tempId === tempId);
        if (tempMessage) tempMessage.status = 'failed';
    } finally {
        loading.value = false;
        await nextTick();
        scrollToBottom();
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

const switchChat = (newChat) => {
    router.visit(route('chat::chat', { chat: newChat }));
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

const cancelNameEdit = async () => {
    await new Promise((resolve) => {
        setTimeout(resolve, 50);
    });
    editingName.value = false;
    revertUserChanges();
};

const saveNickname = async () => {
    if (chat.value.is_group) {
        try {
            await axios.put(updateChatUrl.value, {
                name: nicknameModel.value,
            });
            editingName.value = false;
        } catch {
            revertUserChanges();
        }
    } else {
        try {
            await axios.put(updateUserUrl.value, {
                nickname: selectedUser.value.pivot.nickname,
            });
            editingName.value = false;
        } catch {
            revertUserChanges();
        }
    }
};

const updateFromStatusMessage = (sm) => {
    if (sm.status_type === 'name') {
        chat.value.name = sm.content;
        return;
    }
    chat.value.users = chat.value.users.map((u) => {
        if (u.id === sm.user_id) {
            u.pivot[sm.status_type] = sm.content;
        }
        return u;
    });
};

const createGroupChat = async () => {
    try {
        const resp = await axios.post(route('chat::createGroupChat', { chat: chat.value.id }));
        router.visit(route('chat::chat', { chat: resp.data }));
        groupModalOpen.value = false;
    } catch (error) {
        groupCreationFailed.value = true;
        console.error(error);
    }
};

const getAvailableMembers = async () => {
    try {
        const resp = await axios.get(route('chat::getAvailableMembers', chat.value.id));
        availableMembers.value = resp.data;
    } catch (error) {
        console.error(error);
        availableMembers.value = [];
    }
};

const addMembers = async () => {
    if (selectedMembers.value.length === 0) {
        return;
    }
    addingMembers.value = true;

    try {
        await axios.post(route('chat::addMembers', chat.value.id), {
            members: selectedMembers.value,
        });
    } catch (error) {
        console.error(error);
    } finally {
        groupModalOpen.value = false;
    }
};

// 6. External or Helper Functions
const scrollToBottom = () => {
    if (chatScroll.value) {
        chatScroll.value.scrollTop = chatScroll.value.scrollHeight;
    }
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
    // Dark text for light backgrounds and vice versa
    return yiq >= 128 ? '#000000' : '#FFFFFF';
};

const getMessageStatusDate = (message) => {
    switch (message.status) {
        case 'sending':
            return 'Sending...';
        case 'sent':
            return `Sent at ${formatDate(message.sent_at, !!message.id)}`;
        case 'delivered':
            return `Delivered at ${formatDate(message.sent_at, !!message.id)}`;
        case 'read':
            return `Sent at ${formatDate(message.sent_at, !!message.id)}`;
        case 'failed':
            return 'Message could not be sent. Try again ';
        default:
            return message.status;
    }
};

const getMemberOptions = (member) => {
    if (userFriends.value.includes(member.id)) return ['Remove friend'];
    return ['Add friend'];
};
</script>
<template>
    <div>
        <Head title="Chat" />

        <AuthenticatedLayout :current-chat="chat" :user="user" :open-drawer="drawer" @switch-chat="switchChat">
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div class="chat-container bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg flex flex-col">
                        <div class="w-full -mb-12 flex justify-end">
                            <v-btn
                                v-if="chat.is_group"
                                class="mt-6 mr-4 !text-blue-100"
                                variant="text"
                                size="regular"
                                @click="membersModalOpen = true"
                            >
                                <v-icon icon="mdi-account-group-outline" size="x-large" />
                            </v-btn>
                            <v-menu location="bottom">
                                <!-- eslint-disable-next-line vue/no-template-shadow -->
                                <template #activator="{ props }">
                                    <v-btn
                                        class="mt-6 mr-8 !text-blue-100"
                                        variant="text"
                                        size="regular"
                                        v-bind="props"
                                    >
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
                                    <div class="flex justify-center nickname-container" @blur="cancelNameEdit">
                                        <input
                                            ref="nicknameInputEl"
                                            v-model="nicknameModel"
                                            placeholder="User nickname"
                                            class="nickname-input text-blue-100 font-bold text-xl text-center p-0"
                                            variant="solo"
                                            maxlength="25"
                                            @keyup.enter="saveNickname"
                                            @focusout="cancelNameEdit"
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
                                <div v-else class="text-blue-100 font-bold text-xl relative" style="max-width: 50%">
                                    <p class="truncate">
                                        <span v-if="chat.is_group">
                                            {{ chat.name }}
                                        </span>
                                        <span v-else>
                                            {{ otherUser.pivot.nickname ?? otherUser.name }}
                                        </span>
                                    </p>
                                    <v-btn
                                        variant="text"
                                        size="regular"
                                        class="ml-2 !absolute top-0 bottom-0 -right-6"
                                        :class="{ '!hidden': !nameHover }"
                                        @click="editName"
                                    >
                                        <v-icon icon="mdi-pencil" size="x-small" class="text-blue-100"></v-icon>
                                    </v-btn>
                                </div>
                            </div>

                            <div
                                ref="chatScroll"
                                class="content-container bg-blue-100 text-black rounded-lg chat-scroll"
                            >
                                <v-infinite-scroll v-if="chat.messages.length" side="start" @load="loadMessages">
                                    <div v-for="(message, index) in chat.messages" :key="index" class="m-4">
                                        <p
                                            v-if="message.type === 'text'"
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
                                                {{ getMessageStatusDate(message) }}
                                                <v-btn
                                                    v-if="message.status === 'failed'"
                                                    variant="text"
                                                    size="regular"
                                                    @click="resendMessage(index)"
                                                >
                                                    <v-icon
                                                        icon="mdi-refresh"
                                                        size="small"
                                                        :style="{
                                                            color: getTextColor(getBubbleColor(message.user_id)),
                                                        }"
                                                    ></v-icon>
                                                </v-btn>
                                            </span>
                                        </p>
                                        <p
                                            v-else-if="message.type === 'status'"
                                            class="flex flex-col justify-center items-center text-center"
                                        >
                                            <!-- eslint-disable-next-line vue/no-v-html -->
                                            <span class="w-fit mx-auto" v-html="getStatusMessageContent(message)" />
                                            <span class="text-xs mx-auto">
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
                                    <p class="empty-message flex flex-col space-y-2">
                                        <span> There are no messages in this chat yet. </span>
                                        <span> Awkward... </span>
                                        <span> Why don't you start a conversation? </span>
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
                                    color="white"
                                    class="ml-2"
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
                        variant="solo"
                        class="!bg-blue-50 mb-4 v-select"
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

        <v-dialog v-model="groupModalOpen" width="500" transition="dialog-bottom-transition">
            <template #default>
                <v-card class="!bg-gray-800 flex flex-col">
                    <v-btn
                        class="!absolute top-2 right-3"
                        variant="text"
                        color="white"
                        size="regular"
                        @click="groupModalOpen = false"
                    >
                        <v-icon icon="mdi-close"></v-icon>
                    </v-btn>
                    <div class="w-full flex flex-col justify-center p-8">
                        <div class="flex flex-col justify-center items-center">
                            <p class="text-white pb-6 text-center">
                                <!-- eslint-disable vue/no-v-html -->
                                <span v-if="groupCreationFailed" v-html="groupModalFailedText" />
                                <span v-else v-html="groupModalText" />
                                <!-- eslint-enable vue/no-v-html -->
                            </p>
                            <v-btn color="white" class="p-2" @click="createGroupChat">
                                Create group with {{ otherUser.name }}
                            </v-btn>
                        </div>
                    </div>
                </v-card>
            </template>
        </v-dialog>

        <v-dialog v-model="membersModalOpen" width="500" transition="dialog-bottom-transition">
            <template #default>
                <v-card class="!bg-gray-800 flex flex-col">
                    <v-btn
                        class="!absolute top-2 right-3"
                        variant="text"
                        color="white"
                        size="regular"
                        @click="membersModalOpen = false"
                    >
                        <v-icon icon="mdi-close"></v-icon>
                    </v-btn>
                    <div class="w-full flex flex-col justify-center p-8">
                        <div
                            v-if="viewingMembers"
                            class="overflow-y-scroll max-h-96 flex flex-col justify-center items-center"
                        >
                            <div
                                v-for="(member, index) in chat.users"
                                :key="index"
                                class="bg-white my-2 flex w-96 rounded items-center"
                            >
                                <p variant="text" size="small" color="black" class="-mr-14 ml-4">p-img</p>
                                <div class="py-2 mx-auto flex flex-col justify-center items-center">
                                    <p class="font-bold">
                                        {{ member.name }}
                                    </p>
                                    <small>
                                        Member since
                                        <span class="font-bold">{{ formatDate(member.pivot.joined_at) }}</span>
                                    </small>
                                </div>
                                <v-menu location="top">
                                    <!-- eslint-disable-next-line vue/no-template-shadow -->
                                    <template #activator="{ props }">
                                        <v-btn
                                            variant="text"
                                            size="regular"
                                            color="black"
                                            class="-ml-14 mr-4"
                                            v-bind="props"
                                        >
                                            <v-icon icon="mdi-dots-horizontal"></v-icon>
                                        </v-btn>
                                    </template>

                                    <v-list width="160">
                                        <v-list-item v-for="(option, i) in getMemberOptions(member)" :key="i">
                                            <v-list-item-title @click="console.log('tja')">
                                                <v-btn variant="text">
                                                    {{ option }}
                                                </v-btn>
                                            </v-list-item-title>
                                        </v-list-item>
                                    </v-list>
                                </v-menu>
                                <!-- <v-btn variant="text" size="regular" color="black" class="-ml-14 mr-4">
                                    <v-icon icon="mdi-dots-horizontal"></v-icon>
                                </v-btn> -->
                            </div>
                        </div>
                        <div v-else class="flex flex-col justify-center items-center">
                            <p class="text-white pb-6 text-center">
                                <!-- eslint-disable-next-line vue/no-v-html -->
                                <span v-html="groupInviteMembersText" />
                            </p>
                            <div class="flex items-end">
                                <v-autocomplete
                                    v-model="selectedMembers"
                                    clearable
                                    chips
                                    multiple
                                    auto-select-first
                                    item-title="name"
                                    item-value="id"
                                    label="New group members"
                                    :items="availableMembers"
                                ></v-autocomplete>
                                <v-btn class="ml-8 flex" @click="addMembers">
                                    <v-progress-circular
                                        v-if="addingMembers"
                                        indeterminate
                                        :size="20"
                                        color="black"
                                    ></v-progress-circular>
                                    <v-icon v-else icon="mdi-send-check-outline"></v-icon>
                                </v-btn>
                            </div>
                        </div>
                    </div>
                </v-card>
            </template>
        </v-dialog>
    </div>
</template>
<style>
.v-autocomplete .v-label {
    background: white;
    padding: 5px 0px;
    width: 93%;
    margin-top: -7px;
}

.v-autocomplete .v-field__input {
    max-height: 8rem;
    overflow: scroll;
}

.v-autocomplete .v-input__control {
    width: 20rem;
}

.v-autocomplete .v-input__control,
.v-autocomplete .v-field__field {
    border-radius: 4px;
}

.v-autocomplete input {
    box-shadow: none !important;
}

.v-field__field,
.v-field__overlay,
.v-input__control {
    background-color: white;
    outline: none;
}

.v-select,
.v-select .v-field {
    background-color: rgb(239 246 255);
}

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
