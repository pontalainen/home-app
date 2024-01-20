<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { toRefs, ref, onMounted, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';

const Echo = window.Echo;

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    chat: {
        type: Object,
        required: false,
        default: () => ({}),
    },
    lastMessageId: {
        type: Number,
        required: false,
        default: 1,
    }
})
const { user, chat } = toRefs(props);
let { lastMessageId } = toRefs(props);
chat.value.messages = chat.value.messages.reverse();

const newMessage = ref('');
let chatScroll = ref(null);
let chatInput = ref(null);
let isAtBottom = ref(true);
let loading = ref(false);

// Regular function for immediate response
const sendMessage = () => {
    if (loading.value || newMessage.value === '') {
        return;
    }

    loading.value = true;
    saveMessage(newMessage.value);
    newMessage.value = '';
}

const saveMessage = async (content) => {
    try {
        await axios.post(route('chat::sendMessage', { chat: chat.value, user: user.value }), {
            content,
        });
        await nextTick();
        scrollToBottom();
    } catch (error) {
        console.error(error);
        alert(error)
    } finally {
        loading.value = false;
    }
}

let noMoreMessages = false;
const loadMessages = async ({ done }) => {
    if (noMoreMessages) {
        done('empty');
        return;
    }

    try {
        const previousScrollHeight = chatScroll.value.scrollHeight;
        const previousScrollTop = chatScroll.value.scrollTop;

        const newMessages = await axios.post(
            route('chat::loadMessages', { chat: chat.value }),
            { lastMessageId: lastMessageId.value }
        );

        if (typeof newMessages.data.messages === 'object') {
            chat.value.messages.unshift(...newMessages.data.messages);
            lastMessageId = newMessages.data.lastMessageId;

            newMessage.value = '';

            await nextTick();
            const newScrollHeight = chatScroll.value.scrollHeight;
            chatScroll.value.scrollTop = previousScrollTop + (newScrollHeight - previousScrollHeight);

            done('ok')
        } else {
            noMoreMessages = true;
            done('empty')
        }

    } catch (error) {
        console.error(error);
        alert(error)
        done('error')
    }
}

onMounted(() => {
    if (chat.value) {
        chatScroll.value.addEventListener('scroll', () => {
            const { scrollTop, scrollHeight, clientHeight } = chatScroll.value;
            isAtBottom.value = scrollTop + clientHeight + 800 >= scrollHeight;
        });
        scrollToBottom();

        Echo.private('chat.' + chat.value.id)
            .listen('MessageSent', async (e) => {
                chat.value.messages.push(e.message);
                await nextTick();
                scrollToBottom();
            })
    }
})

const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

const scrollToBottom = () => {
    chatScroll.value.scrollTop = chatScroll.value.scrollHeight;
}

const switchChat = (newChat) => {
    console.log('Mottaget!', newChat)
    // chat.value = newChat;
    // lastMessageId.value = 1;
    // noMoreMessages = false;
    // scrollToBottom();
}

</script>

<template>
    <Head title="Chat" />

    <AuthenticatedLayout @switch-chat="switchChat">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="chat-container bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg flex">
                    <div v-if="chat" class="chat-window p-6 rounded-lg">
                        <div class="content-container bg-blue-100 text-black rounded-lg chat-scroll" ref="chatScroll">
                            <v-infinite-scroll v-if="chat.messages.length" @load="loadMessages" side="start">
                                <div v-for="(message, index) in chat.messages" :key="index" class="m-4">
                                    <p class="p-4 rounded-lg message"
                                        :class="message.user_id === user.id ? 'bg-blue-600 ml-20' : 'bg-green-600 mr-20'">
                                        <span class="text-xs">
                                            {{ message.user ? message.user.name : '[User deleted]' }}<br>
                                        </span>
                                        {{ message.content }} <br>
                                        <span class="text-xs">
                                            {{ formatDate(message.sent_at) }}
                                        </span>
                                    </p>
                                </div>
                                <div class="fixed-wrapper">
                                    <v-btn v-if="!isAtBottom" @click="scrollToBottom" class="to-bottom-btn">
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
                            <input placeholder="Chat..." v-model="newMessage" class="chat-input bg-blue-100 rounded-lg"
                                @keyup.enter="sendMessage" ref="chatInput" />
                            <v-progress-circular v-if="loading" indeterminate color="white ml-2"></v-progress-circular>
                        </div>
                    </div>

                    <div v-else class="start-chat-container w-full">
                        <p class="text-gray-300">
                            Start a chat with someone!
                        </p>
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
</style>
