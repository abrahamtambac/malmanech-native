// js/video_call.js
document.addEventListener('DOMContentLoaded', function() {
    // Variabel global
    const userId = window.userId;               // ID pengguna saat ini
    const classroomId = window.classroomId;     // ID kelas saat ini
    const classroomMembers = window.classroomMembers; // Daftar ID anggota kelas
    const memberNames = window.memberNames;     // Nama anggota kelas berdasarkan ID
    const isLecturer = window.isLecturer;       // Status apakah pengguna adalah dosen
    let ws = null;                              // Koneksi WebSocket
    let localStream = null;                     // Stream lokal (kamera/mikrofon pengguna)
    let audioContext = null;                    // Konteks audio untuk visualizer
    let analyser = null;                        // Analyser untuk visualisasi audio
    const peerConnections = {};                 // Objek untuk menyimpan koneksi peer
    let isCallActive = false;                   // Status apakah panggilan sedang aktif
    let participants = new Set();               // Set untuk melacak peserta aktif
    let screenStream = null;                    // Stream untuk screen sharing
    let currentScreenSharer = null;             // ID pengguna yang sedang berbagi layar
    let isScreenSharingFull = false;            // Status apakah screen sharing dalam mode fullscreen
    const iceCandidateQueue = {};               // Antrian untuk ICE candidates

    // Konfigurasi WebRTC dengan STUN server
    const configuration = {
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            // Tambahkan TURN server jika diperlukan:
            // { urls: 'turn:your-turn-server', username: 'user', credential: 'pass' }
        ]
    };

    // Fungsi untuk logging
    function log(message) {
        console.log(`[VideoCall] ${message}`);
    }

    // Fungsi untuk menghubungkan WebSocket
    function connectWebSocket() {
        const isLocalhost = window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1";
        const wsUrl = isLocalhost
            ? `${window.location.protocol === 'https:' ? 'wss' : 'ws'}://${window.location.hostname}:8080`
            : `${window.location.protocol === 'https:' ? 'wss' : 'ws'}://${window.location.hostname}/ws`;

        ws = new WebSocket(wsUrl);

        ws.onopen = function() {
            ws.send(JSON.stringify({ type: 'register', user_id: userId }));
            log('WebSocket connected');
            checkOngoingCall();
        };

        ws.onmessage = function(event) {
            const data = JSON.parse(event.data);
            log(`Received message: ${JSON.stringify(data)}`);
            handleWebSocketMessage(data);
        };

        ws.onclose = function() {
            log('WebSocket closed, reconnecting...');
            setTimeout(connectWebSocket, 1000);
        };

        ws.onerror = function(error) {
            log(`WebSocket error: ${error}`);
        };
    }

    // Fungsi untuk menangani pesan WebSocket
    function handleWebSocketMessage(data) {
        switch (data.type) {
            case 'video_call_started':
                if (!isLecturer && !isCallActive) {
                    showJoinToast(data.user_id);
                    isCallActive = true;
                }
                break;
            case 'offer':
                handleOffer(data);
                break;
            case 'answer':
                handleAnswer(data);
                break;
            case 'ice_candidate':
                handleIceCandidate(data);
                break;
            case 'video_call_ended':
                if (isCallActive) {
                    endVideoCall(classroomId);
                    hideJoinToast();
                }
                break;
            case 'participant_joined':
                if (isCallActive) {
                    participants.add(data.user_id);
                    updateParticipantList();
                    createPeerConnection(data.user_id, classroomId);
                    log(`Participant ${data.user_id} joined`);
                }
                break;
            case 'participant_left':
                if (isCallActive) {
                    endPeerConnection(data.user_id);
                    participants.delete(data.user_id);
                    updateParticipantList();
                    log(`Participant ${data.user_id} left`);
                }
                break;
            case 'screen_shared':
                handleScreenShare(data);
                break;
            case 'status_update':
                log(`Status update received for User ${data.user_id}: Last seen ${data.last_seen}`);
                break;
            case 'error':
                alert(data.message);
                log(`Error received: ${data.message}`);
                break;
            default:
                log(`Unknown message type: ${data.type}`);
        }
    }

    // Fungsi untuk menampilkan notifikasi join
    function showJoinToast(initiatorId) {
        const initiatorName = memberNames[initiatorId] || 'Dosen';
        const toastBody = document.querySelector('#joinToast .toast-body');
        toastBody.innerHTML = `${initiatorName} sedang dalam meeting class. <button class="btn btn-primary btn-sm ms-2" id="join-toast-btn">Join</button>`;
        const toast = new bootstrap.Toast(document.getElementById('joinToast'));
        toast.show();
        log(`Showing join toast for ${initiatorName}`);

        document.getElementById('join-toast-btn').onclick = function() {
            joinVideoCall(classroomId);
            toast.hide();
        };
    }

    // Fungsi untuk menyembunyikan notifikasi join
    function hideJoinToast() {
        const toast = new bootstrap.Toast(document.getElementById('joinToast'));
        toast.hide();
        log('Hiding join toast');
    }

    // Fungsi untuk memeriksa status panggilan yang sedang berlangsung
    function checkOngoingCall() {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type: 'check_call_status',
                classroom_id: classroomId,
                user_id: userId
            }));
            log('Checking ongoing call status');
        } else {
            log('WebSocket not open yet, cannot check call status');
        }
    }

    // Fungsi untuk mengisi dropdown perangkat kamera dan mikrofon
    async function populateDeviceSelects() {
        const devices = await navigator.mediaDevices.enumerateDevices();
        const cameraSelect = document.getElementById('cameraSelect');
        const micSelect = document.getElementById('micSelect');

        cameraSelect.innerHTML = '<option value="">Pilih Kamera</option>';
        micSelect.innerHTML = '<option value="">Pilih Mikrofon</option>';

        devices.forEach(device => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.text = device.label || `${device.kind} ${device.deviceId.slice(0, 5)}`;
            if (device.kind === 'videoinput') {
                cameraSelect.appendChild(option);
            } else if (device.kind === 'audioinput') {
                micSelect.appendChild(option);
            }
        });
        log('Device selects populated');
    }

    // Fungsi untuk memulai panggilan video (khusus dosen)
    async function startVideoCall(classroomId) {
        try {
            if (!isLecturer) {
                log('Only lecturers can start a video call');
                return;
            }
            await populateDeviceSelects();
            localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            setupAudioVisualizer();
            addLocalVideo();

            ws.send(JSON.stringify({
                type: 'start_video_call',
                classroom_id: classroomId,
                user_id: userId
            }));
            log(`Video call started by User ${userId} in classroom ${classroomId}`);

            participants.add(userId);
            updateParticipantList();

            classroomMembers.forEach(memberId => {
                if (memberId != userId) {
                    createPeerConnection(memberId, classroomId);
                }
            });

            $('#videoCallModal').modal('show');
            setupControls();
            makeVideosDraggable();
            isCallActive = true;
        } catch (err) {
            log(`Error starting video call: ${err.message}`);
            alert('Gagal memulai video call: ' + err.message);
        }
    }

    // Fungsi untuk bergabung ke panggilan video
    async function joinVideoCall(classroomId) {
        try {
            if (!localStream) {
                await populateDeviceSelects();
                localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                setupAudioVisualizer();
                addLocalVideo();
            }

            ws.send(JSON.stringify({
                type: 'participant_joined',
                classroom_id: classroomId,
                user_id: userId
            }));
            log(`User ${userId} joined video call in classroom ${classroomId}`);

            participants.add(userId);
            updateParticipantList();

            classroomMembers.forEach(memberId => {
                if (memberId != userId) {
                    createPeerConnection(memberId, classroomId);
                }
            });

            $('#videoCallModal').modal('show');
            setupControls();
            makeVideosDraggable();
            isCallActive = true;
            hideJoinToast();
        } catch (err) {
            log(`Error joining video call: ${err.message}`);
            alert('Gagal bergabung: ' + err.message);
        }
    }

    // Fungsi untuk menambahkan video lokal pengguna
    function addLocalVideo() {
        const localVideo = document.createElement('video');
        localVideo.id = `remoteVideo-${userId}`;
        localVideo.autoplay = true;
        localVideo.muted = true; // Mute lokal agar tidak ada echo
        localVideo.playsinline = true;
        localVideo.classList.add('remote-video');
        localVideo.srcObject = localStream;
        const container = document.createElement('div');
        container.classList.add('video-wrapper');
        container.style.position = 'absolute';
        container.innerHTML = `<span class="video-label">You (${memberNames[userId] || 'Me'})</span>`;
        container.appendChild(localVideo);
        document.getElementById('participant-videos').appendChild(container);
        log(`Local video added for User ${userId}`);
    }

    // Fungsi untuk membuat koneksi peer dengan anggota lain
    async function createPeerConnection(memberId, classroomId) {
        if (peerConnections[memberId]) {
            log(`Peer connection for User ${memberId} already exists`);
            return;
        }

        const pc = new RTCPeerConnection(configuration);
        peerConnections[memberId] = pc;

        // Inisialisasi antrian ICE candidates untuk peer ini
        iceCandidateQueue[memberId] = [];

        // Tambahkan semua track lokal ke koneksi peer
        if (localStream) {
            localStream.getTracks().forEach(track => {
                pc.addTrack(track, localStream);
                log(`Added track ${track.kind} to peer connection for User ${memberId}`);
            });
        }
        if (screenStream) {
            screenStream.getTracks().forEach(track => {
                pc.addTrack(track, screenStream);
                log(`Added screen share track to peer connection for User ${memberId}`);
            });
        }

        // Tangani stream masuk dari peer
        pc.ontrack = (event) => {
            const stream = event.streams[0];
            const existingVideo = document.getElementById(`remoteVideo-${memberId}`);
            if (existingVideo) {
                log(`Video for User ${memberId} already exists, skipping`);
                return;
            }

            const video = document.createElement('video');
            video.id = `remoteVideo-${memberId}`;
            video.autoplay = true;
            video.playsinline = true;
            video.classList.add('remote-video');
            video.srcObject = stream;

            if (stream.getVideoTracks().length > 0 && stream.getVideoTracks()[0].label.includes('screen')) {
                updateScreenShareUI(stream, memberId);
            } else {
                const container = document.createElement('div');
                container.classList.add('video-wrapper');
                container.style.position = 'absolute';
                container.innerHTML = `<span class="video-label">${memberNames[memberId] || 'Unknown'}</span>`;
                container.appendChild(video);
                document.getElementById('participant-videos').appendChild(container);
                makeVideosDraggable();
                log(`Camera video added for User ${memberId}`);
            }
        };

        // Kirim ICE candidate ke peer
        pc.onicecandidate = (event) => {
            if (event.candidate) {
                ws.send(JSON.stringify({
                    type: 'ice_candidate',
                    to_user_id: memberId,
                    candidate: event.candidate,
                    user_id: userId,
                    classroom_id: classroomId
                }));
                log(`ICE candidate sent to User ${memberId}`);
            }
        };

        // Tangani perubahan status koneksi
        pc.onconnectionstatechange = () => {
            log(`Peer connection state with User ${memberId}: ${pc.connectionState}`);
            if (pc.connectionState === 'disconnected' || pc.connectionState === 'failed') {
                endPeerConnection(memberId);
                ws.send(JSON.stringify({
                    type: 'participant_left',
                    classroom_id: classroomId,
                    user_id: memberId
                }));
                log(`Peer connection with User ${memberId} disconnected`);
            }
        };

        // Kirim offer ke peer
        try {
            const offer = await pc.createOffer();
            await pc.setLocalDescription(offer);
            ws.send(JSON.stringify({
                type: 'offer',
                to_user_id: memberId,
                offer: offer,
                user_id: userId,
                classroom_id: classroomId
            }));
            log(`Offer sent to User ${memberId}`);
        } catch (err) {
            log(`Error creating offer for User ${memberId}: ${err.message}`);
        }
    }

    // Fungsi untuk menangani offer dari peer lain
    async function handleOffer(data) {
        if (data.user_id === userId) return;

        if (!peerConnections[data.user_id]) {
            const pc = new RTCPeerConnection(configuration);
            peerConnections[data.user_id] = pc;

            // Inisialisasi antrian ICE candidates untuk peer ini
            iceCandidateQueue[data.user_id] = [];

            if (localStream) {
                localStream.getTracks().forEach(track => {
                    pc.addTrack(track, localStream);
                    log(`Added track ${track.kind} to peer connection for User ${data.user_id}`);
                });
            }
            if (screenStream) {
                screenStream.getTracks().forEach(track => {
                    pc.addTrack(track, screenStream);
                    log(`Added screen share track to peer connection for User ${data.user_id}`);
                });
            }

            pc.ontrack = (event) => {
                const stream = event.streams[0];
                const existingVideo = document.getElementById(`remoteVideo-${data.user_id}`);
                if (existingVideo) {
                    log(`Video for User ${data.user_id} already exists, skipping`);
                    return;
                }

                const video = document.createElement('video');
                video.id = `remoteVideo-${data.user_id}`;
                video.autoplay = true;
                video.playsinline = true;
                video.classList.add('remote-video');
                video.srcObject = stream;

                if (stream.getVideoTracks().length > 0 && stream.getVideoTracks()[0].label.includes('screen')) {
                    updateScreenShareUI(stream, data.user_id);
                } else {
                    const container = document.createElement('div');
                    container.classList.add('video-wrapper');
                    container.style.position = 'absolute';
                    container.innerHTML = `<span class="video-label">${memberNames[data.user_id] || 'Unknown'}</span>`;
                    container.appendChild(video);
                    document.getElementById('participant-videos').appendChild(container);
                    makeVideosDraggable();
                    log(`Camera video added for User ${data.user_id}`);
                }
            };

            pc.onicecandidate = (event) => {
                if (event.candidate) {
                    ws.send(JSON.stringify({
                        type: 'ice_candidate',
                        to_user_id: data.user_id,
                        candidate: event.candidate,
                        user_id: userId,
                        classroom_id: data.classroom_id
                    }));
                    log(`ICE candidate sent to User ${data.user_id}`);
                }
            };

            pc.onconnectionstatechange = () => {
                log(`Peer connection state with User ${data.user_id}: ${pc.connectionState}`);
                if (pc.connectionState === 'disconnected' || pc.connectionState === 'failed') {
                    endPeerConnection(data.user_id);
                    ws.send(JSON.stringify({
                        type: 'participant_left',
                        classroom_id: classroomId,
                        user_id: data.user_id
                    }));
                    log(`Peer connection with User ${data.user_id} disconnected`);
                }
            };

            try {
                await pc.setRemoteDescription(new RTCSessionDescription(data.offer));
                log(`Remote description set for User ${data.user_id}`);

                // Proses ICE candidates yang ada di antrian
                if (iceCandidateQueue[data.user_id] && iceCandidateQueue[data.user_id].length > 0) {
                    for (const candidate of iceCandidateQueue[data.user_id]) {
                        await pc.addIceCandidate(new RTCIceCandidate(candidate));
                        log(`Processed queued ICE candidate for User ${data.user_id}`);
                    }
                    iceCandidateQueue[data.user_id] = [];
                }

                const answer = await pc.createAnswer();
                await pc.setLocalDescription(answer);
                ws.send(JSON.stringify({
                    type: 'answer',
                    to_user_id: data.user_id,
                    answer: answer,
                    user_id: userId,
                    classroom_id: data.classroom_id
                }));
                log(`Answer sent to User ${data.user_id}`);
            } catch (err) {
                log(`Error handling offer from User ${data.user_id}: ${err.message}`);
            }
        }
    }

    // Fungsi untuk menangani jawaban dari peer lain
    async function handleAnswer(data) {
        if (peerConnections[data.user_id]) {
            try {
                const pc = peerConnections[data.user_id];
                await pc.setRemoteDescription(new RTCSessionDescription(data.answer));
                log(`Answer received and set from User ${data.user_id}`);

                // Proses ICE candidates yang ada di antrian
                if (iceCandidateQueue[data.user_id] && iceCandidateQueue[data.user_id].length > 0) {
                    for (const candidate of iceCandidateQueue[data.user_id]) {
                        await pc.addIceCandidate(new RTCIceCandidate(candidate));
                        log(`Processed queued ICE candidate for User ${data.user_id}`);
                    }
                    iceCandidateQueue[data.user_id] = [];
                }
            } catch (err) {
                log(`Error handling answer from User ${data.user_id}: ${err.message}`);
            }
        }
    }

    // Fungsi untuk menangani ICE candidate dari peer lain
    async function handleIceCandidate(data) {
        if (peerConnections[data.user_id]) {
            const pc = peerConnections[data.user_id];
            if (pc.remoteDescription) {
                try {
                    await pc.addIceCandidate(new RTCIceCandidate(data.candidate));
                    log(`ICE candidate added from User ${data.user_id}`);
                } catch (err) {
                    log(`Error adding ICE candidate from User ${data.user_id}: ${err.message}`);
                }
            } else {
                // Jika remoteDescription belum diatur, masukkan ke antrian
                iceCandidateQueue[data.user_id].push(data.candidate);
                log(`ICE candidate queued for User ${data.user_id} because remote description is not set`);
            }
        }
    }

    // Fungsi untuk menangani pemberitahuan screen sharing
    async function handleScreenShare(data) {
        if (data.user_id !== userId && peerConnections[data.user_id]) {
            log(`Screen share received from User ${data.user_id}`);
            // Ditangani oleh ontrack event
        }
    }

    // Fungsi untuk memperbarui UI saat screen sharing aktif
    function updateScreenShareUI(stream, memberId) {
        const screenShareVideo = document.getElementById('screen-share-video');
        screenShareVideo.srcObject = stream;
        document.getElementById('screen-share-label').textContent = `${memberNames[memberId] || 'Unknown'} (Screen)`;
        document.getElementById('screen-share-container').classList.remove('d-none');
        currentScreenSharer = memberId;

        if (isScreenSharingFull) {
            toggleFullScreenShare(true);
        }
        log(`Screen share updated for User ${memberId}`);
    }

    // Fungsi untuk mengaktifkan/menonaktifkan mode fullscreen screen sharing
    function toggleFullScreenShare(forceFull = false) {
        const screenContainer = document.getElementById('screen-share-container');
        const participantVideos = document.getElementById('participant-videos');

        if (forceFull || !isScreenSharingFull) {
            screenContainer.style.height = '100%';
            screenContainer.style.width = '100%';
            screenContainer.classList.add('fullscreen');
            participantVideos.classList.add('d-none');
            isScreenSharingFull = true;
        } else {
            screenContainer.style.height = '50%';
            screenContainer.style.width = '100%';
            screenContainer.classList.remove('fullscreen');
            participantVideos.classList.remove('d-none');
            isScreenSharingFull = false;
        }
    }

    // Fungsi untuk mengakhiri koneksi peer dengan pengguna tertentu
    function endPeerConnection(userId) {
        if (peerConnections[userId]) {
            peerConnections[userId].close();
            delete peerConnections[userId];
            delete iceCandidateQueue[userId];
            const videoWrapper = document.querySelector(`#participant-videos .video-wrapper:has(#remoteVideo-${userId})`);
            if (videoWrapper) videoWrapper.remove();
            if (currentScreenSharer === userId) {
                document.getElementById('screen-share-container').classList.add('d-none');
                currentScreenSharer = null;
                isScreenSharingFull = false;
            }
            participants.delete(userId);
            updateParticipantList();
            log(`Peer connection ended with User ${userId}`);
        }
    }

    // Fungsi untuk mengakhiri seluruh panggilan video
    function endVideoCall(classroomId) {
        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
            localStream = null;
        }
        if (screenStream) {
            screenStream.getTracks().forEach(track => track.stop());
            screenStream = null;
        }
        if (audioContext) {
            audioContext.close();
            audioContext = null;
        }
        Object.keys(peerConnections).forEach(userId => {
            peerConnections[userId].close();
            delete peerConnections[userId];
            delete iceCandidateQueue[userId];
        });
        document.getElementById('participant-videos').innerHTML = '';
        document.getElementById('screen-share-container').classList.add('d-none');
        participants.clear();
        updateParticipantList();
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type: 'video_call_ended',
                classroom_id: classroomId,
                user_id: userId
            }));
            log(`Video call ended by User ${userId} in classroom ${classroomId}`);
        }
        $('#videoCallModal').modal('hide');
        $('#video-controls').empty();
        isCallActive = false;
        hideJoinToast();
    }

    // Fungsi untuk memperbarui daftar peserta di sidebar
    function updateParticipantList() {
        const participantList = document.getElementById('participant-list');
        participantList.innerHTML = '';
        participants.forEach(pid => {
            const li = document.createElement('li');
            li.textContent = memberNames[pid] || 'Unknown';
            participantList.appendChild(li);
        });
        log('Participant list updated');
    }

    // Fungsi untuk membuat video bisa di-drag
    function makeVideosDraggable() {
        interact('.video-wrapper').draggable({
            inertia: true,
            modifiers: [
                interact.modifiers.restrictRect({
                    restriction: '#video-container',
                    endOnly: true
                })
            ],
            listeners: {
                move(event) {
                    const target = event.target;
                    const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                    const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

                    target.style.transform = `translate(${x}px, ${y}px)`;
                    target.setAttribute('data-x', x);
                    target.setAttribute('data-y', y);
                }
            }
        });
    }

    // Fungsi untuk mengatur kontrol video (mute, video off, screen share, dll.)
    function setupControls() {
        $('#mute-audio').on('click', function() {
            const audioTrack = localStream.getAudioTracks()[0];
            if (audioTrack.enabled) {
                audioTrack.enabled = false;
                $(this).html('<i class="bi bi-mic-mute"></i>').addClass('btn-danger').removeClass('btn-outline-secondary');
                log('Audio muted');
            } else {
                audioTrack.enabled = true;
                $(this).html('<i class="bi bi-mic"></i>').removeClass('btn-danger').addClass('btn-outline-secondary');
                log('Audio unmuted');
            }
        });

        $('#disable-video').on('click', function() {
            const videoTrack = localStream.getVideoTracks()[0];
            if (videoTrack.enabled) {
                videoTrack.enabled = false;
                $(this).html('<i class="bi bi-camera-video-off"></i>').addClass('btn-danger').removeClass('btn-outline-secondary');
                log('Video disabled');
            } else {
                videoTrack.enabled = true;
                $(this).html('<i class="bi bi-camera-video"></i>').removeClass('btn-danger').addClass('btn-outline-secondary');
                log('Video enabled');
            }
        });

        $('#share-screen').on('click', async function() {
            try {
                if (!screenStream) {
                    screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                    const screenTrack = screenStream.getVideoTracks()[0];
                    screenTrack.onended = () => stopScreenSharing();
                    Object.values(peerConnections).forEach(pc => {
                        const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (sender) {
                            sender.replaceTrack(screenTrack);
                            log(`Replaced video track with screen share for peer ${pc}`);
                        } else {
                            pc.addTrack(screenTrack, screenStream);
                            log(`Added screen share track to peer ${pc}`);
                        }
                    });
                    ws.send(JSON.stringify({
                        type: 'screen_shared',
                        classroom_id: classroomId,
                        user_id: userId
                    }));
                    $(this).addClass('btn-success').removeClass('btn-outline-secondary');
                    updateScreenShareUI(screenStream, userId);
                } else {
                    stopScreenSharing();
                }
            } catch (err) {
                log(`Error sharing screen: ${err.message}`);
            }
        });

        $('#screen-share-video').on('dblclick', function() {
            toggleFullScreenShare();
        });

        $('#cameraSelect').on('change', async function() {
            const deviceId = this.value;
            if (deviceId && localStream) {
                const newStream = await navigator.mediaDevices.getUserMedia({ video: { deviceId }, audio: true });
                const videoTrack = newStream.getVideoTracks()[0];
                localStream.getVideoTracks().forEach(track => track.stop());
                localStream.removeTrack(localStream.getVideoTracks()[0]);
                localStream.addTrack(videoTrack);
                document.getElementById(`remoteVideo-${userId}`).srcObject = localStream;
                Object.values(peerConnections).forEach(pc => {
                    const sender = pc.getSenders().find(s => s.track.kind === 'video');
                    if (sender) sender.replaceTrack(videoTrack);
                });
                log(`Camera changed to device ID: ${deviceId}`);
            }
        });

        $('#micSelect').on('change', async function() {
            const deviceId = this.value;
            if (deviceId && localStream) {
                const newStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: { deviceId } });
                const audioTrack = newStream.getAudioTracks()[0];
                localStream.getAudioTracks().forEach(track => track.stop());
                localStream.removeTrack(localStream.getAudioTracks()[0]);
                localStream.addTrack(audioTrack);
                document.getElementById(`remoteVideo-${userId}`).srcObject = localStream;
                Object.values(peerConnections).forEach(pc => {
                    const sender = pc.getSenders().find(s => s.track.kind === 'audio');
                    if (sender) sender.replaceTrack(audioTrack);
                });
                setupAudioVisualizer();
                log(`Microphone changed to device ID: ${deviceId}`);
            }
        });

        $('#end-video-call-btn').on('click', function() {
            endVideoCall(classroomId);
        });
    }

    // Fungsi untuk menghentikan screen sharing
    function stopScreenSharing() {
        if (screenStream) {
            screenStream.getTracks().forEach(track => track.stop());
            screenStream = null;
            $('#share-screen').removeClass('btn-success').addClass('btn-outline-secondary');
            const videoTrack = localStream.getVideoTracks()[0];
            Object.values(peerConnections).forEach(pc => {
                const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                if (sender) {
                    sender.replaceTrack(videoTrack);
                    log(`Reverted to camera track for peer ${pc}`);
                }
            });
            if (currentScreenSharer === userId) {
                document.getElementById('screen-share-container').classList.add('d-none');
                currentScreenSharer = null;
                isScreenSharingFull = false;
            }
            log('Screen sharing stopped');
        }
    }

    // Fungsi untuk mengatur visualisasi audio
    function setupAudioVisualizer() {
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioContext.createAnalyser();
            analyser.fftSize = 256;
            const source = audioContext.createMediaStreamSource(localStream);
            source.connect(analyser);

            const canvas = document.getElementById('audioVisualizer');
            const canvasCtx = canvas.getContext('2d');
            const bufferLength = analyser.frequencyBinCount;
            const dataArray = new Uint8Array(bufferLength);

            function draw() {
                requestAnimationFrame(draw);
                analyser.getByteFrequencyData(dataArray);
                canvasCtx.fillStyle = '#f0f2f5';
                canvasCtx.fillRect(0, 0, canvas.width, canvas.height);

                const barWidth = (canvas.width / bufferLength) * 2.5;
                let barHeight;
                let x = 0;

                for (let i = 0; i < bufferLength; i++) {
                    barHeight = dataArray[i] / 2;
                    canvasCtx.fillStyle = `rgb(${barHeight + 100}, 50, 50)`;
                    canvasCtx.fillRect(x, canvas.height - barHeight, barWidth, barHeight);
                    x += barWidth + 1;
                }
            }
            draw();
            log('Audio visualizer set up');
        }
    }

    // Event listener untuk tombol start dan join
    $('#start-video-call').on('click', function() {
        const classroomId = $(this).data('classroom-id');
        startVideoCall(classroomId);
    });

    $('#join-meeting-btn').on('click', function() {
        joinVideoCall(classroomId);
        document.getElementById('meeting-notification').classList.add('d-none');
    });

    // Mulai koneksi WebSocket saat halaman dimuat
    connectWebSocket();
});