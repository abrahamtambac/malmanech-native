// main.js

$(document).ready(function() {
  const userId = '<?php echo json_encode($_SESSION['user_id']); ?>;
  let currentChatUserId = null;
  let totalUnread = <?php echo $totalUnread; ?>;
  let pendingCount = <?php echo $pendingCount; ?>;
  let friendIds = <?php echo json_encode(array_column($friends, 'id')); ?>;
  const messageSound = document.getElementById('message-sound');
  const friendRequestSound = document.getElementById('friend-request-sound');

  function updateFriendList(query = '') {
      $.ajax({
          url: 'index.php?page=chat&action=get_friends_with_latest',
          type: 'GET',
          data: { query: query },
          dataType: 'json',
          success: function(friends) {
              $('#friend-list-container').empty();
              if (!friends || friends.length === 0) {
                  $('#friend-list-container').html('<p class="text-muted p-2">Tidak ada teman untuk ditampilkan</p>');
                  return;
              }
              
              if (Array.isArray(friends)) {
                  friends.forEach(friend => {
                      const unreadCount = friend.unread_count || 0;
                      const friendImage = friend.profile_image ? `./upload/image/${friend.profile_image}` : './image/robot-ai.png';
                      const lastSeen = friend.last_seen || 'Tidak diketahui';
                      const latestMessage = friend.latest_message && friend.latest_message.message ? 
                          friend.latest_message.message.substring(0, 20) + (friend.latest_message.message.length > 20 ? '...' : '') : 
                          'Belum ada pesan';
                      const isActive = currentChatUserId == friend.id ? 'active' : '';
                      
                      const chatItem = `
                          <div class="chat-item chat-friend ${isActive}" data-user-id="${friend.id}" data-user-name="${friend.name}">
                              <div class="d-flex align-items-center position-relative">
                                  <div class="position-relative">
                                      ${unreadCount > 0 ? 
                                          `<img src="${friendImage}" class="rounded-circle me-2 profile-img" style="width: 35px; height: 35px;">
                                           <span class="badge bg-primary rounded-pill position-absolute" style="top: -5px; right: -5px; font-size: 0.7em;border-width: thick;">${unreadCount}</span>` : 
                                          `<img src="${friendImage}" class="rounded-circle me-2 border border-primary" style="width: 35px; height: 35px;">`}
                                  </div>
                                  <div class="flex-grow-1">
                                      <strong>   ${friend.name}</strong>
                                      <span class="last-seen">${lastSeen.includes('Last seen') ? lastSeen : (lastSeen ? `<span class="status-dot offline"></span>Last seen: ${lastSeen}` : '<span class="status-dot online"></span>Online')}</span>
                                  </div>
                              </div>
                          </div>`;
                      $('#friend-list-container').append(chatItem);
                  });
              }
              
              if (currentChatUserId) {
                  $(`.chat-friend[data-user-id="${currentChatUserId}"]`).addClass('active');
              }
          },
          error: function(xhr, status, error) {
              console.error('Error fetching friend list:', xhr.responseText, status, error);
              $('#friend-list-container').html('<p class="text-danger p-2">Gagal memuat daftar teman. Silakan coba lagi.</p>');
          }
      });
  }

  function updateChatBadge() {
      $('#chat-badge').remove();
      if (totalUnread > 0) {
          $('#chat-toggle').append(`<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="chat-badge">${totalUnread}</span>`);
      }
  }

  function updateFriendRequestBadge() {
      $('#friend-request-badge').remove();
      if (pendingCount > 0) {
          $('#friend-request-toggle').append(`<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="friend-request-badge">${pendingCount}</span>`);
      }
  }

  function handleMessage(data) {
      if (data.sender_id == userId) return;

      const messageClass = data.sender_id == userId ? 'sent' : 'received';
      let messageHtml = `
          <div class="message ${messageClass}" data-message-id="${data.message_id || Date.now()}">
              ${data.message ? '<div class="message-text">' + data.message + '</div>' : ''}
              <div class="timestamp">${data.timestamp} ${data.sender_id == userId ? '<i class="bi bi-check"></i>' : ''}</div>
      `;
      if (data.file_name) {
          const fileIcon = getFileIcon(data.file_type, data.file_name);
          const fileTypeText = data.file_type ? data.file_type.split('/')[1] || data.file_type : (data.file_name.split('.').pop() || 'unknown');
          const fileSizeText = formatFileSize(data.file_size);
          messageHtml += `
              <div class="file-details">
                  <span class="file-name">${data.file_name}</span>
                  <span class="file-type">(${fileTypeText})</span>
                  <br/>
              </div>
              <div class="file-divider"></div>
              <span class="file-size">${fileSizeText}</span>
              <a href="./upload/file_chats/${data.sender_id}/${data.file_name}" 
                 download="${data.file_name}" 
                 class="file-download">${fileIcon} Download</a>
          `;
      }
      messageHtml += `</div>`;

      if (data.sender_id == currentChatUserId || data.receiver_id == currentChatUserId) {
          $('#chat-messages').append(messageHtml);
          $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
          if (data.receiver_id == userId && data.sender_id != currentChatUserId) {
              const unreadCount = parseInt($('#chat-unread-count').text() || 0) + 1;
              $('#chat-unread-count').text(unreadCount).show();
          }
      }

      if (data.receiver_id == userId) {
          messageSound.play().catch(err => console.log('Audio error:', err));
          updateFriendList();
          if (!$('#chatModal').hasClass('show')) {
              const $friendItem = $(`.chat-friend[data-user-id="${data.sender_id}"]`);
              $('body').append(`
                  <div class="notification-toast toast show" role="alert" aria-live="assertive" aria-atomic="true">
                      <div class="toast-header">
                          <img src="${$friendItem.find('img').attr('src') || './image/robot-ai.png'}" class="rounded-circle me-2" style="width: 20px; height: 20px;">
                          <strong class="me-auto">${$friendItem.data('user-name') || 'Unknown'}</strong>
                          <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                      </div>
                      <div class="toast-body">${data.message ? data.message.substring(0, 50) : 'File attachment'}${data.message && data.message.length > 50 ? '...' : ''}</div>
                  </div>
              `);
              setTimeout(() => $('.notification-toast').remove(), 5000);
          }
      }
  }

  function handleFriendRequest(data) {
      if (data.friend_id == userId) {
          friendRequestSound.play().catch(err => console.log('Audio error:', err));
          pendingCount++;
          updateFriendRequestBadge();
          $('#pending-requests').prepend(`
              <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                  <div class="d-flex align-items-center">
                      <img src="${data.profile_image ? './upload/image/' + data.profile_image : './image/robot-ai.png'}" class="rounded-circle me-2" style="width: 40px; height: 40px;">
                      <span>${data.friend_name}</span>
                  </div>
                  <button class="btn btn-sm btn-success accept-friend-btn" data-user-id="${data.sender_id}" data-user-name="${data.friend_name}">Terima</button>
              </div>
          `);
          if ($('#pending-requests h6').length === 0) {
              $('#pending-requests').prepend('<h6>Permintaan Tertunda</h6>');
          }
      }
  }

  function handleFriendAccepted(data) {
      if (data.user_id == userId || data.friend_id == userId) {
          const friendId = data.user_id == userId ? data.friend_id : data.user_id;
          const friendName = data.friend_name;
          const friendImage = data.profile_image || './image/robot-ai.png';
          friendIds.push(friendId);
          if (data.friend_id == userId) {
              pendingCount--;
              updateFriendRequestBadge();
              $(`#pending-requests .d-flex:has(button[data-user-id="${data.user_id}"])`).remove();
              if ($('#pending-requests .d-flex').length === 0) {
                  $('#pending-requests').html('<p class="text-muted">Tidak ada permintaan tertunda</p>');
              }
          }
          updateFriendList();
      }
  }

  function handleReadMessage(data) {
      if (data.user_id == currentChatUserId && data.friend_id == userId) {
          $('#chat-messages .message.sent .timestamp').each(function() {
              const $timestamp = $(this);
              if (!$timestamp.find('.bi-check2-all').length) {
                  $timestamp.html($timestamp.text().replace('<i class="bi bi-check"></i>', '') + ' <i class="bi bi-check2-all"></i>');
              }
          });
          totalUnread -= parseInt($('#chat-unread-count').text() || 0);
          updateChatBadge();
          $('#chat-unread-count').hide();
      }
      const $friendItem = $(`.chat-friend[data-user-id="${data.user_id}"]`);
      $friendItem.find('.badge').remove();
      updateFriendList();
  }

  function handleStatusUpdate(data) {
      try {
          if (!data || !data.user_id) {
              console.warn('Invalid status update data:', data);
              return;
          }
          const safeUserId = $('<div>').text(data.user_id).html();
          const $friend = $(`.chat-friend[data-user-id="${safeUserId}"]`);
          if ($friend.length) {
              let statusText, statusDot;
              if (data.last_seen) {
                  const lastSeenDate = new Date(data.last_seen);
                  statusText = `Last seen: ${lastSeenDate.toLocaleString('id-ID', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: 'short', year: 'numeric' })}`;
                  statusDot = '<span class="status-dot offline"></span>';
              } else {
                  statusText = 'Online';
                  statusDot = '<span class="status-dot online"></span>';
              }
              $friend.find('.last-seen').html(`${statusDot} ${statusText}`);
          }
          if (data.user_id === currentChatUserId) {
              let chatStatusHtml;
              if (data.last_seen) {
                  const lastSeenDate = new Date(data.last_seen);
                  chatStatusHtml = `<span class="status-dot offline"></span>Last seen: ${lastSeenDate.toLocaleString('id-ID', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: 'short', year: 'numeric' })}`;
              } else {
                  chatStatusHtml = '<span class="status-dot online"></span>Online';
              }
              $('#chat-last-seen').html(chatStatusHtml);
          }
      } catch (error) {
          console.error('Error in handleStatusUpdate:', error);
      }
  }

  function setupModals() {
      $('#chatModal').modal({ backdrop: 'static', keyboard: false });
      $('#chat-toggle').on('click', function(e) {
          e.preventDefault();
          $('#chatModal').modal('show');
          updateFriendList();
      });
      $('#friend-request-toggle').on('click', function(e) {
          e.preventDefault();
          $('#friendRequestModal').modal('show');
      });

      $('#chatModal').on('shown.bs.modal', function() {
          updateFriendList();
      });

      $('#chatModal').on('hidden.bs.modal', function() {
          currentChatUserId = null;
          $('.chat-header, .chat-input').hide();
          $('#chat-messages').html('<h4 class="text-muted text-center mt-5"><i class="bi bi-fingerprint text-warning"></i><b> Malmanech</b><br/>Pilih teman untuk mulai mengobrol</h4>');
      });

      $('#friendRequestModal').on('hidden.bs.modal', function() {
          $('#friend-search').val('');
          $('#search-results').empty();
      });
  }

  try {
      connectWebSocket(userId, handleMessage, handleFriendRequest, handleFriendAccepted, handleReadMessage, handleStatusUpdate);
      setupModals();
      const chatData = setupChat(userId, currentChatUserId, totalUnread, friendIds, updateFriendList, updateChatBadge);
      currentChatUserId = chatData.currentChatUserId;
      totalUnread = chatData.totalUnread;
      const friendData = setupFriendRequests(userId, friendIds, pendingCount, updateFriendList, updateFriendRequestBadge);
      pendingCount = friendData.pendingCount;
      friendIds = friendData.friendIds;
      updateChatBadge();
      updateFriendRequestBadge();
  } catch (error) {
      console.error('Error during initialization:', error);
  }

  $(window).on('beforeunload', function() {
      cleanupWebSocket();
      $(document).off();
      $('.modal').modal('hide');
  });
});