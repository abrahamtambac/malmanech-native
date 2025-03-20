const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const mysql = require('mysql2');

const app = express();
const server = http.createServer(app);
const io = new Server(server);

// MySQL Connection
const db = mysql.createConnection({
    host: 'localhost', // Use '127.0.0.1' instead of 'localhost' to avoid ::1 issues
    user: 'root',      // Replace with your MySQL username
    password: 'root',      // Replace with your MySQL password
    database: 'db_chatai' // Replace with your database name
});

db.connect((err) => {
    if (err) {
        console.error('Database connection failed:', err);
        return;
    }
    console.log('Connected to MySQL');
});

io.on('connection', (socket) => {
    console.log('A user connected:', socket.id);

    socket.on('join', (userId) => {
        socket.join(userId);
        console.log(`User ${userId} joined`);
    });

    socket.on('sendMessage', (data) => {
        const { sender_id, receiver_id, message, file } = data;

        // Verify friendship before sending
        db.query(
            'SELECT * FROM tb_friends WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) AND status = "accepted"',
            [sender_id, receiver_id, receiver_id, sender_id],
            (err, results) => {
                if (err) throw err;
                if (results.length > 0) {
                    // Save message to database
                    db.query(
                        'INSERT INTO tb_messages (sender_id, receiver_id, message, file_name, file_data) VALUES (?, ?, ?, ?, ?)',
                        [sender_id, receiver_id, message, file?.name || null, file?.data || null],
                        (err) => {
                            if (err) throw err;
                            io.to(receiver_id).emit('receiveMessage', data);
                            io.to(sender_id).emit('receiveMessage', data); // Echo back to sender
                        }
                    );
                } else {
                    socket.emit('error', 'You must be friends to send a message.');
                }
            }
        );
    });

    socket.on('disconnect', () => {
        console.log('User disconnected:', socket.id);
    });
});

server.listen(3000, () => {
    console.log('Server running on port 3000');
});