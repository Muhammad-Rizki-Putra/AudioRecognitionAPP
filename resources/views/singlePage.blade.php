<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    {{-- Ensure your Tailwind CSS is built and linked correctly --}}
    @vite(['resources/css/app.css', 'resources/js/app.js']) 

</head>
<body class="flex h-screen bg-black">
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>

    <div class="flex flex-col items-center justify-center w-full bg-gray-900 p-4">
        <h1 class="text-4xl font-bold text-white mb-8">Sound Detection</h1>
    
        <div class="mb-4">
            <button id="recordButton" class="text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-lg px-8 py-4 mr-4 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                Start Listening
            </button>
            <button id="stopRecordButton" class="text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-lg px-8 py-4 opacity-50 cursor-not-allowed dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800" disabled>
                Stop Listening
            </button>
        </div>

        <div class="mb-8">
            <button id="playButton" class="text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-lg px-8 py-4 mr-4 opacity-50 cursor-not-allowed" disabled>
                Play
            </button>
            <button id="recognizeButton" class="text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-lg px-8 py-4 opacity-50 cursor-not-allowed" disabled>
                Recognize
            </button>
        </div>

        <div id="statusMessage" class="text-white text-xl mb-2">Ready</div>
        <div id="micDisplay" class="text-gray-400 text-sm mb-4"></div>
        <div id="resultDisplay" class="text-green-400 text-2xl font-semibold"></div>    
        
        <audio id="audioPlayback" controls class="hidden"></audio>

    </div>

    <script>
        const recordButton = document.getElementById('recordButton');
        const stopRecordButton = document.getElementById('stopRecordButton');
        const playButton = document.getElementById('playButton');
        const recognizeButton = document.getElementById('recognizeButton');
        const statusMessage = document.getElementById('statusMessage');
        const micDisplay = document.getElementById('micDisplay');
        const resultDisplay = document.getElementById('resultDisplay');
        const audioPlayback = document.getElementById('audioPlayback');

        let mediaRecorder;
        let audioChunks = [];
        const RECOGNITION_CONFIDENCE_THRESHOLD = 15; // Set the minimum confidence score
        const RECORDING_CHUNK_SIZE = 3000; // 3 seconds in milliseconds
        let combinedBlob; // Stores the accumulated audio

        function updateButtonState(isRecording) {
            recordButton.disabled = isRecording;
            recordButton.classList.toggle('opacity-50', isRecording);
            recordButton.classList.toggle('cursor-not-allowed', isRecording);

            stopRecordButton.disabled = !isRecording;
            stopRecordButton.classList.toggle('opacity-50', !isRecording);
            stopRecordButton.classList.toggle('cursor-not-allowed', !isRecording);

            playButton.disabled = isRecording || !combinedBlob;
            playButton.classList.toggle('opacity-50', playButton.disabled);
            playButton.classList.toggle('cursor-not-allowed', playButton.disabled);

            recognizeButton.disabled = isRecording || !combinedBlob;
            recognizeButton.classList.toggle('opacity-50', recognizeButton.disabled);
            recognizeButton.classList.toggle('cursor-not-allowed', recognizeButton.disabled);
        }

        recordButton.addEventListener('click', async () => {
            resultDisplay.textContent = '';
            micDisplay.textContent = '';
            statusMessage.textContent = 'Requesting microphone access...';
            updateButtonState(true);

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                const devices = await navigator.mediaDevices.enumerateDevices();
                const audioTrack = stream.getAudioTracks()[0];
                const microphone = devices.find(device => device.kind === 'audioinput' && device.label === audioTrack.label);

                if (microphone) {
                    micDisplay.textContent = `Using: ${microphone.label}`;
                } else {
                    micDisplay.textContent = `Using: Default Microphone`;
                }
                
                // Reset for a new recording session
                audioChunks = [];
                combinedBlob = null;
                audioPlayback.classList.add('hidden');
                audioPlayback.src = '';
                
                mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });

                mediaRecorder.ondataavailable = async (event) => {
                    if (event.data.size > 0) {
                        audioChunks.push(event.data);
                        // Combine current chunks and send for recognition
                        combinedBlob = new Blob(audioChunks, { type: 'audio/webm' });
                        await sendAudioChunkForRecognition(combinedBlob);
                    }
                };

                mediaRecorder.onstop = () => {
                    stream.getTracks().forEach(track => track.stop());
                    statusMessage.textContent = 'Recording stopped.';
                    micDisplay.textContent = '';
                    updateButtonState(false);
                };

                mediaRecorder.start(RECORDING_CHUNK_SIZE);
                statusMessage.textContent = 'Listening for a song...';
                
            } catch (error) {
                console.error('Error accessing microphone:', error);
                statusMessage.textContent = 'Error: Could not access microphone.';
                micDisplay.textContent = '';
                updateButtonState(false);
            }
        });

        stopRecordButton.addEventListener('click', () => {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
            }
        });

        playButton.addEventListener('click', () => {
            if (combinedBlob) {
                const audioUrl = URL.createObjectURL(combinedBlob);
                audioPlayback.src = audioUrl;
                audioPlayback.classList.remove('hidden');
                audioPlayback.play();
                statusMessage.textContent = 'Playing recorded audio...';
            }
        });

        recognizeButton.addEventListener('click', async () => {
            if (!combinedBlob) {
                statusMessage.textContent = 'Please record a song first to recognize.';
                return;
            }

            statusMessage.textContent = 'Sending audio to API for recognition...';
            resultDisplay.textContent = '';

            await sendAudioChunkForRecognition(combinedBlob);
        });

        async function sendAudioChunkForRecognition(blob) {
            statusMessage.textContent = `Analyzing ${Math.round(blob.size / 1024)} KB of audio...`;
            const formData = new FormData();
            formData.append('audio_file', blob, 'recorded_audio.webm');
            formData.append('mode', 'single');

            try {
                const response = await fetch('http://localhost:5000/recognize', {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json();

                if (response.ok && data.results && data.results.length > 0) {
                    const song = data.results[0];
                    if (song.confidence >= RECOGNITION_CONFIDENCE_THRESHOLD) {
                        resultDisplay.textContent = `🎵 Match Found: ${song.song} (at ${song.start_time}) with a confidence of ${song.confidence}`;
                        statusMessage.textContent = 'Recognition successful!';
                        // Automatically stop recording if the confidence is high enough
                        if (mediaRecorder && mediaRecorder.state === 'recording') {
                             mediaRecorder.stop();
                        }
                    } else {
                        resultDisplay.textContent = `🧐 Possible Match: ${song.song} (Confidence: ${song.confidence}). Recording more...`;
                        statusMessage.textContent = 'Confidence is too low. Recording more...';
                        // The ondataavailable handler will automatically send the next chunk
                    }
                } else {
                    resultDisplay.textContent = `❌ No song found.`;
                    statusMessage.textContent = 'Recognition complete. No match found.';
                }
            } catch (error) {
                console.error('Fetch error:', error);
                statusMessage.textContent = 'Error communicating with server. Check Flask server.';
            }
        }

        updateButtonState(false);
    </script>
</body>
</html>