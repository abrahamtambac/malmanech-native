// websocket.js

let ws = null;
let statusInterval = null;

function connectWebSocket(userId, handleMessage, handleFriendRequest, handleFriendAccepted, handleReadMessage, handleStatusUpdate) {
    const wsUrl = `${window.location.protocol === 'https:' ? 'wss' : 'ws'}://${window.location.hostname}:8080`;
    ws = new WebSocket(wsUrl);

    ws.onopen = function() {
        ws.send(JSON.stringify({ type: 'register', user_id: userId }));
        console.log('WebSocket connection opened');
        startStatusUpdate(userId);
    };

    ws.onmessage = function(event) {
        try {
            const data = JSON.parse(event.data);
            switch (data.type) {
                case 'message':
                    handleMessage(data);
                    break;
                case 'friend_request':
                    handleFriendRequest(data);
                    break;
                case 'friend_accepted':
                    handleFriendAccepted(data);
                    break;
                case 'read_message':
                    handleReadMessage(data);
                    break;
                case 'status_update':
                    handleStatusUpdate(data);
                    break;
                default:
                    console.log('Unhandled WebSocket message type:', data.type);
            }
        } catch (error) {
            console.error('Error parsing WebSocket message:', error);
        }
    };

    ws.onerror = function(error) {
        console.error('WebSocket error:', error);
    };

    ws.onclose = function() {
        console.log('WebSocket connection closed, attempting to reconnect...');
        clearInterval(statusInterval);
        setTimeout(() => connectWebSocket(userId, handleMessage, handleFriendRequest, handleFriendAccepted, handleReadMessage, handleStatusUpdate), 2000);
    };
}

function startStatusUpdate(userId) {
    clearInterval(statusInterval);
    statusInterval = setInterval(() => {
        if (ws && ws.readyState === WebSocket.OPEN) {
            const lastSeen = new Date().toISOString().slice(0, 19).replace('T', ' ');
            ws.send(JSON.stringify({
                type: 'status_update',
                user_id: userId,
                last_seen: lastSeen
            }));
        }
    }, 30000);
}

function getWebSocket() {
    return ws;
}

function cleanupWebSocket() {
    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.close();
        console.log('WebSocket connection closed by cleanup');
    }
    clearInterval(statusInterval);
}