<script setup>
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { toRefs, ref, onMounted, nextTick } from 'vue';
    import { Head } from '@inertiajs/vue3';

    const props = defineProps({
        user: {
            type: Object,
            required: true,
        },
        chat: {
            type: Object,
            required: true,
        },
        lastMessageId: {
            type: Number,
            required: true,
        }
    })
    const { user, chat } = toRefs(props);
    let { lastMessageId } = toRefs(props);
    chat.value.messages = chat.value.messages.reverse();

    const newMessage = ref('');

    const sendMessage = async () => {
        // Don't send an empty message
        if (newMessage.value === '') {
            return;
        }

        try {
            // Make the API call to send the message
            const resp = await axios.post(route('chat::sendMessage', {chat: chat.value, user: user.value}), {
                content: newMessage.value,
            });

            // Push the new message to the local array of messages
            chat.value.messages.push({
                content: newMessage.value,
                user: user.value,
                created_at: new Date().toISOString() // Use the current time
            });

            // Clear the new message input
            newMessage.value = '';

            if (await resp) {
                const objDiv = document.getElementById("chat-scroll");
                objDiv.scrollTop = objDiv.scrollHeight;
            }
        } catch (error) {
            console.error(error);
            alert(error)
        }
    }

    let noMoreMessages = false;
    const loadMessages = async ({ done }) => {
        if (noMoreMessages) {
            done('empty');
            return;
        }

        try {
            const chatScroll = document.getElementById("chat-scroll");
            const previousScrollHeight = chatScroll.scrollHeight;
            const previousScrollTop = chatScroll.scrollTop;

            const newMessages = await axios.post(
                route('chat::loadMessages', {chat: chat.value}),
                { lastMessageId: lastMessageId.value }
            );

            if (typeof newMessages.data.messages === 'object') {
                // Push the new message to the local array of messages
                chat.value.messages.unshift(...newMessages.data.messages);
                lastMessageId = newMessages.data.lastMessageId;

                // Clear the new message input
                newMessage.value = '';

                await nextTick();
                const newScrollHeight = chatScroll.scrollHeight;
                chatScroll.scrollTop = previousScrollTop + (newScrollHeight - previousScrollHeight);

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
        const chatScroll = document.getElementById("chat-scroll");
        chatScroll.scrollTop = chatScroll.scrollHeight;
    })
</script>

<template>
    <Head title="Chat" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="chat-container bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg flex">
                    <div class="chat-window p-6 bg-pink-600 rounded-lg">
                        <div class="content-container bg-blue-100 text-black" id="chat-scroll" ref="scrollContainer">
                            <v-infinite-scroll @load="loadMessages" side="start">
                                <div v-for="(message, index) in chat.messages" :key="index">
                                    <p
                                        class="m-4 p-4 rounded-lg"
                                        :class="message.user.id === user.id ? 'bg-blue-600 ml-20': 'bg-green-600 mr-20'"
                                    >
                                        <span class="text-xs">
                                            {{ message.user.name }}<br>
                                        </span>
                                        {{ message.content }} <br>
                                        <span class="text-xs">
                                            {{ message.sent_at }}
                                        </span>
                                    </p>
                                </div>
                                <template #empty></template>
                            </v-infinite-scroll>
                        </div>

                        <div class="input-container flex mt-4">
                            <input placeholder="Chat..." v-model="newMessage" class="chat-input bg-white rounded-lg" @keyup.enter="sendMessage" />
                            <v-btn @click="sendMessage" class="ml-2">send</v-btn>
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
        overflow-y: scroll;
    }
    .chat-input {
        width: 100%;
    }
</style>
