<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <style>
            /* This is just for demonstration purposes */
            .hidden-input {
                display: none;
            }
            
            /* Custom styles for better visual feedback */
            #dropzone-file:hover {
                background-color: #f9fafb;
            }
            
            .drag-over {
                background-color: #e5e7eb !important;
                border-color: #3b82f6 !important;
            }
            
            #status-message {
                margin-top: 8px;
                font-size: 14px;
                color: #d1d5db;
            }
        </style>
    </head>
    <body class="flex h-screen bg-black">
        <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
        <div class="flex-col flex items-center justify-center w-full bg-gray-900">
            <h1 class="text-4xl font-bold text-white p-4">Sound Detection</h1>
            <label for="dropzone-file" class="flex flex-col items-center justify-center w-1/2 h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600">
                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                    <svg class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                    </svg>
                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">MP3 or MP4 File</p>
                </div>
                <input id="dropzone-file" type="file" class="hidden-input" accept="audio/*,video/*" />
            </label>
            <div id="progress-container" class="w-1/2 mt-4 hidden">
                <div class="flex justify-between mb-1">
                    <span class="text-base font-medium text-blue-700 dark:text-white">Uploading...</span>
                    <span id="progress-percent" class="text-sm font-medium text-blue-700 dark:text-white">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                    <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
                <div id="status-message" class="status-message"></div>
            </div>
            <div class="mt-4 p-4 flex flex-col justify-center items-center">
                 <h1 class="text-xl font-bold text-white p-4">Or press here for detecting single song</h1>
                 <a href="/detect-single" class="mt-4">
                     <button type="button" class="text-white bg-gray-800 hover:bg-gray-900 focus:outline-none focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-lg px-8 py-4 me-2 mb-2 dark:bg-gray-800 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700">Detect Song</button>
                 </a>
            </div>
        </div> 
        <script>
            // Add drag and drop functionality
            const dropZone = document.querySelector('label[for="dropzone-file"]');
            const fileInput = document.getElementById('dropzone-file');
            
            // Add drag and drop event listeners
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                dropZone.classList.add('drag-over');
            }
            
            function unhighlight() {
                dropZone.classList.remove('drag-over');
            }
            
            dropZone.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                if (files.length) {
                    fileInput.files = files;
                    const event = new Event('change');
                    fileInput.dispatchEvent(event);
                }
            }
            
            // File upload handling
            document.getElementById('dropzone-file').addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    // Validate file size (100MB limit)
                    const maxSize = 100 * 1024 * 1024;
                    if (file.size > maxSize) {
                        alert(`File too large. Maximum size is ${formatFileSize(maxSize)}.`);
                        return;
                    }
                    
                    uploadFileWithProgress(file);
                }
            });

            async function uploadFileWithProgress(file) {
                // Get DOM elements for the progress bar
                const progressContainer = document.getElementById('progress-container');
                const progressBar = document.getElementById('progress-bar');
                const progressPercent = document.getElementById('progress-percent');
                const statusMessage = document.getElementById('status-message');
                
                // Show upload status
                progressContainer.classList.remove('hidden');
                statusMessage.textContent = 'Starting upload...';
                
                // Prepare the form data
                const formData = new FormData();
                formData.append('video_file', file);
                
                try {
                    // Create a custom request with progress tracking
                    const response = await fetch('/upload-video', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    });
                    
                    // Update progress to 100% when request completes
                    progressBar.style.width = '100%';
                    progressPercent.innerText = '100%';
                    statusMessage.textContent = 'Processing...';
                    
                    if (response.ok) {
                        const data = await response.json();
                        statusMessage.textContent = 'Upload successful! Redirecting...';
                        
                        // Redirect after a short delay to show the 100% status
                        setTimeout(() => {
                            window.location.href = `/play-multiple/${data.filename}`;
                        }, 1000);
                    } else {
                        if (response.status === 413) {
                            throw new Error('File too large. Server rejected the upload.');
                        } else {
                            throw new Error(`Upload failed with status: ${response.status}`);
                        }
                    }
                } catch (error) {
                    progressContainer.classList.add('hidden');
                    alert('Upload error: ' + error.message);
                    console.error('Upload error:', error);
                }
            }
            
            // Helper function to format file size
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
        </script>
    </body>
</html>