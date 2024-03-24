export const getStatusMessageContent = (message) => {
    let messageContent = 'Status changed';

    switch (message.status_type) {
        case 'bubble_color':
            messageContent = `<strong>${message.user.name}</strong> is lookin' kinda <strong style="color: ${message.content};">${message.content}</strong>!`;
            break;
        case 'nickname':
            messageContent = `<strong>${message.user.name}</strong> is now known as <strong>${message.content}</strong>!`;
            break;
        case 'members_added': {
            const namesArray = message.content.split('|');
            const formattedNames = namesArray.length > 1
                ? `${namesArray.slice(0, -1).map((name) => `<strong>${name}</strong>`).join(', ')} and <strong>${namesArray.slice(-1)}</strong>`
                : `<strong>${namesArray[0]}</strong>`;

            messageContent = `${formattedNames} have joined the chat!`;
            break;
        }
        default:
            break;
    }

    return messageContent;
};

export const formatDate = (dateString, isUTC = true) => {
    // Adjust the dateString based on whether it's UTC or already in the correct timezone
    const adjustedDateString = isUTC ? `${dateString}Z` : dateString;
    const date = new Date(adjustedDateString);
    const options = {
        year: 'numeric', 
        month: 'long', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit', 

        timeZone: Intl.DateTimeFormat().resolvedOptions().timeZone
    };

    return date.toLocaleDateString(undefined, options);
};

export const genTempId = () =>
    Math.random().toString(36).substring(2, 15) +
    Math.random().toString(36).substring(2, 15);

export const getChatName = (chat) => {
    if (chat.is_group) {
        return chat.name;
    }

    return chat.otherUser.pivot.nickname ? chat.otherUser.pivot.nickname : chat.otherUser.name;
};

export const getCurrentChatUser = (chat, userId) => {
    if (!chat || !userId) {
        return null;
    }

    return chat.users.find((u) => u.id === userId);
};
