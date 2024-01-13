<script setup>
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { toRefs, ref } from 'vue';
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
    const { user, chat } = toRefs(props);

    const newMessage = ref('');

    const sendMessage = async () => {
        try {
            // Make the API call to send the message
            await axios.post(route('chat::sendMessage', {chat: chat.value, user: user.value}), {
                content: newMessage.value,
            });

            // Push the new message to the local array of messages
            chat.messages.push({
                content: newMessage.value,
                user_id: user.id,
                created_at: new Date().toISOString() // Use the current time
            });

            // Clear the new message input
            newMessage.value = '';
        } catch (error) {
            console.error(error);
            alert(error)
        }
    }
</script>

<template>
    <Head title="Chat" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="chat-container bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg flex">
                    <div class="chat-window p-6 bg-pink-600 rounded-lg">
                        <div class="content-container bg-blue-100 text-black">
                            <div v-for="(message, index) in chat.messages" :key="index">
                                <p
                                    class="m-4 p-4 rounded-lg"
                                    :class="message.user_id === user.id ? 'bg-blue-600 ml-12': 'bg-green-600 mr-12'"
                                >
                                    <span class="text-xs">
                                        {{ message.user.name }}<br>
                                    </span>
                                    {{ message.content }} <br>
                                    <span class="text-xs">
                                        {{ message.created_at }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="input-container flex mt-4">
                            <input placeholder="Chat..." v-model="newMessage" class="chat-input bg-white rounded-lg" />
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
