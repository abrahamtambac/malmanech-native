// js/video_call.js
document.addEventListener('DOMContentLoaded', function() {
    const userId = window.userId;
    const classroomId = window.classroomId;
    const classroomMembers = window.classroomMembers;
    const memberNames = window.memberNames;
    const isLecturer = window.isLecturer;
    let ws = null;
    let localStream = null;
    let audioContext = null;
    let analyser = null;
    const peerConnections = {};
    let isCallActive = false;
    let participants = new Set();
    let screenStream = null;
    let currentScreenSharer = null;

    const configuration = {
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' }
        ]
    };

    function log(message) {
        console.log(`[VideoCall] ${message}`);
    }

    function waitForWebSocket() {
        return new Promise((resolve) => {
            if (ws && ws.readyState === WebSocket.OPEN) {
                resolve();
            } else {
                ws.onopen = () => {
                    log('WebSocket connected');
                    resolve();
                };
                ws.onerror = (error) => {
                    log(`WebSocket error during wait: ${error}`);
                };
            }
        });
    }

    function connectWebSocket() {
        const wsUrl = window.location.protocol === "https:" 
            ? `wss://${window.location.hostname}:8080` 
            : `ws://${window.location.hostname}:8080`;

        ws = new WebSocket(wsUrl);

        ws.onopen = function() {
            ws.send(JSON.stringify({ type: 'register', user_id: userId }));
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
                    updateParticipantList(data.user_id, true);
                    createPeerConnection(data.user_id, classroomId);
                }
                break;
            case 'participant_left':
                if (isCallActive) {
                    endPeerConnection(data.user_id);
                    updateParticipantList(data.user_id, false);
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

    function hideJoinToast() {
        const toast = new bootstrap.Toast(document.getElementById('joinToast'));
        toast.hide();
        log('Hiding join toast');
    }

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

            await waitForWebSocket(); // Tunggu hingga WebSocket terbuka
            ws.send(JSON.stringify({
                type: 'start_video_call',
                classroom_id: classroomId,
                user_id: userId
            }));
            log(`Video call started by User ${userId} in classroom ${classroomId}`);

            participants.add(userId);
            updateParticipantList(userId, true);

            classroomMembers.forEach(memberId => {
                if (memberId != userId) {
                    createPeerConnection(memberId, classroomId);
                }
            });

            $('#videoCallModal').modal('show');
            setupControls();
            isCallActive = true;
        } catch (err) {
            log(`Error starting video call: ${err.message}`);
            alert('Gagal memulai video call: ' + err.message);
        }
    }

    async function joinVideoCall(classroomId) {
        try {
            if (!localStream) {
                await populateDeviceSelects();
                localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                setupAudioVisualizer();
                addLocalVideo();
            }

            await waitForWebSocket(); // Tunggu hingga WebSocket terbuka
            ws.send(JSON.stringify({
                type: 'participant_joined',
                classroom_id: classroomId,
                user_id: userId
            }));
            log(`User ${userId} joined video call in classroom ${classroomId}`);

            participants.add(userId);
            updateParticipantList(userId, true);

            classroomMembers.forEach(memberId => {
                if (memberId != userId) {
                    createPeerConnection(memberId, classroomId);
                }
            });

            $('#videoCallModal').modal('show');
            setupControls();
            isCallActive = true;
            hideJoinToast();
        } catch (err) {
            log(`Error joining video call: ${err.message}`);
            alert('Gagal bergabung: ' + err.message);
        }
    }

    function addLocalVideo() {
        const localVideo = document.createElement('video');
        localVideo.id = `remoteVideo-${userId}`;
        localVideo.autoplay = true;
        localVideo.muted = true;
        localVideo.playsinline = true;
        localVideo.classList.add('remote-video');
        localVideo.srcObject = localStream;
        const container = document.createElement('div');
        container.classList.add('video-wrapper');
        container.innerHTML = `<span class="video-label">You (${memberNames[userId] || 'Me'})</span>`;
        container.appendChild(localVideo);
        document.getElementById('participant-videos').appendChild(container);
        log(`Local video added for User ${userId}`);
    }

    async function createPeerConnection(memberId, classroomId) {
        if (peerConnections[memberId]) return;

        const pc = new RTCPeerConnection(configuration);
        peerConnections[memberId] = pc;

        localStream.getTracks().forEach(track => pc.addTrack(track, localStream));
        if (screenStream) {
            screenStream.getTracks().forEach(track => pc.addTrack(track, screenStream));
        }

        pc.ontrack = (event) => {
            const stream = event.streams[0];
            const video = document.createElement('video');
            video.id = `remoteVideo-${memberId}`;
            video.autoplay = true;
            video.playsinline = true;
            video.classList.add('remote-video');
            video.srcObject = stream;

            if (stream.getVideoTracks().length > 0 && stream.getVideoTracks()[0].label.includes('screen')) {
                const screenShareVideo = document.getElementById('screen-share-video');
                screenShareVideo.srcObject = stream;
                document.getElementById('screen-share-label').textContent = `${memberNames[memberId] || 'Unknown'} (Screen)`;
                document.getElementById('screen-share-container').classList.remove('d-none');
                currentScreenSharer = memberId;
                log(`Screen share video added for User ${memberId}`);
            } else {
                const container = document.createElement('div');
                container.classList.add('video-wrapper');
                container.innerHTML = `<span class="video-label">${memberNames[memberId] || 'Unknown'}</span>`;
                container.appendChild(video);
                document.getElementById('participant-videos').appendChild(container);
                log(`Camera video added for User ${memberId}`);
            }
        };

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

        pc.onconnectionstatechange = () => {
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

    async function handleOffer(data) {
        if (data.user_id === userId) return;

        if (!peerConnections[data.user_id]) {
            const pc = new RTCPeerConnection(configuration);
            peerConnections[data.user_id] = pc;

            localStream.getTracks().forEach(track => pc.addTrack(track, localStream));
            if (screenStream) {
                screenStream.getTracks().forEach(track => pc.addTrack(track, screenStream));
            }

            pc.ontrack = (event) => {
                const stream = event.streams[0];
                const video = document.createElement('video');
                video.id = `remoteVideo-${data.user_id}`;
                video.autoplay = true;
                video.playsinline = true;
                video.classList.add('remote-video');
                video.srcObject = stream;

                if (stream.getVideoTracks().length > 0 && stream.getVideoTracks()[0].label.includes('screen')) {
                    const screenShareVideo = document.getElementById('screen-share-video');
                    screenShareVideo.srcObject = stream;
                    document.getElementById('screen-share-label').textContent = `${memberNames[data.user_id] || 'Unknown'} (Screen)`;
                    document.getElementById('screen-share-container').classList.remove('d-none');
                    currentScreenSharer = data.user_id;
                    log(`Screen share video added for User ${data.user_id}`);
                } else {
                    const container = document.createElement('div');
                    container.classList.add('video-wrapper');
                    container.innerHTML = `<span class="video-label">${memberNames[data.user_id] || 'Unknown'}</span>`;
                    container.appendChild(video);
                    document.getElementById('participant-videos').appendChild(container);
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

    async function handleAnswer(data) {
        if (peerConnections[data.user_id]) {
            try {
                await peerConnections[data.user_id].setRemoteDescription(new RTCSessionDescription(data.answer));
                log(`Answer received and set from User ${data.user_id}`);
            } catch (err) {
                log(`Error handling answer from User ${data.user_id}: ${err.message}`);
            }
        }
    }

    async function handleIceCandidate(data) {
        if (peerConnections[data.user_id]) {
            try {
                await peerConnections[data.user_id].addIceCandidate(new RTCIceCandidate(data.candidate));
                log(`ICE candidate added from User ${data.user_id}`);
            } catch (err) {
                log(`Error adding ICE candidate from User ${data.user_id}: ${err.message}`);
            }
        }
    }

    async function handleScreenShare(data) {
        if (data.user_id !== userId && peerConnections[data.user_id]) {
            log(`Screen share received from User ${data.user_id}`);
        }
    }

    function endPeerConnection(userId) {
        if (peerConnections[userId]) {
            peerConnections[userId].close();
            delete peerConnections[userId];
            const videoWrapper = document.querySelector(`#participant-videos .video-wrapper:has(#remoteVideo-${userId})`);
            if (videoWrapper) videoWrapper.remove();
            if (currentScreenSharer === userId) {
                document.getElementById('screen-share-container').classList.add('d-none');
                currentScreenSharer = null;
            }
            participants.delete(userId);
            updateParticipantList(userId, false);
            log(`Peer connection ended with User ${userId}`);
        }
    }

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

    function updateParticipantList(userId, isJoining) {
        if (isJoining) {
            participants.add(userId);
            log(`Participant ${userId} joined`);
        } else if (userId) {
            participants.delete(userId);
            log(`Participant ${userId} left`);
        }
        const participantList = document.getElementById('participant-list');
        participantList.innerHTML = '';
        participants.forEach(pid => {
            const li = document.createElement('li');
            li.textContent = memberNames[pid] || 'Unknown';
            participantList.appendChild(li);
        });
    }

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
                    screenTrack.onended = () => {
                        stopScreenSharing();
                    };
                    Object.values(peerConnections).forEach(pc => {
                        const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (sender) sender.replaceTrack(screenTrack);
                    });
                    ws.send(JSON.stringify({
                        type: 'screen_shared',
                        classroom_id: classroomId,
                        user_id: userId
                    }));
                    $(this).addClass('btn-success').removeClass('btn-outline-secondary');
                    const screenShareVideo = document.getElementById('screen-share-video');
                    screenShareVideo.srcObject = screenStream;
                    document.getElementById('screen-share-label').textContent = `${memberNames[userId] || 'Me'} (Screen)`;
                    document.getElementById('screen-share-container').classList.remove('d-none');
                    currentScreenSharer = userId;
                    log('Screen sharing started');
                } else {
                    stopScreenSharing();
                }
            } catch (err) {
                log(`Error sharing screen: ${err.message}`);
            }
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

    function stopScreenSharing() {
        if (screenStream) {
            screenStream.getTracks().forEach(track => track.stop());
            screenStream = null;
            $('#share-screen').removeClass('btn-success').addClass('btn-outline-secondary');
            const videoTrack = localStream.getVideoTracks()[0];
            Object.values(peerConnections).forEach(pc => {
                const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                if (sender) sender.replaceTrack(videoTrack);
            });
            if (currentScreenSharer === userId) {
                document.getElementById('screen-share-container').classList.add('d-none');
                currentScreenSharer = null;
            }
            log('Screen sharing stopped');
        }
    }

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

    $('#start-video-call').on('click', function() {
        const classroomId = $(this).data('classroom-id');
        startVideoCall(classroomId);
    });

    connectWebSocket();
});