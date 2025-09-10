<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }} - Detection Result</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    </head>
    <body class="flex h-screen bg-gray-900 text-white p-8">
        
        <div class="container mx-auto grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
            
            <div>
                <h1 class="text-4xl font-bold mb-4">Original Video</h1>
                <div class="aspect-video bg-black rounded-lg overflow-hidden">
                    <video class="w-full h-full" controls autoplay>
                        <source src="{{ route('video.stream', ['filename' => $filename]) }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
                
                <!-- MP3 Section -->
                <div id="mp3-section" class="mt-4 hidden">
                    <h2 class="text-2xl font-bold mb-2">Audio Version</h2>
                    <audio id="mp3-audio" controls class="w-full mb-2"></audio>
                    <a id="mp3-download" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded" download>
                        Download MP3
                    </a>
                </div>
            </div>

            <div>
                <h1 class="text-4xl font-bold mb-4">Detection Results</h1>
                
                <!-- Song Recognition Results -->
                <div id="song-results" class="bg-gray-800 rounded-lg p-6 space-y-4 max-h-[40vh] overflow-y-auto mb-4">
                    <p class="text-gray-400 text-center">Song recognition results will appear here...</p>
                </div>

                <!-- Original Detection Results -->
                <h2 class="text-2xl font-bold mb-4 hidden">Original Detections</h2>
                <div id="results-list" class="bg-gray-800 rounded-lg p-6 hidden space-y-4 max-h-[40vh] overflow-y-auto">
                    <!-- Original results will be displayed here -->
                </div>
            </div>

        </div>

        <!-- Conversion Status -->
        <div id="conversion-status" class="fixed bottom-4 right-4 bg-gray-800 p-4 rounded-lg shadow-lg hidden">
            <p class="text-center">Processing...</p>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', (event) => {
                const filename = "{{ $filename }}";
                const resultsList = document.getElementById('results-list');
                const songResults = document.getElementById('song-results');
                const conversionStatus = document.getElementById('conversion-status');
                const mp3Section = document.getElementById('mp3-section');
                const mp3Audio = document.getElementById('mp3-audio');
                const mp3Download = document.getElementById('mp3-download');

                // Display original detection results
                displayOriginalResults();
                
                // Start MP3 conversion and song recognition
                convertVideoAndRecognizeSongs(filename);

                function displayOriginalResults() {
                    const resultsData = JSON.parse(sessionStorage.getItem('detectionResult') || '{}');
                    
                    if (resultsData && resultsData.predictions && resultsData.predictions.length > 0) {
                        resultsData.predictions.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'text-lg p-4 bg-gray-700 rounded';
                            div.textContent = item;
                            resultsList.appendChild(div);
                        });
                    } else {
                        resultsList.innerHTML = '<p class="text-gray-400">No original detections found.</p>';
                    }
                    
                    // Clean up storage
                    sessionStorage.removeItem('detectionResult');
                }

                async function convertVideoAndRecognizeSongs(filename) {
                    showStatus('Converting video to MP3 and analyzing songs...');
                    
                    try {
                        const response = await fetch('{{ route("convert.video") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ filename: filename })
                        });

                        const data = await response.json();
                        
                        if (response.ok) {
                            // Show MP3 player
                            mp3Audio.src = data.mp3_url;
                            mp3Download.href = data.mp3_url;
                            mp3Download.download = data.mp3_filename;
                            // mp3Section.classList.remove('hidden');
                            
                            // Display song recognition results
                            displaySongResults(data.recognition_results);
                            
                            showStatus('✓ Conversion and analysis complete!', 'success');
                        } else {
                            showStatus('Error: ' + data.error, 'error');
                        }
                    } catch (error) {
                        showStatus('Processing failed: ' + error.message, 'error');
                    }
                }

                function displaySongResults(results) {
                    songResults.innerHTML = ''; // Clear previous results
                    
                    if (results && results.results && results.results.length > 0) {
                        const title = document.createElement('h3');
                        title.className = 'text-xl font-bold mb-3 text-green-400';
                        title.textContent = '🎵 Songs Recognized';
                        songResults.appendChild(title);
                        
                        results.results.forEach((song, index) => {
                            const songCard = document.createElement('div');
                            songCard.className = 'p-4 bg-gray-700 rounded-lg';
                            
                            songCard.innerHTML = `
                                <div class="flex justify-between items-start mb-2">
                                    <span class="font-bold text-lg">${index + 1}. ${song.song || 'Unknown Song'}</span>
                                    <span class="bg-green-600 text-white px-2 py-1 rounded text-sm">
                                        ${song.confidence || '0'} hash confidence
                                    </span>
                                </div>
                                ${song.start_time ? `<div class="text-sm text-gray-300">Position: ${song.end_time}</div>` : ''}
                            `;
                            
                            songResults.appendChild(songCard);
                        });
                    } else {
                        songResults.innerHTML = `
                            <p class="text-gray-400 text-center">
                                No songs recognized or recognition service unavailable.
                            </p>
                        `;
                    }
                }

                function showStatus(message, type = 'info') {
                    conversionStatus.classList.remove('hidden');
                    conversionStatus.innerHTML = `<p class="text-center ${type === 'success' ? 'text-green-400' : type === 'error' ? 'text-red-400' : ''}">${message}</p>`;
                    
                    // Auto-hide success messages after 5 seconds
                    if (type === 'success') {
                        setTimeout(() => {
                            conversionStatus.classList.add('hidden');
                        }, 5000);
                    }
                }
            });
        </script>

    </body>
</html>