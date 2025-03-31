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
    const iceCandidateQueue = {};
    let activeSpeaker = null;

    const configuration = {
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'turn:165.22.176.111:3478', username: 'testuser', credential: 'testpass' }
        ]
    };

    const videoConstraints = {
        width: { ideal: 640 },
        height: { ideal: 480 },
        frameRate: { ideal: 15 },
        advanced: [
            { width: 640, height: 480 },
            { aspectRatio: 4/3 }
        ]
    };

    function log(message) {
        console.log(`[VideoCall] ${message}`);
    }

    function connectWebSocket() {
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
            alert('Gagal terhubung ke server video call. Pastikan server aktif.');
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
                    participants.add(data.user_id);
                    updateParticipantList();
                    createPeerConnection(data.user_id, classroomId);
                }
                break;
            case 'participant_left':
                if (isCallActive) {
                    endPeerConnection(data.user_id);
                    participants.delete(data.user_id);
                    updateParticipantList();
                }
                break;
            case 'screen_shared':
                handleScreenShare(data);
                break;
            case 'screen_share_stopped':
                if (data.user_id !== userId && currentScreenSharer === data.user_id) {
                    document.getElementById('screen-share-container').classList.add('d-none');
                    currentScreenSharer = null;
                    setParticipantsView();
                }
                break;
            case 'speaker_activity':
                updateActiveSpeaker(data.user_id, data.volume);
                break;
        }
    }

    function showJoinToast(initiatorId) {
        const initiatorName = memberNames[initiatorId] || 'Dosen';
        const toastBody = document.querySelector('#joinToast .toast-body');
        toastBody.innerHTML = `${initiatorName} sedang dalam meeting class. <button class="btn btn-primary btn-sm ms-2" id="join-toast-btn">Join</button>`;
        const toast = new bootstrap.Toast(document.getElementById('joinToast'));
        toast.show();

        document.getElementById('join-toast-btn').onclick = function() {
            joinVideoCall(classroomId);
            toast.hide();
        };
    }

    function hideJoinToast() {
        const toast = new bootstrap.Toast(document.getElementById('joinToast'));
        toast.hide();
    }

    function checkOngoingCall() {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type: 'check_call_status',
                classroom_id: classroomId,
                user_id: userId
            }));
            log('Checking ongoing call status');
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
            if (device.kind === 'videoinput') cameraSelect.appendChild(option);
            else if (device.kind === 'audioinput') micSelect.appendChild(option);
        });
        log('Device selects populated');
    }

    async function startVideoCall(classroomId) {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Browser Anda tidak mendukung WebRTC.');
            return;
        }

        try {
            if (!isLecturer) {
                log('Only lecturers can start a video call');
                return;
            }

            const response = await fetch(`index.php?page=classroom&action=can_start_video_call&classroom_id=${classroomId}`);
            const result = await response.json();
            if (!result.success) {
                alert('Hanya lecturer yang bisa memulai video call.');
                return;
            }

            await populateDeviceSelects();
            localStream = await navigator.mediaDevices.getUserMedia({
                video: videoConstraints,
                audio: { echoCancellation: true, noiseSuppression: true }
            });
            applyCompression(localStream);
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
                if (memberId != userId) createPeerConnection(memberId, classroomId);
            });

            $('#videoCallModal').modal('show');
            $('#loading-overlay').removeClass('hidden');
            setTimeout(() => $('#loading-overlay').addClass('hidden'), 2000);
            setupControls();
            makeVideosDraggable();
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
                localStream = await navigator.mediaDevices.getUserMedia({
                    video: videoConstraints,
                    audio: { echoCancellation: true, noiseSuppression: true }
                });
                applyCompression(localStream);
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
                if (memberId != userId) createPeerConnection(memberId, classroomId);
            });

            $('#videoCallModal').modal('show');
            $('#loading-overlay').removeClass('hidden');
            setTimeout(() => $('#loading-overlay').addClass('hidden'), 2000);
            setupControls();
            makeVideosDraggable();
            isCallActive = true;
            hideJoinToast();
        } catch (err) {
            log(`Error joining video call: ${err.message}`);
            alert('Gagal bergabung: ' + err.message);
        }
    }

    function applyCompression(stream) {
        stream.getVideoTracks().forEach(track => {
            track.applyConstraints({
                width: 640,
                height: 480,
                frameRate: 15,
                bitrate: 500000 // 500kbps untuk kompresi
            });
            log(`Applied compression to video track: ${track.id}`);
        });
    }

    function addLocalVideo() {
        const existingVideo = document.getElementById(`remoteVideo-${userId}`);
        if (existingVideo) {
            log(`Local video for User ${userId} already exists`);
            return;
        }

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
        if (peerConnections[memberId]) {
            log(`Peer connection for User ${memberId} already exists`);
            return;
        }

        const pc = new RTCPeerConnection(configuration);
        peerConnections[memberId] = pc;
        iceCandidateQueue[memberId] = [];

        if (!localStream) {
            log(`Local stream not available for User ${memberId}, fetching...`);
            localStream = await navigator.mediaDevices.getUserMedia({
                video: videoConstraints,
                audio: { echoCancellation: true, noiseSuppression: true }
            });
            applyCompression(localStream);
            addLocalVideo();
        }

        localStream.getTracks().forEach(track => {
            pc.addTrack(track, localStream);
            log(`Added track ${track.kind} to peer connection for User ${memberId}`);
        });
        if (screenStream) {
            screenStream.getTracks().forEach(track => {
                pc.addTrack(track, screenStream);
                log(`Added screen track ${track.kind} to peer connection for User ${memberId}`);
            });
        }

        pc.ontrack = (event) => {
            const stream = event.streams[0];
            const existingVideo = document.getElementById(`remoteVideo-${memberId}`);
            if (existingVideo) {
                log(`Video for User ${memberId} already exists in UI`);
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
                log(`Screen share video added for User ${memberId}`);
            } else {
                const container = document.createElement('div');
                container.classList.add('video-wrapper');
                container.innerHTML = `<span class="video-label">${memberNames[memberId] || 'Unknown'}</span>`;
                container.appendChild(video);
                document.getElementById('participant-videos').appendChild(container);
                makeVideosDraggable();
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
                log(`Sent ICE candidate to User ${memberId}`);
            }
        };

        pc.onconnectionstatechange = () => {
            log(`Connection state for User ${memberId}: ${pc.connectionState}`);
            if (pc.connectionState === 'connected') {
                log(`Connected to User ${memberId}`);
            } else if (pc.connectionState === 'failed' || pc.connectionState === 'disconnected') {
                log(`Connection failed/disconnected for User ${memberId}, restarting ICE`);
                pc.restartIce();
                setTimeout(() => {
                    if (pc.connectionState !== 'connected') {
                        log(`Retrying peer connection for User ${memberId}`);
                        endPeerConnection(memberId);
                        createPeerConnection(memberId, classroomId);
                    }
                }, 2000);
            }
        };

        try {
            const offer = await pc.createOffer({
                offerToReceiveAudio: true,
                offerToReceiveVideo: true
            });
            await pc.setLocalDescription(offer);
            const desc = pc.localDescription;
            desc.sdp = desc.sdp.replace(/a=mid:video\r\n/g, 'a=mid:video\r\nb=AS:500\r\n');
            await pc.setLocalDescription(desc);
            ws.send(JSON.stringify({
                type: 'offer',
                to_user_id: memberId,
                offer: desc,
                user_id: userId,
                classroom_id: classroomId
            }));
            log(`Sent offer to User ${memberId}`);
        } catch (err) {
            log(`Error creating offer for User ${memberId}: ${err.message}`);
        }
    }

    async function handleOffer(data) {
        if (data.user_id === userId) return;

        let pc = peerConnections[data.user_id];
        if (!pc) {
            pc = new RTCPeerConnection(configuration);
            peerConnections[data.user_id] = pc;
            iceCandidateQueue[data.user_id] = [];

            if (localStream) {
                localStream.getTracks().forEach(track => {
                    pc.addTrack(track, localStream);
                    log(`Added local track ${track.kind} to peer connection for User ${data.user_id}`);
                });
            }
            if (screenStream) {
                screenStream.getTracks().forEach(track => {
                    pc.addTrack(track, screenStream);
                    log(`Added screen track ${track.kind} to peer connection for User ${data.user_id}`);
                });
            }

            pc.ontrack = (event) => {
                const stream = event.streams[0];
                const existingVideo = document.getElementById(`remoteVideo-${data.user_id}`);
                if (existingVideo) {
                    log(`Video for User ${data.user_id} already exists in UI`);
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
                    log(`Screen share video added for User ${data.user_id}`);
                } else {
                    const container = document.createElement('div');
                    container.classList.add('video-wrapper');
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
                    log(`Sent ICE candidate to User ${data.user_id}`);
                }
            };

            pc.onconnectionstatechange = () => {
                log(`Connection state for User ${data.user_id}: ${pc.connectionState}`);
                if (pc.connectionState === 'failed' || pc.connectionState === 'disconnected') {
                    log(`Connection failed/disconnected for User ${data.user_id}, restarting ICE`);
                    pc.restartIce();
                    setTimeout(() => {
                        if (pc.connectionState !== 'connected') {
                            log(`Retrying peer connection for User ${data.user_id}`);
                            endPeerConnection(data.user_id);
                            createPeerConnection(data.user_id, classroomId);
                        }
                    }, 2000);
                }
            };
        }

        try {
            await pc.setRemoteDescription(new RTCSessionDescription(data.offer));
            log(`Set remote description (offer) from User ${data.user_id}`);
            if (iceCandidateQueue[data.user_id].length > 0) {
                for (const candidate of iceCandidateQueue[data.user_id]) {
                    await pc.addIceCandidate(new RTCIceCandidate(candidate));
                    log(`Added queued ICE candidate from User ${data.user_id}`);
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
            log(`Sent answer to User ${data.user_id}`);
        } catch (err) {
            log(`Error handling offer from User ${data.user_id}: ${err.message}`);
        }
    }

    async function handleAnswer(data) {
        if (peerConnections[data.user_id]) {
            const pc = peerConnections[data.user_id];
            try {
                await pc.setRemoteDescription(new RTCSessionDescription(data.answer));
                log(`Set remote description (answer) from User ${data.user_id}`);
                if (iceCandidateQueue[data.user_id].length > 0) {
                    for (const candidate of iceCandidateQueue[data.user_id]) {
                        await pc.addIceCandidate(new RTCIceCandidate(candidate));
                        log(`Added queued ICE candidate from User ${data.user_id}`);
                    }
                    iceCandidateQueue[data.user_id] = [];
                }
            } catch (err) {
                log(`Error handling answer from User ${data.user_id}: ${err.message}`);
            }
        } else {
            log(`No peer connection found for User ${data.user_id} to handle answer`);
        }
    }

    async function handleIceCandidate(data) {
        if (peerConnections[data.user_id]) {
            const pc = peerConnections[data.user_id];
            try {
                if (pc.remoteDescription) {
                    await pc.addIceCandidate(new RTCIceCandidate(data.candidate));
                    log(`Added ICE candidate from User ${data.user_id}`);
                } else {
                    iceCandidateQueue[data.user_id].push(data.candidate);
                    log(`Queued ICE candidate from User ${data.user_id}, awaiting remote description`);
                }
            } catch (err) {
                log(`Error adding ICE candidate from User ${data.user_id}: ${err.message}`);
            }
        } else {
            log(`No peer connection found for User ${data.user_id} to handle ICE candidate`);
        }
    }

    function handleScreenShare(data) {
        if (data.user_id !== userId && peerConnections[data.user_id]) {
            log(`Screen share received from User ${data.user_id}`);
        }
    }

    function updateScreenShareUI(stream, memberId) {
        const screenShareVideo = document.getElementById('screen-share-video');
        screenShareVideo.srcObject = stream;
        document.getElementById('screen-share-label').textContent = `${memberNames[memberId] || 'Unknown'} (Screen)`;
        document.getElementById('screen-share-container').classList.remove('d-none');
        currentScreenSharer = memberId;
        setScreenShareFullView();
    }

    function setScreenShareFullView() {
        const screenShareContainer = document.getElementById('screen-share-container');
        const participantVideos = document.getElementById('participant-videos');
        screenShareContainer.classList.remove('d-none');
        screenShareContainer.style.width = '75%';
        screenShareContainer.style.height = '100%';
        participantVideos.style.width = '25%';
        participantVideos.style.height = '100%';
        log('Screen share set to full view');
    }

    function setSpeakerView() {
        const participantVideos = document.getElementById('participant-videos');
        const screenShareContainer = document.getElementById('screen-share-container');
        participantVideos.style.display = 'none';
        screenShareContainer.style.height = currentScreenSharer ? '50%' : '0';
        document.querySelectorAll('.video-wrapper').forEach(wrapper => wrapper.style.display = 'none');
        if (activeSpeaker) {
            const speakerVideo = document.getElementById(`remoteVideo-${activeSpeaker}`);
            if (speakerVideo) {
                speakerVideo.parentElement.style.display = 'block';
                log(`Switched to speaker view for User ${activeSpeaker}`);
            } else {
                log(`Speaker video for User ${activeSpeaker} not found`);
            }
        } else {
            log('No active speaker detected');
        }
    }

    function setParticipantsView() {
        const participantVideos = document.getElementById('participant-videos');
        const screenShareContainer = document.getElementById('screen-share-container');
        participantVideos.style.display = 'flex';
        screenShareContainer.style.height = currentScreenSharer ? '50%' : '0';
        document.querySelectorAll('.video-wrapper').forEach(wrapper => wrapper.style.display = 'block');
        log('Switched to participants view');
    }

    function updateActiveSpeaker(userId, volume) {
        if (volume > 10) {
            activeSpeaker = userId;
            if (document.getElementById('speaker-view-btn').classList.contains('btn-success')) {
                setSpeakerView();
            }
            log(`Active speaker updated to User ${userId} with volume ${volume}`);
        }
    }

    function endPeerConnection(userId) {
        if (peerConnections[userId]) {
            peerConnections[userId].close();
            delete peerConnections[userId];
            delete iceCandidateQueue[userId];
            const videoWrapper = document.querySelector(`#participant-videos .video-wrapper:has(#remoteVideo-${userId})`);
            if (videoWrapper) {
                videoWrapper.remove();
                log(`Removed video UI for User ${userId}`);
            }
            if (currentScreenSharer === userId) {
                document.getElementById('screen-share-container').classList.add('d-none');
                currentScreenSharer = null;
            }
            participants.delete(userId);
            updateParticipantList();
            log(`Peer connection ended for User ${userId}`);
        }
    }

    function endVideoCall(classroomId) {
        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
            log('Stopped local stream tracks');
        }
        if (screenStream) {
            screenStream.getTracks().forEach(track => track.stop());
            log('Stopped screen stream tracks');
        }
        if (audioContext) {
            audioContext.close();
            log('Closed audio context');
        }
        Object.keys(peerConnections).forEach(userId => endPeerConnection(userId));
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
            log(`Sent video call ended signal for classroom ${classroomId}`);
        }
        $('#videoCallModal').modal('hide');
        $('#video-controls').empty();
        isCallActive = false;
        hideJoinToast();
        log('Video call ended');
    }

    function updateParticipantList() {
        const participantList = document.getElementById('participant-list');
        participantList.innerHTML = '';
        participants.forEach(pid => {
            const li = document.createElement('li');
            li.textContent = memberNames[pid] || 'Unknown';
            if (pid === activeSpeaker) li.style.fontWeight = 'bold';
            participantList.appendChild(li);
        });
        log(`Updated participant list: ${Array.from(participants).join(', ')}`);
    }

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
        log('Enabled draggable videos');
    }

    function setupControls() {
        $('#mute-audio').on('click', function() {
            const audioTrack = localStream.getAudioTracks()[0];
            audioTrack.enabled = !audioTrack.enabled;
            $(this).html(audioTrack.enabled ? '<i class="bi bi-mic"></i>' : '<i class="bi bi-mic-mute"></i>')
                .toggleClass('btn-danger btn-outline-secondary');
            log(audioTrack.enabled ? 'Audio unmuted' : 'Audio muted');
        });

        $('#disable-video').on('click', function() {
            const videoTrack = localStream.getVideoTracks()[0];
            videoTrack.enabled = !videoTrack.enabled;
            $(this).html(videoTrack.enabled ? '<i class="bi bi-camera-video"></i>' : '<i class="bi bi-camera-video-off"></i>')
                .toggleClass('btn-danger btn-outline-secondary');
            log(videoTrack.enabled ? 'Video enabled' : 'Video disabled');
        });

        $('#share-screen').on('click', async function() {
            try {
                if (!screenStream) {
                    screenStream = await navigator.mediaDevices.getDisplayMedia({
                        video: { cursor: 'always', frameRate: 15 },
                        audio: true
                    });
                    const screenTrack = screenStream.getVideoTracks()[0];
                    screenTrack.onended = () => stopScreenSharing();
                    Object.values(peerConnections).forEach(pc => {
                        const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (sender) {
                            sender.replaceTrack(screenTrack);
                            log(`Replaced video track with screen for peer`);
                        } else {
                            pc.addTrack(screenTrack, screenStream);
                            log(`Added screen track to peer`);
                        }
                    });
                    ws.send(JSON.stringify({
                        type: 'screen_shared',
                        classroom_id: classroomId,
                        user_id: userId
                    }));
                    $(this).addClass('btn-success').removeClass('btn-outline-secondary');
                    updateScreenShareUI(screenStream, userId);
                    log('Screen sharing started');
                } else {
                    stopScreenSharing();
                }
            } catch (err) {
                log(`Error sharing screen: ${err.message}`);
            }
        });

        $('#speaker-view-btn').on('click', function() {
            $(this).addClass('btn-success').removeClass('btn-outline-secondary');
            $('#participants-view-btn').removeClass('btn-success').addClass('btn-outline-secondary');
            setSpeakerView();
        });

        $('#participants-view-btn').on('click', function() {
            $(this).addClass('btn-success').removeClass('btn-outline-secondary');
            $('#speaker-view-btn').removeClass('btn-success').addClass('btn-outline-secondary');
            setParticipantsView();
        });

        $('#cameraSelect').on('change', async function() {
            const deviceId = this.value;
            if (deviceId && localStream) {
                localStream.getVideoTracks().forEach(track => track.stop());
                const newStream = await navigator.mediaDevices.getUserMedia({
                    video: { ...videoConstraints, deviceId },
                    audio: true
                });
                const videoTrack = newStream.getVideoTracks()[0];
                localStream.removeTrack(localStream.getVideoTracks()[0]);
                localStream.addTrack(videoTrack);
                document.getElementById(`remoteVideo-${userId}`).srcObject = localStream;
                Object.values(peerConnections).forEach(pc => {
                    const sender = pc.getSenders().find(s => s.track.kind === 'video');
                    if (sender) sender.replaceTrack(videoTrack);
                });
                log(`Switched camera to device ${deviceId}`);
            }
        });

        $('#micSelect').on('change', async function() {
            const deviceId = this.value;
            if (deviceId && localStream) {
                localStream.getAudioTracks().forEach(track => track.stop());
                const newStream = await navigator.mediaDevices.getUserMedia({
                    video: videoConstraints,
                    audio: { deviceId, echoCancellation: true, noiseSuppression: true }
                });
                const audioTrack = newStream.getAudioTracks()[0];
                localStream.removeTrack(localStream.getAudioTracks()[0]);
                localStream.addTrack(audioTrack);
                document.getElementById(`remoteVideo-${userId}`).srcObject = localStream;
                Object.values(peerConnections).forEach(pc => {
                    const sender = pc.getSenders().find(s => s.track.kind === 'audio');
                    if (sender) sender.replaceTrack(audioTrack);
                });
                setupAudioVisualizer();
                log(`Switched microphone to device ${deviceId}`);
            }
        });

        $('#refresh-connection').on('click', function() {
            Object.values(peerConnections).forEach(pc => {
                pc.restartIce();
                log('Restarting ICE for peer connections');
            });
        });

        $('#end-video-call-btn').on('click', function() {
            endVideoCall(classroomId);
        });

        $('#close-video-modal').on('click', function() {
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
                if (sender) {
                    sender.replaceTrack(videoTrack);
                    log(`Reverted to camera track for peer`);
                }
            });
            ws.send(JSON.stringify({
                type: 'screen_share_stopped',
                classroom_id: classroomId,
                user_id: userId
            }));
            if (currentScreenSharer === userId) {
                document.getElementById('screen-share-container').classList.add('d-none');
                currentScreenSharer = null;
            }
            setParticipantsView();
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
                let x = 0;
                for (let i = 0; i < bufferLength; i++) {
                    const barHeight = dataArray[i] / 2;
                    canvasCtx.fillStyle = `rgb(${barHeight + 100}, 50, 50)`;
                    canvasCtx.fillRect(x, canvas.height - barHeight, barWidth, barHeight);
                    x += barWidth + 1;
                }

                const volume = dataArray.reduce((a, b) => a + b) / bufferLength;
                ws.send(JSON.stringify({
                    type: 'speaker_activity',
                    classroom_id: classroomId,
                    user_id: userId,
                    volume: volume
                }));
            }
            draw();
            log('Audio visualizer set up');
        }
    }

    $('#start-video-call').on('click', function() {
        const classroomId = $(this).data('classroom-id');
        startVideoCall(classroomId);
    });

    $('#join-meeting-btn').on('click', function() {
        joinVideoCall(classroomId);
        document.getElementById('meeting-notification').classList.add('d-none');
    });

    connectWebSocket();
});