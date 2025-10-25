<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sound Detection</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #111827;
            color: #e5e7eb;
        }
        
        .pulse-ring {
            animation: pulse-ring 2s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
        }
        
        @keyframes pulse-ring {
            0% {
                transform: scale(0.8);
                opacity: 1;
            }
            100% {
                transform: scale(1.2);
                opacity: 0;
            }
        }
        
        .wave-animation {
            animation: wave 1.5s ease-in-out infinite;
        }
        
        @keyframes wave {
            0%, 100% { height: 8px; }
            50% { height: 24px; }
        }

        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #374151;
            border-radius: 4px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #6B7280;
            border-radius: 4px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #9CA3AF;
        }

        /* Desktop-specific styles */
        @media (min-width: 768px) {
            .desktop-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 2rem;
            }
            
            .desktop-layout {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 2rem;
                align-items: start;
            }
            
            .desktop-controls {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .desktop-side-panel {
                background: #1f2937;
                border-radius: 12px;
                padding: 1.5rem;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            }
            
            .desktop-waveform {
                height: 120px;
                margin: 1.5rem 0;
            }
            
            .desktop-results {
                max-height: 300px;
                overflow-y: auto;
            }
            
            .desktop-title {
                font-size: 1.875rem;
                margin-bottom: 1.5rem;
                text-align: center;
                color: #3b82f6;
            }
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Mobile View -->
    <div class="block md:hidden bg-gray-900 min-h-screen">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-700 to-blue-800 text-white p-4">
            <div class="flex justify-between items-center mb-2">
                <div class="w-6"></div>
                <h1 class="text-lg font-semibold">Sound Detection</h1>
                <div class="flex items-center space-x-1">
                    <div class="w-4 h-2 bg-blue-300 rounded-sm"></div>
                    <div class="w-4 h-2 bg-blue-300 rounded-sm"></div>
                    <div class="w-4 h-2 bg-blue-300 rounded-sm"></div>
                    <div class="w-2 h-2 bg-blue-300 rounded-full"></div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex flex-col items-center justify-center px-8 py-12 space-y-8">
            <!-- Recording Circle -->
            <div class="relative flex items-center justify-center">
                <!-- Pulse rings -->
                <div id="pulseRing1Mobile" class="absolute w-48 h-48 rounded-full bg-blue-800 opacity-0"></div>
                <div id="pulseRing2Mobile" class="absolute w-40 h-40 rounded-full bg-blue-700 opacity-0"></div>
                
                <!-- Main circle -->
                <div id="recordingCircleMobile" class="w-32 h-32 bg-blue-600 rounded-full flex items-center justify-center shadow-lg">
                    <!-- Microphone Icon -->
                    <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2a3 3 0 0 1 3 3v6a3 3 0 0 1-6 0V5a3 3 0 0 1 3-3Z"/>
                        <path d="M19 10v1a7 7 0 0 1-14 0v-1"/>
                        <path d="M12 18v4"/>
                        <path d="M8 22h8"/>
                    </svg>
                </div>
            </div>

            <!-- Timer Display -->
            <div class="text-center">
                <div id="timerMobile" class="text-4xl font-mono font-bold text-white mb-2">00:00:00</div>
                <div id="statusMessageMobile" class="text-gray-400 text-sm">Ready to listen</div>
            </div>

            <!-- Possible Match Display -->
            <div id="possibleMatchMobile" class="text-center text-blue-400 font-semibold px-4 min-h-8"></div>

        

            <!-- Microphone Info -->
            <div id="micDisplayMobile" class="text-xs text-gray-500 text-center px-4"></div>

            <!-- Control Buttons -->
            <div class="flex items-center justify-center space-x-4">
                <!-- Pause/Play Button (when recording) -->
                <button id="pauseButtonMobile" class="w-12 h-12 bg-blue-800 rounded-full flex items-center justify-center opacity-0 transition-opacity">
                    <svg class="w-6 h-6 text-blue-300" fill="currentColor" viewBox="0 0 24 24">
                        <rect x="6" y="4" width="4" height="16"/>
                        <rect x="14" y="4" width="4" height="16"/>
                    </svg>
                </button>
            </div>

            <!-- Main Action Button -->
            <button id="mainActionButtonMobile" class="w-48 h-12 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-full transition-colors">
                Start Listening
            </button>

            <!-- Secondary Actions -->
            <div class="flex space-x-4 opacity-0" id="secondaryActionsMobile">
                <button id="playButtonMobile" class="px-6 py-2 bg-gray-700 text-gray-300 rounded-full font-medium">
                    Play
                </button>
                <button id="recognizeButtonMobile" class="px-6 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-full font-medium">
                    Recognize
                </button>
            </div>

            <!-- Result Display -->
            <div id="resultDisplayMobile" class="text-center text-blue-400 font-semibold px-4"></div>
        </div>

        <!-- Hidden Audio Element -->
        <audio id="audioPlaybackMobile" controls class="hidden"></audio>
    </div>

    <!-- Desktop View -->
    <div class="hidden md:block desktop-container">
        <h1 class="desktop-title">Sound Detection</h1>
        
        <div class="desktop-layout">
            <!-- Left Panel - Controls -->
            <div class="desktop-controls">
                <div class="desktop-side-panel">
                    <!-- Recording Section -->
                    <div class="flex flex-col items-center justify-center">
                        <!-- Recording Circle -->
                        <div class="relative flex items-center justify-center mb-6">
                            <!-- Pulse rings -->
                            <div id="pulseRing1Desktop" class="absolute w-48 h-48 rounded-full bg-blue-800 opacity-0"></div>
                            <div id="pulseRing2Desktop" class="absolute w-40 h-40 rounded-full bg-blue-700 opacity-0"></div>
                            
                            <!-- Main circle -->
                            <div id="recordingCircleDesktop" class="w-32 h-32 bg-blue-600 rounded-full flex items-center justify-center shadow-lg">
                                <!-- Microphone Icon -->
                                <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2a3 3 0 0 1 3 3v6a3 3 0 0 1-6 0V5a3 3 0 0 1 3-3Z"/>
                                    <path d="M19 10v1a7 7 0 0 1-14 0v-1"/>
                                    <path d="M12 18v4"/>
                                    <path d="M8 22h8"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Timer Display -->
                        <div class="text-center mb-4">
                            <div id="timerDesktop" class="text-4xl font-mono font-bold text-white mb-2">00:00:00</div>
                            <div id="statusMessageDesktop" class="text-gray-400 text-sm">Ready to listen</div>
                        </div>

                        <!-- Possible Match Display -->
                        <div id="possibleMatchDesktop" class="text-center text-blue-400 font-semibold px-4 min-h-8 mb-4"></div>

                        <!-- Main Action Button -->
                        <button id="mainActionButtonDesktop" class="w-48 h-12 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-full transition-colors mb-4">
                            Start Listening
                        </button>

                        <!-- Control Buttons -->
                        <div class="flex items-center justify-center space-x-4 mb-4">
                            <!-- Pause/Play Button (when recording) -->
                            <button id="pauseButtonDesktop" class="w-12 h-12 bg-blue-800 rounded-full flex items-center justify-center opacity-0 transition-opacity">
                                <svg class="w-6 h-6 text-blue-300" fill="currentColor" viewBox="0 0 24 24">
                                    <rect x="6" y="4" width="4" height="16"/>
                                    <rect x="14" y="4" width="4" height="16"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Microphone Info -->
                        <div id="micDisplayDesktop" class="text-xs text-gray-500 text-center px-4 mb-4"></div>
                    </div>
                </div>

                
            </div>

            <!-- Right Panel - Results -->
            <div class="desktop-side-panel desktop-results custom-scrollbar">
                <h2 class="text-xl font-semibold text-center mb-4 text-blue-300">Detection Results</h2>
                <div id="resultDisplayDesktop" class="text-center text-blue-400 font-semibold px-4"></div>
                
                <div class="mt-6">
                    <h3 class="text-lg font-semibold mb-3 text-blue-300">Recent Detections</h3>
                    <div id="detectionHistory" class="space-y-3">
                        <div class="text-sm text-gray-400 text-center">No recent detections</div>
                    </div>
                </div>
                
                <div class="mt-6">
                    <h3 class="text-lg font-semibold mb-3 text-blue-300">Settings</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" id="autoRecognize" class="rounded bg-gray-700 border-gray-600 mr-2" checked>
                                <span>Auto-recognize on detection</span>
                            </label>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="range" id="sensitivity" class="w-full accent-blue-600" min="1" max="100" value="75">
                                <span class="ml-2">Sensitivity: <span id="sensitivityValue">75</span>%</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden Audio Element -->
        <audio id="audioPlaybackDesktop" controls class="hidden"></audio>
    </div>

    <script>
        // --- Cleaned up variable declarations ---
        // Mobile elements
        const mainActionButtonMobile = document.getElementById('mainActionButtonMobile');
        const pauseButtonMobile = document.getElementById('pauseButtonMobile');
        const playButtonMobile = document.getElementById('playButtonMobile');
        const recognizeButtonMobile = document.getElementById('recognizeButtonMobile');
        const statusMessageMobile = document.getElementById('statusMessageMobile');
        const micDisplayMobile = document.getElementById('micDisplayMobile');
        const resultDisplayMobile = document.getElementById('resultDisplayMobile');
        const possibleMatchMobile = document.getElementById('possibleMatchMobile');
        const audioPlaybackMobile = document.getElementById('audioPlaybackMobile');
        const timerMobile = document.getElementById('timerMobile');
        const recordingCircleMobile = document.getElementById('recordingCircleMobile');
        const waveformMobile = document.getElementById('waveformMobile');
        const pulseRing1Mobile = document.getElementById('pulseRing1Mobile');
        const pulseRing2Mobile = document.getElementById('pulseRing2Mobile');
        const secondaryActionsMobile = document.getElementById('secondaryActionsMobile');

        // Desktop elements
        const mainActionButtonDesktop = document.getElementById('mainActionButtonDesktop');
        const pauseButtonDesktop = document.getElementById('pauseButtonDesktop');
        const playButtonDesktop = document.getElementById('playButtonDesktop');
        const recognizeButtonDesktop = document.getElementById('recognizeButtonDesktop');
        const statusMessageDesktop = document.getElementById('statusMessageDesktop');
        const micDisplayDesktop = document.getElementById('micDisplayDesktop');
        const resultDisplayDesktop = document.getElementById('resultDisplayDesktop');
        const possibleMatchDesktop = document.getElementById('possibleMatchDesktop');
        const audioPlaybackDesktop = document.getElementById('audioPlaybackDesktop');
        const timerDesktop = document.getElementById('timerDesktop');
        const recordingCircleDesktop = document.getElementById('recordingCircleDesktop');
        const waveformDesktop = document.getElementById('waveformDesktop');
        const pulseRing1Desktop = document.getElementById('pulseRing1Desktop');
        const pulseRing2Desktop = document.getElementById('pulseRing2Desktop');
        const secondaryActionsDesktop = document.getElementById('secondaryActionsDesktop');
        const detectionHistory = document.getElementById('detectionHistory');
        const sensitivitySlider = document.getElementById('sensitivity');
        const sensitivityValue = document.getElementById('sensitivityValue');

        // --- Cleaned up State Variables ---
        let mediaRecorder;
        let audioChunks = [];
        let isRecording = false;
        let startTime;
        let timerInterval;
        let combinedBlob;
        let detectionCount = 0;

        // Variables for the new incremental logic
        const RECOGNITION_CONFIDENCE_THRESHOLD = 3; // You can tune this
        const RECORDING_CHUNK_DURATION = 6300; // Recognize every 3 seconds
        let isMatchFound = false;
        let isRecognizing = false;

        // --- The rest of your functions ---
        // Update sensitivity value display
        if (sensitivitySlider) {
            sensitivitySlider.addEventListener('input', () => {
                sensitivityValue.textContent = sensitivitySlider.value;
            });
        }

        function updateTimer() {
            if (!startTime) return;
            const elapsed = Date.now() - startTime;
            const hours = Math.floor(elapsed / 3600000);
            const minutes = Math.floor((elapsed % 3600000) / 60000);
            const seconds = Math.floor((elapsed % 60000) / 1000);
            const timerText = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            if (timerMobile) timerMobile.textContent = timerText;
            if (timerDesktop) timerDesktop.textContent = timerText;
        }

        function updateStatusMessage(message) {
            if (statusMessageMobile) statusMessageMobile.textContent = message;
            if (statusMessageDesktop) statusMessageDesktop.textContent = message;
        }

        function updateMicDisplay(message) {
            if (micDisplayMobile) micDisplayMobile.textContent = message;
            if (micDisplayDesktop) micDisplayDesktop.textContent = message;
        }

        function updatePossibleMatch(message) {
            if (possibleMatchMobile) possibleMatchMobile.textContent = message;
            if (possibleMatchDesktop) possibleMatchDesktop.textContent = message;
        }

        function updateResultDisplay(message) {
            if (resultDisplayMobile) {
                resultDisplayMobile.innerHTML = message;
            }
            if (resultDisplayDesktop) {
                resultDisplayDesktop.innerHTML = message;
            }
        }

        function toggleMainButton(isRecording) {
            const buttonText = isRecording ? 'Stop Listening' : 'Start Listening';
            if (mainActionButtonMobile) mainActionButtonMobile.textContent = buttonText;
            if (mainActionButtonDesktop) mainActionButtonDesktop.textContent = buttonText;
        }

        async function startRecording() {
            updateStatusMessage('Requesting microphone access...');
            updateResultDisplay('');
            updatePossibleMatch('');
            updateMicDisplay('');

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                const devices = await navigator.mediaDevices.enumerateDevices();
                const audioTrack = stream.getAudioTracks()[0];
                const microphone = devices.find(device => device.kind === 'audioinput' && device.label === audioTrack.label);

                // Cleaned up state reset
                isMatchFound = false;
                isRecognizing = false;
                audioChunks = [];
                combinedBlob = null;
                if (audioPlaybackMobile) {
                    audioPlaybackMobile.classList.add('hidden');
                    audioPlaybackMobile.src = '';
                }
                if (audioPlaybackDesktop) {
                    audioPlaybackDesktop.classList.add('hidden');
                    audioPlaybackDesktop.src = '';
                }

                if (microphone) {
                    updateMicDisplay(`Using: ${microphone.label}`);
                } else {
                    updateMicDisplay(`Using: Default Microphone`);
                }

                mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });

                mediaRecorder.ondataavailable = (event) => {
                    if (isMatchFound || isRecognizing) return;
                    if (event.data.size > 0) {
                        audioChunks.push(event.data);
                        const currentBlob = new Blob(audioChunks, { type: 'audio/webm' });
                        sendAudioForRecognition(currentBlob);
                    }
                };

                mediaRecorder.onstop = () => {
                    stream.getTracks().forEach(track => track.stop());
                    if (audioChunks.length > 0) {
                        combinedBlob = new Blob(audioChunks, { type: 'audio/webm' });
                    }
                    
                    if (!isMatchFound) {
                        updateStatusMessage('Recording stopped');
                    }
                    
                    updateMicDisplay('');
                    isRecording = false;
                    toggleMainButton(false);
                    clearInterval(timerInterval);
                };

                mediaRecorder.start(RECORDING_CHUNK_DURATION);
                isRecording = true;
                startTime = Date.now();
                timerInterval = setInterval(updateTimer, 100);
                toggleMainButton(true);
                updateStatusMessage('Listening...');
                startRecordingAnimation();

            } catch (error) {
                console.error('Error accessing microphone:', error);
                updateStatusMessage('Error: Could not access microphone');
                updateMicDisplay('');
            }
        }

        function stopRecording() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
            }
        }

        function playRecording() {
            if (combinedBlob) {
                const audioUrl = URL.createObjectURL(combinedBlob);
                if (audioPlaybackMobile) {
                    audioPlaybackMobile.src = audioUrl;
                    audioPlaybackMobile.classList.remove('hidden');
                    audioPlaybackMobile.play();
                }
                if (audioPlaybackDesktop) {
                    audioPlaybackDesktop.src = audioUrl;
                    audioPlaybackDesktop.classList.remove('hidden');
                    audioPlaybackDesktop.play();
                }
                updateStatusMessage('Playing recorded audio...');
            }
        }

        async function sendAudioForRecognition(blob) {
                if (isRecognizing || isMatchFound) return;

                isRecognizing = true;
                const audioDuration = Math.round((Date.now() - startTime) / 1000);
                updateStatusMessage(`Analyzing ${audioDuration}s of audio...`);

                const formData = new FormData();
                formData.append('audio_file', blob, 'recorded_audio.webm');
                formData.append('mode', 'single');

                try {
                    const response = await fetch('http://127.0.0.1:5000/recognize', {
                        method: 'POST', body: formData,
                    });
                    const data = await response.json();

                    if (!isRecording || isMatchFound) { isRecognizing = false; return; }

                    if (response.ok && data.results && data.results.length > 0) {
                        const song = data.results[0];
                        if (song.confidence >= RECOGNITION_CONFIDENCE_THRESHOLD) {
                            isMatchFound = true;

                            // ***** THIS IS THE CORRECTED LINE *****
                            updateResultDisplay(`🎵 <strong>${song.song}</strong><br>Confidence: ${song.confidence}`);
                            // ************************************

                            updatePossibleMatch('');
                            updateStatusMessage('Match found!');
                            stopRecording();
                        } else {
                            updatePossibleMatch(`🧐 Possible: <strong>${song.song}</strong> (Confidence: ${song.confidence})`);
                            updateStatusMessage('Low confidence, listening for more...');
                        }
                    } else {
                        updatePossibleMatch(`❌ No song detected yet.`);
                        updateStatusMessage('No match yet, listening for more...');
                    }
                } catch (error) {
                    console.error('API Error:', error);
                    updateStatusMessage('Server connection error. Retrying...');
                } finally {
                    isRecognizing = false;
                }
            }

        async function recognizeAudio() {
            if (!combinedBlob) {
                updateStatusMessage('Please record a song first');
                return;
            }
            updateResultDisplay('');
            await sendAudioForRecognition(combinedBlob);
        }

        // --- Cleaned up Event Listeners ---
        if (mainActionButtonMobile) {
            mainActionButtonMobile.addEventListener('click', () => {
                if (!isRecording) {
                    startRecording();
                } else {
                    stopRecording();
                }
            });
        }

        if (playButtonMobile) {
            playButtonMobile.addEventListener('click', playRecording);
        }

        if (recognizeButtonMobile) {
            recognizeButtonMobile.addEventListener('click', recognizeAudio);
        }

        if (mainActionButtonDesktop) {
            mainActionButtonDesktop.addEventListener('click', () => {
                if (!isRecording) {
                    startRecording();
                } else {
                    stopRecording();
                }
            });
        }

        if (playButtonDesktop) {
            playButtonDesktop.addEventListener('click', playRecording);
        }

        if (recognizeButtonDesktop) {
            recognizeButtonDesktop.addEventListener('click', recognizeAudio);
        }

        // Reset timer display on page load
        if (timerMobile) timerMobile.textContent = '00:00:00';
        if (timerDesktop) timerDesktop.textContent = '00:00:00';
    </script>
</body>
</html>