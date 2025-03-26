// chat.js

function setupChat(userId, currentChatUserId, totalUnread, friendIds, updateFriendList, updateChatBadge) {
  let isSending = false;

  $(document).on('click', '.chat-friend', function() {
      currentChatUserId = $(this).data('user-id');
      const friendName = $(this).data('user-name');
      const friendImg = $(this).find('img').attr('src');

      $('.chat-item').removeClass('active');
      $(this).addClass('active');
      $('#chat-header-img').attr('src', friendImg);
      $('#chat-header-name').text(friendName);
      $('.chat-header, .chat-input').show();
      $('#chat-messages').empty();

      $.ajax({
          url: 'index.php?page=chat&action=get_messages',
          type: 'GET',
          data: { friend_id: currentChatUserId },
          dataType: 'json',
          success: function(messages) {
              let unreadCount = 0;
              messages.forEach(msg => {
                  const messageClass = msg.sender_id == userId ? 'sent' : 'received';
                  let messageHtml = `
                      <div class="message ${messageClass}" data-message-id="${msg.id || Date.now()}">
                          ${msg.message ? '<div class="message-text">' + msg.message + '</div>' : ''}
                          <div class="timestamp">${msg.timestamp} ${msg.sender_id == userId ? (msg.is_read ? '<i class="bi bi-check2-all"></i>' : '<i class="bi bi-check"></i>') : ''}</div>
                  `;
                  if (msg.file_name) {
                      const fileIcon = getFileIcon(msg.file_type, msg.file_name);
                      const fileTypeText = msg.file_type ? msg.file_type.split('/')[1] || msg.file_type : (msg.file_name.split('.').pop() || 'unknown');
                      const fileSizeText = formatFileSize(msg.file_size);
                      messageHtml += `
                          <div class="file-details">
                              <span class="file-name">${msg.file_name}</span>
                              <span class="file-type">(${fileTypeText})</span>
                              <br/>
                          </div>
                          <div class="file-divider"></div>
                          <span class="file-size">${fileSizeText}</span><br/><br/>
                          <a class="btn btn-light text-dark btn-sm" href="./upload/file_chats/${msg.sender_id}/${msg.file_name}" 
                             download="${msg.file_name}" 
                             class="file-download">${fileIcon} Download</a>
                      `;
                  }
                  messageHtml += `</div>`;
                  $('#chat-messages').append(messageHtml);
                  if (msg.receiver_id == userId && !msg.is_read) unreadCount++;
              });
              $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
              $('#chat-unread-count').text(unreadCount).toggle(unreadCount > 0);
          },
          error: function(xhr, status, error) {
              console.error('Error fetching messages:', error);
              $('#chat-messages').html('<p class="text-danger text-center">Gagal memuat pesan. Silakan coba lagi.</p>');
          }
      });

      const unreadCount = parseInt($(this).find('.badge').text() || 0);
      $(this).find('.badge').remove();
      if (unreadCount > 0) {
          totalUnread -= unreadCount;
          updateChatBadge();
      }
      const ws = getWebSocket();
      if (ws && ws.readyState === WebSocket.OPEN) {
          ws.send(JSON.stringify({
              type: 'read_message',
              user_id: userId,
              friend_id: currentChatUserId
          }));
          ws.send(JSON.stringify({
              type: 'get_status',
              user_id: userId,
              friend_id: currentChatUserId
          }));
      }
  });

  function sendMessage(file = null) {
      if (!currentChatUserId || isSending || !getWebSocket() || getWebSocket().readyState !== WebSocket.OPEN) {
          isSending = false;
          return;
      }
      isSending = true;

      const message = $('#message-input').val().trim();
      if (!message && !file) {
          isSending = false;
          return;
      }

      const timestamp = new Date().toLocaleString('id-ID', { 
          hour: '2-digit', 
          minute: '2-digit', 
          second: '2-digit' 
      });
      const messageId = Date.now();

      if (file) {
          const formData = new FormData();
          formData.append('file', file);
          formData.append('sender_id', userId);
          formData.append('receiver_id', currentChatUserId);
          formData.append('message', message);

          $.ajax({
              url: 'index.php?page=chat&action=upload_file',
              type: 'POST',
              data: formData,
              contentType: false,
              processData: false,
              dataType: 'json',
              success: function(response) {
                  if (response.success) {
                      const fileType = file.type;
                      const fileSize = file.size;
                      const fileTypeText = fileType.split('/')[1] || fileType;
                      const fileSizeText = formatFileSize(fileSize);
                      const messageData = {
                          type: 'message',
                          user_id: userId,
                          receiver_id: currentChatUserId,
                          message: message,
                          file_name: response.file_name,
                          file_type: fileType,
                          file_size: fileSize,
                          timestamp: timestamp,
                          is_read: false,
                          message_id: messageId
                      };
                      getWebSocket().send(JSON.stringify(messageData));

                      let messageHtml = `
                          <div class="message sent" data-message-id="${messageId}">
                              ${message ? '<div class="message-text">' + message + '</div>' : ''}
                              <div class="timestamp">${timestamp} <i class="bi bi-check"></i></div>
                      `;
                      if (response.file_name) {
                          const fileIcon = getFileIcon(fileType, response.file_name);
                          messageHtml += `
                              <div class="file-details">
                                  <span class="file-name">${response.file_name}</span>
                                  <span class="file-type">(${fileTypeText})</span>
                                  <span class="file-size">${fileSizeText}</span>
                              </div>
                              <div class="file-divider"></div>
                              <a href="./upload/file_chats/${userId}/${response.file_name}" 
                                 download="${response.file_name}" 
                                 class="file-download">${fileIcon} Download</a>
                          `;
                      }
                      messageHtml += `</div>`;
                      $('#chat-messages').append(messageHtml);
                      $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
                  } else {
                      alert(response.message);
                  }
                  isSending = false;
              },
              error: function(xhr, status, error) {
                  console.error('Error uploading file:', error);
                  alert('Gagal mengunggah file. Silakan coba lagi.');
                  isSending = false;
              }
          });
      } else if (message) {
          const messageData = {
              type: 'message',
              user_id: userId,
              receiver_id: currentChatUserId,
              message: message,
              timestamp: timestamp,
              is_read: false,
              message_id: messageId
          };
          getWebSocket().send(JSON.stringify(messageData));

          const messageHtml = `
              <div class="message sent" data-message-id="${messageId}">
                  <div class="message-text">${message}</div>
                  <div class="timestamp">${timestamp} <i class="bi bi-check"></i></div>
              </div>`;
          $('#chat-messages').append(messageHtml);
          $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
          isSending = false;
      }

      $('#message-input').val('');
      $('#file-input').val('');
      updateFriendList();
  }

  $('#send-btn').on('click', function(e) {
      e.preventDefault();
      sendMessage();
  });

  $('#message-input').on('keypress', function(e) {
      if (e.which === 13 && !e.shiftKey) {
          e.preventDefault();
          sendMessage();
      }
  });

  $('#attach-btn').on('click', function() {
      $('#file-input').click();
  });

  $('#file-input').on('change', function() {
      const file = this.files[0];
      if (file) {
          if (file.size > 10 * 1024 * 1024) {
              alert('Ukuran file melebihi batas 10MB.');
              $(this).val('');
              return;
          }
          sendMessage(file);
      }
  });

  $('#friend-list-search').on('input', debounce(function() {
      const query = $(this).val().trim();
      updateFriendList(query);
  }, 300));

  $('#search-friend-btn').on('click', function() {
      const query = $('#friend-list-search').val().trim();
      updateFriendList(query);
  });

  return { currentChatUserId, totalUnread };
}