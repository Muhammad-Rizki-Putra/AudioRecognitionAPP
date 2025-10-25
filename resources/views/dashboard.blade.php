<!DOCTYPE html>
<html>

<head>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Video Processing App</title>
    <style>
        .drag-area {
            border: 2px dashed #ccc;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        #songResults::-webkit-scrollbar {
            width: 8px;
        }

        #songResults::-webkit-scrollbar-track {
            background: #374151;
            /* gray-700 */
            border-radius: 4px;
        }

        #songResults::-webkit-scrollbar-thumb {
            background: #6B7280;
            /* gray-500 */
            border-radius: 4px;
        }

        #songResults::-webkit-scrollbar-thumb:hover {
            background: #9CA3AF;
            /* gray-400 */
        }

        .drag-area.active {
            border-color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.05);
        }

        .drag-area .icon {
            font-size: 50px;
            color: #6b7280;
        }

        .drag-area .header {
            font-size: 20px;
            font-weight: 500;
            color: #6b7280;
            margin: 10px 0;
        }

        .drag-area .support {
            color: #6b7280;
            font-size: 14px;
            margin: 5px 0;
        }

        .drag-area.hidden {
            display: none;
        }

        .video-container {
            position: relative;
            width: 100%;
            height: 100%;
        }

        #videoPlayer {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
    </style>
</head>

<body class="flex h-full w-auto min-h-screen">
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <div class="flex-col flex w-full bg-gray-900 p-8 gap-8">
        <div class="flex items-center">
            <h1 class="text-white text-base mr-8">URL</h1>
            <input type="url" id="url"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-1/2 p-2.5 dark:bg-white dark:border-gray-600 dark:placeholder-black dark:text-black dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="YouTube URL" required />
            <div class="flex pl-8">
                <button type="button" id="processUrlBtn"
                    class="text-white bg-gradient-to-r from-blue-500 via-blue-600 to-blue-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center me-2">Process</button>
                <button type="button" id="cancelBtn"
                    class="text-white bg-gradient-to-r from-red-500 via-red-600 to-red-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center me-2">Cancel</button>
            </div>
        </div>

        <div class="flex justify-center w-full gap-8 h-96">
            <div class="w-full aspect-video bg-black rounded-lg overflow-hidden">
                <div id="dragArea" class="drag-area w-full h-full">
                    <span class="icon">📁</span>
                    <header class="header">Drag & Drop MP4 Video</header>
                    <span class="support">or</span>
                    <button type="button" id="browseBtn"
                        class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5">Browse
                        Files</button>
                    <input type="file" id="fileInput" hidden accept="video/mp4">
                </div>
                <div id="video-container" class="aspect-video bg-black rounded-lg hidden h-full">
                    <div class="flex justify-center items-center h-full">
                        <video id="main-video" class="w-auto h-full" controls autoplay>
                            Your browser does not support the video tag.
                        </video>
                    </div>
                </div>
            </div>
            <div class="w-full flex flex-col gap-4 h-96">
                <div id="song-results" class="max-h-64 overflow-y-auto  bg-black rounded-lg p-6 space-y-4">
                    <p class="text-gray-400 text-center">Song recognition results will appear here...</p>
                </div>
                <button type="button" id="saveAllBtn"
                    class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5">Save
                    All</button>
            </div>
        </div>

        <!-- Add job status indicator -->
        <div id="jobStatus" class="hidden p-4 bg-gray-800 rounded-lg">
            <div class="flex items-center">
                <div role="status">
                    <svg aria-hidden="true"
                        class="inline w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600"
                        viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                            fill="currentColor" />
                        <path
                            d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                            fill="currentFill" />
                    </svg>
                    <span class="sr-only">Loading...</span>
                </div>
                <div class="ml-4">
                    <p class="text-white" id="statusMessage">Processing your video...</p>
                    <p class="text-gray-400 text-sm" id="jobIdDisplay">Job ID: <span id="currentJobId"></span></p>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center">
            <div>
                <button type="button" id="returnToUploadBtn"
                    class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">Return to Upload</button>
            </div>
            <div class="flex items-center">
                <h1 class="text-white text-xl mr-4">Search</h1>
                <input type="text" id="search"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-1/3 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                    placeholder="Search..." />
            </div>
        </div>

        <div class="flex-grow overflow-auto rounded-lg">
            <table class="w-full text-sm text-left text-gray-400 border-separate border-spacing-y-3">
                <thead class="text-xs text-gray-400 uppercase bg-black">
                    <tr>
                        <th scope="col" class="px-6 py-3">File Name</th>
                        <th scope="col" class="px-6 py-3 text-center">Total Songs</th>
                        <th scope="col" class="px-6 py-3 text-center">Total Duration</th>
                        <th scope="col" class="px-6 py-3 text-center">Start</th>
                        <th scope="col" class="px-6 py-3 text-center">End</th>
                        <th scope="col" class="px-6 py-3 text-center">Status</th>
                        <th scope="col" class="px-6 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="resultsTableBody">
                </tbody>
            </table>
        </div>

        <nav id="paginationControls" class="flex items-center justify-center pt-4" aria-label="Table navigation">
        </nav>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('fileInput');
            const dragArea = document.getElementById('dragArea');
            const browseBtn = document.getElementById('browseBtn');
            const mainVideo = document.getElementById('main-video');
            const songResults = document.getElementById('song-results');
            const jobStatus = document.getElementById('jobStatus');
            const statusMessage = document.getElementById('statusMessage');
            const jobIdDisplay = document.getElementById('currentJobId');
            const saveAllBtn = document.getElementById('saveAllBtn');
            const resultsTableBody = document.getElementById('resultsTableBody');
            const processUrlBtn = document.getElementById('processUrlBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const urlInput = document.getElementById('url');
            const returnToUploadBtn = document.getElementById('returnToUploadBtn');
            let currentProcessData = null;
            let currentSongResults = [];
            
            resultsTableBody.addEventListener('click', async (event) => {
                if (event.target.classList.contains('load-btn')) {
                    const row = event.target.closest('tr');
                    const jobId = row.getAttribute('data-job-id');
                    await loadDataFromDB(jobId);
                }

                else if (event.target.classList.contains('delete-btn')) {
                    const row = event.target.closest('tr');
                    if (row) {
                        const jobId = row.getAttribute('data-job-id');
                        deleteRow(row, jobId);
                    }
                }
            });

            // Trigger file input when the browse button is clicked
            browseBtn.onclick = () => {
                fileInput.click();
            };

            function loadSavedData() {
                const savedData = localStorage.getItem('videoProcessingData');
                if (savedData) {
                    const data = JSON.parse(savedData);
                    // Loop through all saved items without filtering
                    data.forEach(item => {
                        // Create and add the row for every item, including those "Processing"
                        const newRow = createTableRow(item);
                        resultsTableBody.prepend(newRow);
                    });
                }
            }

            // Get the search input element
            const searchInput = document.getElementById('search');

            // Add event listener for search input
            searchInput.addEventListener('input', function () {
                const searchText = this.value.toLowerCase();
                filterTable(searchText);
            });

            // Function to filter table based on search text
            function filterTable(searchText) {
                const rows = resultsTableBody.getElementsByTagName('tr');

                for (let i = 0; i < rows.length; i++) {
                    const fileNameCell = rows[i].getElementsByTagName('th')[0];
                    if (fileNameCell) {
                        const fileName = fileNameCell.textContent || fileNameCell.innerText;
                        if (fileName.toLowerCase().indexOf(searchText) > -1) {
                            rows[i].style.display = '';
                        } else {
                            rows[i].style.display = 'none';
                        }
                    }
                }
            }

           function createTableRow(data) {
                const newRow = document.createElement('tr');
                newRow.className = 'bg-black';
                newRow.setAttribute('data-job-id', data.id);

                let statusColorClass = '';
                if (data.status === 'Completed' || data.status === 'Success' || data.status === 'Saved') {
                    statusColorClass = 'text-green-400';
                } else if (data.status === 'Processing' || data.status === 'Uploading') {
                    statusColorClass = 'text-yellow-400';
                } else {
                    statusColorClass = 'text-red-400';
                }

                const isLoadDisabled = (data.status !== 'Completed' && data.status !== 'Saved') || data.totalSongs == 0;
                const loadClasses = isLoadDisabled ? 'text-gray-500' : 'hover:bg-gray-600 hover:text-white';

                newRow.innerHTML = `
                <th scope="row" class="px-6 py-4 font-medium text-white whitespace-nowrap bg-black rounded-l-lg">${data.fileName}</th>
                <td class="px-6 py-4 bg-black text-center">${data.totalSongs}</td>
                <td class="px-6 py-4 bg-black text-center">${formatDuration(data.totalDuration)}</td>
                <td class="px-6 py-4 bg-black text-center">${data.startTime}</td>
                <td class="px-6 py-4 bg-black text-center">${data.endTime}</td>
                <td class="px-6 py-4 bg-black text-center ${statusColorClass}">${data.status}</td>
                <td class="px-6 py-4 bg-black text-center rounded-r-lg">
                    <button id="action-menu-button-${data.id}" data-dropdown-toggle="action-menu-${data.id}" class="inline-flex items-center p-2 text-sm font-medium text-center text-gray-400 rounded-lg hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-700" type="button" 
                            aria-label="Action menu" title="Action menu">
                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 4 15">
                            <path d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/>
                        </svg>
                    </button>
                    <div id="action-menu-${data.id}" class="z-10 hidden bg-gray-700 divide-y divide-gray-600 rounded-lg shadow p-1">
                        <ul class="py-2 text-sm text-gray-200" aria-labelledby="action-menu-button-${data.id}">
                            <li>
                                <button class="load-btn block w-full px-1 text-center py-1 ${loadClasses}" ${isLoadDisabled ? 'disabled' : ''}>Load</button>
                            </li>
                            <li>
                                <button class="delete-btn block w-full px-1 py-1 text-center text-red-500 hover:bg-gray-600 hover:text-red-400">Delete</button>
                            </li>
                        </ul>
                    </div>
                </td>
            `;

                initFlowbite();

                return newRow;
            }


            // Call this function when the page loads
            loadSavedData();

            // Listen for a file to be selected
            fileInput.addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (file) {
                    handleFileUpload(file);
                }
            });

            // --- Drag and Drop Functionality (Optional but good UX) ---
            dragArea.addEventListener('dragover', (event) => {
                event.preventDefault();
                dragArea.classList.add('active');
            });
            dragArea.addEventListener('dragleave', () => {
                dragArea.classList.remove('active');
            });
            dragArea.addEventListener('drop', (event) => {
                event.preventDefault();
                const file = event.dataTransfer.files[0];
                if (file && file.type === "video/mp4") {
                    handleFileUpload(file);
                } else {
                    alert("Please drop an MP4 video file.");
                    dragArea.classList.remove('active');
                }
            });

            async function handleFileUpload(file) {
                const startTime = formatDateTime(new Date());
                const formData = new FormData();
                formData.append('video', file);

                try {
                    const response = await fetch("{{ route('video.process') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    if (!response.ok) {
                        throw new Error(`Server responded with ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        const jobId = data.job_id;

                        // --- THIS IS THE NEW LOGIC ---
                        let existingRow = document.querySelector(`tr[data-job-id="${jobId}"]`);

                        const rowData = {
                            id: jobId,
                            fileName: file.name,
                            totalSongs: 'Processing...',
                            totalDuration: 'Processing...',
                            startTime: startTime,
                            endTime: 'Processing...',
                            status: 'Processing'
                        };

                        if (existingRow) {
                            console.log(`Job ${jobId} already in table. Updating status.`);
                            updateRowInDOM(jobId, rowData);
                        } else {
                            console.log(`Adding new row for job ${jobId}.`);
                            const newRow = createTableRow(rowData);
                            resultsTableBody.prepend(newRow);
                        }

                        // This will now correctly "upsert" the data
                        saveToLocalStorage(rowData);

                    } else {
                        throw new Error(data.message || 'Failed to queue video');
                    }

                } catch (error) {
                    console.error('Upload failed:', error);
                    songResults.innerHTML = `<p class="text-red-400 text-center">Processing failed. Please try again.</p>`;
                }
            }


            async function checkAllProcessingJobs() {
                // 1. Get the source of truth from localStorage
                const allJobs = JSON.parse(localStorage.getItem('videoProcessingData')) || [];

                // 2. Find only the jobs that actually need checking
                const processingJobs = allJobs.filter(job => job.status === 'Processing');

                // 3. If there are no jobs to check, do nothing.
                if (processingJobs.length === 0) {
                    return;
                }

                // 4. Check each processing job
                for (const job of processingJobs) {
                    try {
                        const response = await fetch(`/job-status/${job.id}`);
                        const data = await response.json();

                        if (data.status === 'completed') {
                            const completionTime = formatDateTime(new Date());
                            const updates = {
                                status: 'Completed',
                                endTime: completionTime,
                                totalSongs: data.results.length,
                                results: data.results,
                                video_url: data.video_url,
                                totalDuration: data.duration
                            };  

                            // Update the data in both places: the DOM and localStorage
                            updateRowInDOM(job.id, updates);
                            updateLocalStorage(job.id, updates);

                            fetch(`/finalize-job/${job.id}`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), // Important for security
                                    'Accept': 'application/json'
                                }
                            })
                                .then(response => response.json())
                                .then(finalizeData => {
                                    console.log('Finalization response:', finalizeData);
                                })

                        } else if (data.status === 'failed') {
                            const updates = {
                                totalDuration: 'Failed',
                                totalSongs: 0,
                                status: 'Failed',
                                endTime: formatDateTime(new Date())
                            };
                            updateRowInDOM(job.id, updates);
                            updateLocalStorage(job.id, updates);
                        }

                    } catch (error) {
                        console.error(`Error checking job ${job.id} status:`, error);
                    }
                }
            }

            function updateRowInDOM(jobId, updates) {
                const targetRow = document.querySelector(`tr[data-job-id="${jobId}"]`);
                if (!targetRow) return; // Exit if the row isn't on the page

                if (updates.totalSongs !== undefined) targetRow.cells[1].textContent = updates.totalSongs;
                if (updates.endTime !== undefined) targetRow.cells[4].textContent = updates.endTime;
                if (updates.totalDuration !== undefined) {
                    targetRow.cells[2].textContent = formatDuration(updates.totalDuration);
                }
                if (updates.status !== undefined) {
                    targetRow.cells[5].textContent = updates.status;
                    let statusColorClass = 'text-red-400';
                    if (updates.status === 'Completed') statusColorClass = 'text-green-400';
                    else if (updates.status === 'Processing') statusColorClass = 'text-yellow-400';
                    targetRow.cells[5].className = `px-6 py-4 ${statusColorClass}`;
                }
            }

            setInterval(checkAllProcessingJobs, 3000);

            function updateTableRow(jobId, updates) {
                const rows = resultsTableBody.getElementsByTagName('tr');
                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const rowId = row.querySelector('th').textContent === jobId ? jobId : null;
                    
                    if (rowId === jobId) {
                        if (updates.totalSongs) {
                            row.cells[1].textContent = updates.totalSongs;
                        }
                        if (updates.totalDuration) {
                            row.cells[2].textContent = updates.totalDuration;
                        }
                        if (updates.endTime) {
                            row.cells[4].textContent = updates.endTime;
                        }
                        if (updates.status) {
                            row.cells[5].textContent = updates.status;
                            row.cells[5].className = `px-6 py-4 ${
                                updates.status === 'Completed' ? 'text-green-400' : 
                                updates.status === 'Processing' ? 'text-yellow-400' : 'text-red-400'
                            }`;
                        }
                        
                        updateLocalStorage(jobId, updates);
                        break;
                    }
                }
            }
            
            function updateLocalStorage(jobId, updates) {
                const existingData = JSON.parse(localStorage.getItem('videoProcessingData')) || [];
                const updatedData = existingData.map(item => {
                    if (item.id === jobId) {
                        return { ...item, ...updates };
                    }
                    return item;
                });
                localStorage.setItem('videoProcessingData', JSON.stringify(updatedData));
            }

            function timeToSeconds(timeStr) {
                if (!timeStr || typeof timeStr !== 'string') {
                    return 0;
                }
                const [minutes, seconds] = timeStr.split(/[:.]/).map(Number);
                if (isNaN(minutes) || isNaN(seconds)) {
                    return 0;
                }
                return (minutes * 60) + seconds;
            }

            function secondsToTime(totalSeconds) {
                if (isNaN(totalSeconds) || totalSeconds < 0) {
                    return '00:00';
                }
                const minutes = Math.floor(totalSeconds / 60);
                const seconds = totalSeconds % 60;

                const paddedMinutes = String(minutes).padStart(2, '0');
                const paddedSeconds = String(seconds).padStart(2, '0');

                return `${paddedMinutes}:${paddedSeconds}`;
            }

            function displaySongResults(results) {
                
                currentSongResults = results;
                const songResultsContainer = document.getElementById('song-results');

                songResultsContainer.innerHTML = '';

                if (results && results.length > 0) {
                    const table = document.createElement('table');
                    table.className = 'w-full text-sm text-left text-white';

                    table.innerHTML = `
            <thead class="text-xs text-gray-400">
                <tr>
                    <th scope="col" class="py-3 px-4 text-xs"><input type="checkbox" class="bg-gray-900 border-gray-600 rounded focus:ring-blue-600"></th>
                    <th scope="col" class="py-3 px-2 text-xs">No</th>
                    <th scope="col" class="py-3 px-6 text-xs">Song Title</th>
                    <th scope="col" class="py-3 px-6 text-xs">Start Time</th>
                    <th scope="col" class="py-3 px-6 text-xs">End Time</th>
                    <th scope="col" class="py-3 px-6 text-xs">Duration</th>
                    <th scope="col" class="py-3 px-6 text-xs"></th>
                </tr>
            </thead>
        `;

                    const tbody = document.createElement('tbody');

                    results.forEach((song, index) => {
                        const row = document.createElement('tr');
                        row.className = 'border-b border-gray-800 hover:bg-gray-800';

                        const timeParts = song.position ? song.position.split(' - ') : [];
                        let startTime = timeParts[0] || 'N/A'; 
                        if (startTime.includes('-')) {
                            startTime = '00:00';
                        }
                        const endTime = timeParts[1] || 'N/A';

                        const startSeconds = timeToSeconds(startTime);
                        const endSeconds = timeToSeconds(endTime);
                        const durationInSeconds = endSeconds - startSeconds;
                        const duration = secondsToTime(durationInSeconds);

                        row.innerHTML = `
                <td class="py-4 px-4"><input type="checkbox" data-index="${index}" class="bg-gray-900 border-gray-600 rounded song-checkbox focus:ring-blue-600"></td>
                <td class="py-4 px-2">${index + 1}</td>
                <td scope="row" class="py-4 px-6 font-medium whitespace-nowrap text-xs">
                    <div>${song.song || 'Unknown Song'}</div>
                    <div class="text-xs text-gray-400">${song.artist || 'Unknown Artist'}</div>
                </td>
                <td class="py-4 px-6 text-xs">${startTime || 'N/A'}</td>
                <td class="py-4 px-6 text-xs">${endTime || 'N/A'}</td>
                <td class="py-4 px-6 text-xs">${duration || 'N/A'}</td>
                <td class="py-4 px-6 text-xs text-center text-lg font-bold text-gray-400">
        <button class="song-detail-btn"
                data-cuesheetid="${song.szcuesheetid}"
                data-shitem="${song.shitem}">⋮</button>
    </td>
            `;
                        tbody.appendChild(row);
                    });

                    table.appendChild(tbody);
                    songResultsContainer.appendChild(table);

                } else {

                    songResultsContainer.innerHTML = `<p class="text-gray-400 text-center">No songs were recognized in this video.</p>`;
                }
            }

            returnToUploadBtn.addEventListener('click', () => {

                const videoContainer = document.getElementById('video-container');

                videoContainer.classList.add('hidden');
                mainVideo.pause();
                mainVideo.src = ''; 
                dragArea.classList.remove('hidden');
                songResults.innerHTML = `<p class="text-gray-400 text-center">Song recognition results will appear here...</p>`;
                urlInput.value = '';
            });

            // YouTube processing
            processUrlBtn.addEventListener('click', async () => {
                const youtubeUrl = urlInput.value.trim();
                if (!youtubeUrl) {
                    alert('Please enter a YouTube URL');
                    return;
                }
                if (!isValidYouTubeUrl(youtubeUrl)) {
                    alert('Please enter a valid YouTube URL');
                    return;
                }

                try {
                    const startTime = formatDateTime(new Date());
                    processUrlBtn.disabled = true;

                    const response = await fetch("{{ route('video.process.youtube') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ url: youtubeUrl }),
                    });

                    if (!response.ok) {
                        throw new Error(`Server responded with ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        const jobId = data.job_id;

                        // --- THIS IS THE NEW LOGIC ---
                        // Check if a row with this ID already exists
                        let existingRow = document.querySelector(`tr[data-job-id="${jobId}"]`);

                        const rowData = {
                            id: jobId,
                            fileName: 'YouTube Video',
                            totalSongs: 'Processing...',
                            totalDuration: 'Processing...',
                            startTime: startTime,
                            endTime: 'Processing...',
                            status: 'Processing'
                        };

                        if (existingRow) {
                            // If it exists, just update it
                            console.log(`Job ${jobId} already in table. Updating status.`);
                            updateRowInDOM(jobId, rowData);
                        } else {
                            // If it doesn't exist, create a new one
                            console.log(`Adding new row for job ${jobId}.`);
                            const newRow = createTableRow(rowData);
                            resultsTableBody.prepend(newRow);
                        }

                        // This will now correctly "upsert" the data
                        saveToLocalStorage(rowData);

                    } else {
                        throw new Error(data.message || 'Failed to queue YouTube video');
                    }

                } catch (error) {
                    console.error('YouTube processing failed:', error);
                    songResults.innerHTML = `<p class="text-red-400 text-center">Processing failed. Please try again.</p>`;
                } finally {
                    processUrlBtn.disabled = false;
                }
            });

            document.querySelectorAll('.song-detail-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const szcuesheetid = btn.getAttribute('data-cuesheetid');
                    const shitem = btn.getAttribute('data-shitem');
                    try {
                        const response = await fetch(`/cuesheet-item/${szcuesheetid}/${shitem}`);
                        if (!response.ok) throw new Error('Failed to fetch details');
                        const details = await response.json();

                        showSongDetailModal(details);
                    } catch (err) {
                        showSongDetailModal({ song_title: 'Not found', artist_name: '-', composer_name: '-' });
                    }
                });
            });

            function showSongDetailModal(details) {
                // Remove previous modal
                const oldModal = document.getElementById('song-detail-modal');
                if (oldModal) oldModal.remove();

                // Create modal
                const modal = document.createElement('div');
                modal.id = 'song-detail-modal';
                modal.className = 'fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-gray-800 text-white p-6 rounded-lg shadow-lg z-50';
                modal.innerHTML = `
        <h3 class="font-bold text-lg mb-2">Song Details</h3>
        <p><strong>Song Title:</strong> ${details.song_title || 'Unknown'}</p>
        <p><strong>Artist:</strong> ${details.artist_name || 'Unknown'}</p>
        <p><strong>Composer:</strong> ${details.composer_name || 'Unknown'}</p>
        <button class="mt-4 bg-blue-700 px-4 py-2 rounded" onclick="document.getElementById('song-detail-modal').remove()">Close</button>
    `;
                document.body.appendChild(modal);
            }


            // Cancel button
            cancelBtn.addEventListener('click', () => {
                urlInput.value = '';
                mainVideo.src = '';
                mainVideo.parentElement.classList.add('hidden');
                songResults.innerHTML = `<p class="text-gray-400 text-center">Song recognition results will appear here...</p>`;
                currentProcessData = null;
            });

            // Save All button
            saveAllBtn.addEventListener('click', () => {
                if (!currentProcessData) {
                    alert("There are no results to save. Please process a video first.");
                    return;
                }

                // Get all checked song checkboxes
                const checkedBoxes = document.querySelectorAll('.song-checkbox:checked');

                // If nothing is checked, optional: alert or handle
                if (checkedBoxes.length === 0) {
                    alert('Please select at least one song to save.');
                    return;
                }

                // Sum durations for checked songs only
                let totalDurationSeconds = 0;
                checkedBoxes.forEach(checkbox => {
                    const index = parseInt(checkbox.getAttribute('data-index'));
                    const song = currentSongResults[index];
                    if (song && song.position) {
                        const timeParts = song.position.split(' - ');
                        const startSeconds = timeToSeconds(timeParts[0]);
                        const endSeconds = timeToSeconds(timeParts[1]);
                        const duration = endSeconds - startSeconds;
                        totalDurationSeconds += duration > 0 ? duration : 0;
                    }
                });

                // Format the summed duration for display
                const formattedDuration = formatDuration(totalDurationSeconds);

                // Save data
                const endTime = new Date();
                const rowData = {
                    id: Date.now(),
                    fileName: currentProcessData.fileName, // Always set!
                    totalSongs: checkedBoxes.length,
                    totalDuration: totalDurationSeconds,   // Save actual seconds, format for table
                    startTime: formatDateTime(currentProcessData.startTime),
                    endTime: formatDateTime(endTime),
                    status: 'Saved',
                    results: currentSongResults,           // Optionally save results for later load
                    video_url: currentProcessData.video_url // If available
                };

                // Save to localStorage as you do now
                saveToLocalStorage(rowData);

                // Create and add the new row to the table
                const newRow = createTableRow(rowData); // Table should format duration with formatDuration
                resultsTableBody.prepend(newRow);

                // Clear temp data if needed
                currentProcessData = null;
                currentSongResults = [];
            });

            function saveToLocalStorage(data) {
                const existingData = JSON.parse(localStorage.getItem('videoProcessingData')) || [];

                const itemIndex = existingData.findIndex(item => item.id == data.id);

                if (itemIndex > -1) {
                    existingData[itemIndex] = { ...existingData[itemIndex], ...data };
                } else {
                    existingData.push(data);
                }

                localStorage.setItem('videoProcessingData', JSON.stringify(existingData));
            }

            function deleteRow(rowElement, id) {
                rowElement.remove();

                const existingData = JSON.parse(localStorage.getItem('videoProcessingData')) || [];
                const updatedData = existingData.filter(item => item.id != id); 
                localStorage.setItem('videoProcessingData', JSON.stringify(updatedData));
            }

            // Helper function to format seconds into HH:MM:SS
            function formatDuration(seconds) {
                return new Date(seconds * 1000).toISOString().substr(11, 8);
            }

            // Helper function to format a Date object into a readable string
            function formatDateTime(date) {
                // Example format: 26-Aug-2025 16:07
                return date.toLocaleString('en-GB', {
                    day: '2-digit', month: 'short', year: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                }).replace(/,/g, '');
            }

            function formatDuration(totalSeconds) {
                if (isNaN(totalSeconds) || totalSeconds < 0) {
                    return "00:00:00";
                }

                // Round down to the nearest whole number
                const seconds = Math.floor(totalSeconds);

                // Calculate hours, minutes, and remaining seconds
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const remainingSeconds = seconds % 60;

                // Pad each component with a leading zero if it's a single digit
                const formattedHours = String(hours).padStart(2, '0');
                const formattedMinutes = String(minutes).padStart(2, '0');
                const formattedSeconds = String(remainingSeconds).padStart(2, '0');

                return `${formattedHours}:${formattedMinutes}:${formattedSeconds}`;
            }

            // Helper function to format a Date object into a readable string
            function formatDateTime(date) {
                // Example format: 26-Aug-2025 16:07
                return date.toLocaleString('en-GB', {
                    day: '2-digit', month: 'short', year: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                }).replace(/,/g, '');
            }

            // Helper function to validate YouTube URLs
            function isValidYouTubeUrl(url) {
                const patterns = [
                    /^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.?be)\/.+$/,
                    /^https?:\/\/(www\.)?youtube\.com\/watch\?v=[\w-]+/,
                    /^https?:\/\/youtu\.be\/[\w-]+/,
                    /^https?:\/\/(www\.)?youtube\.com\/embed\/[\w-]+/
                ];

                return patterns.some(pattern => pattern.test(url));
            }

            // async function loadDataFromDB(jobId) {
            //     try {
            //         const response = await fetch(`/jobs/${jobId}`);
            //         if (!response.ok) {
            //             throw new Error('Failed to load data from database.');
            //         }

            //         const data = await response.json();

            //         // Hide drag area and show video player
            //         document.getElementById('dragArea').classList.add('hidden');
            //         document.getElementById('video-container').classList.remove('hidden');

            //         // Check the source and set the video URL
            //         const videoElement = document.getElementById('main-video');
            //         videoElement.src = `/stream/${data.filename}`;
            
            //         // Display the song results in the right pane
            //         displaySongResults(data.results ? JSON.parse(data.results) : []);

            //     } catch (error) {
            //         console.error('Error loading job data:', error);
            //         alert('Could not load job data. Please try again.');
            //     }
            // }

            async function loadDataFromDB(jobId) {
                try {
                    const response = await fetch(`/jobs/${jobId}`);
                    if (!response.ok) {
                        throw new Error('Failed to load data from database.');
                    }

                    const data = await response.json();

                    // Hide drag area and show video player
                    document.getElementById('dragArea').classList.add('hidden');
                    document.getElementById('video-container').classList.remove('hidden');
                    const videoElement = document.getElementById('main-video');

                    videoElement.src = `/stream/${data.filename}`;

                    currentProcessData = {
                        fileName: data.filename || 'YouTube Video',
                        startTime: data.start_time || new Date(), // use actual if available
                    };
                    currentSongResults = data.results || [];

                    // Display the song results in the right pane, no JSON.parse needed
                    displaySongResults(data.results || []);

                } catch (error) {
                    console.error('Error loading job data:', error);
                    // Use a custom modal or message box instead of alert()
                    const messageBox = document.createElement('div');
                    messageBox.className = 'fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-red-600 text-white p-4 rounded-lg shadow-lg z-50';
                    messageBox.textContent = 'Could not load job data. Please try again.';
                    document.body.appendChild(messageBox);
                    setTimeout(() => messageBox.remove(), 3000);
                }
            }


            function getYouTubeVideoId(url) {
                const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
                const match = url.match(regExp);
                if (match && match[2].length === 11) {
                    return match[2];
                }
                return null;
            }

        });
    </script>

</body>

</html>