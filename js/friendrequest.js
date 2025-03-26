// friendRequests.js

function setupFriendRequests(userId, friendIds, pendingCount, updateFriendList, updateFriendRequestBadge) {
  $('#friend-search').on('input', debounce(function() {
      const query = $(this).val();
      if (query.length < 2) {
          $('#search-results').empty();
          return;
      }

      $.ajax({
          url: 'index.php?page=chat&action=search_users',
          type: 'GET',
          data: { query: query },
          dataType: 'json',
          success: function(data) {
              $('#search-results').empty();
              if (data.length > 0) {
                  data.forEach(function(user) {
                      if (user.id != userId) {
                          const isFriend = friendIds.includes(user.id);
                          $('#search-results').append(`
                              <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                                  <div class="d-flex align-items-center">
                                      <img src="${user.profile_image ? './upload/image/' + user.profile_image : './image/robot-ai.png'}" 
                                           class="rounded-circle me-2" style="width: 40px; height: 40px;">
                                      <span>${user.name}</span>
                                  </div>
                                  ${isFriend ? 
                                      `<button class="btn btn-sm btn-danger delete-friend-btn" data-user-id="${user.id}">Hapus Teman</button>` : 
                                      `<button class="btn btn-sm btn-primary send-friend-request" data-user-id="${user.id}">Tambah Teman</button>`}
                              </div>
                          `);
                      }
                  });
              } else {
                  $('#search-results').html('<p class="text-muted">Tidak ada pengguna ditemukan</p>');
              }
          },
          error: function(xhr, status, error) {
              console.error('Error searching users:', error);
              $('#search-results').html('<p class="text-danger">Gagal mencari pengguna. Silakan coba lagi.</p>');
          }
      });
  }, 300));

  $('#search-btn').on('click', function() {
      const query = $('#friend-search').val();
      if (query.length < 2) {
          $('#search-results').empty();
          return;
      }
      $.ajax({
          url: 'index.php?page=chat&action=search_users',
          type: 'GET',
          data: { query: query },
          dataType: 'json',
          success: function(data) {
              $('#search-results').empty();
              if (data.length > 0) {
                  data.forEach(function(user) {
                      if (user.id != userId) {
                          const isFriend = friendIds.includes(user.id);
                          $('#search-results').append(`
                              <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                                  <div class="d-flex align-items-center">
                                      <img src="${user.profile_image ? './upload/image/' + user.profile_image : './image/robot-ai.png'}" 
                                           class="rounded-circle me-2" style="width: 40px; height: 40px;">
                                      <span>${user.name}</span>
                                  </div>
                                  ${isFriend ? 
                                      `<button class="btn btn-sm btn-danger delete-friend-btn" data-user-id="${user.id}">Hapus Teman</button>` : 
                                      `<button class="btn btn-sm btn-primary send-friend-request" data-user-id="${user.id}">Tambah Teman</button>`}
                              </div>
                          `);
                      }
                  });
              } else {
                  $('#search-results').html('<p class="text-muted">Tidak ada pengguna ditemukan</p>');
              }
          },
          error: function(xhr, status, error) {
              console.error('Error searching users:', error);
              $('#search-results').html('<p class="text-danger">Gagal mencari pengguna. Silakan coba lagi.</p>');
          }
      });
  });

  $(document).on('click', '.accept-friend-btn', function() {
      const friendId = $(this).data('user-id');
      const friendName = $(this).data('user-name');
      $.ajax({
          url: 'index.php?page=chat&action=accept_friend',
          type: 'POST',
          data: { friend_id: friendId },
          dataType: 'json',
          success: function(response) {
              if (response.success) {
                  $(`button[data-user-id="${friendId}"]`).closest('.d-flex').remove();
                  if ($('#pending-requests').children().length === 1) {
                      $('#pending-requests').html('<p class="text-muted">Tidak ada permintaan tertunda</p>');
                  }
                  $('#friend-notification').show().addClass('show');
                  $('#accepted-friend-name').text(friendName);
                  setTimeout(() => $('#friend-notification').removeClass('show').hide(), 5000);
                  const ws = getWebSocket();
                  if (ws && ws.readyState === WebSocket.OPEN) {
                      ws.send(JSON.stringify({
                          type: 'friend_accepted',
                          user_id: userId,
                          friend_id: friendId,
                          friend_name: friendName,
                          profile_image: '<?php echo $profileImage; ?>'
                      }));
                  }
                  pendingCount--;
                  updateFriendRequestBadge();
                  updateFriendList();
              } else {
                  alert(response.message);
              }
          },
          error: function(xhr, status, error) {
              console.error('Error accepting friend request:', error);
              alert('Gagal menerima permintaan teman. Silakan coba lagi.');
          }
      });
  });

  $(document).on('click', '.send-friend-request', function() {
      const friendId = $(this).data('user-id');
      const $button = $(this);
      $.ajax({
          url: 'index.php?page=chat&action=add_friend',
          type: 'POST',
          data: { friend_id: friendId },
          dataType: 'json',
          beforeSend: function() {
              $button.prop('disabled', true).text('Mengirim...');
          },
          success: function(response) {
              if (response.success) {
                  $button.text('Menunggu Konfirmasi').removeClass('btn-primary').addClass('btn-secondary');
                  const ws = getWebSocket();
                  if (ws && ws.readyState === WebSocket.OPEN) {
                      ws.send(JSON.stringify({
                          type: 'friend_request',
                          user_id: userId,
                          friend_id: friendId,
                          friend_name: '<?php echo htmlspecialchars($currentUser['name']); ?>',
                          profile_image: '<?php echo $profileImage; ?>'
                      }));
                  }
                  alert('Permintaan teman berhasil dikirim!');
              } else {
                  alert(response.message);
                  $button.prop('disabled', false).text('Tambah Teman');
              }
          },
          error: function(xhr, status, error) {
              console.error('Error sending friend request:', error);
              alert('Gagal mengirim permintaan teman. Silakan coba lagi.');
              $button.prop('disabled', false).text('Tambah Teman');
          }
      });
  });

  $(document).on('click', '.delete-friend-btn', function() {
      const friendId = $(this).data('user-id');
      if (confirm('Apakah Anda yakin ingin menghapus teman ini?')) {
          $.ajax({
              url: 'index.php?page=chat&action=delete_friend',
              type: 'POST',
              data: { friend_id: friendId },
              dataType: 'json',
              success: function(response) {
                  if (response.success) {
                      $(`button[data-user-id="${friendId}"]`).closest('.d-flex').remove();
                      $(`.chat-friend[data-user-id="${friendId}"]`).remove();
                      friendIds.splice(friendIds.indexOf(friendId), 1);
                      if (currentChatUserId == friendId) {
                          currentChatUserId = null;
                          $('.chat-header, .chat-input').hide();
                          $('#chat-messages').html('<h4 class="text-muted text-center mt-5"><i class="bi bi-fingerprint text-warning"></i><b> Malmanech</b><br/>Pilih teman untuk mulai mengobrol</h4>');
                      }
                      alert('Teman berhasil dihapus');
                      updateFriendList();
                  } else {
                      alert(response.message);
                  }
              },
              error: function(xhr, status, error) {
                  console.error('Error deleting friend:', error);
                  alert('Gagal menghapus teman. Silakan coba lagi.');
              }
          });
      }
  });

  return { pendingCount, friendIds };
}