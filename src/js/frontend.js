const requestQueue = [];
let queueIsRunning = false;
let isProcessing = false; // New flag to track processing status

const currentDomain = window.location.origin;
const wordpressApiBaseUrl = `${currentDomain}/wp-json/aios-populate/v1`;

function addToQueue(apiName, apiEndpoint, showReRunButton = true) {
    // reconstruct api url dynamically
    const apiUrl = `${wordpressApiBaseUrl}/${apiEndpoint}`;

    // date must be set here
    const data = {
        date: ''
    };

    const request = {
        apiName,
        apiUrl,
        data,
        status: 'On Queue',
        showReRunButton,
    };
    requestQueue.push(request);

    if (!queueIsRunning) {
        queueIsRunning = true;
        isProcessing = true; // Set processing flag
        processQueue();
    }

}

function processQueue() {
    if (requestQueue.length > 0) {
        const { apiName, apiUrl, data } = requestQueue[0];


        let currentDate = new Date().toLocaleString();

        const request = requestQueue.find(req => req.apiName === apiName);
        if (request) {
            request.data.date = currentDate;
        }

        updateStatus(apiName, 'Generating Please Wait...');

        
        // Validate and sanitize the apiUrl
        if (!apiUrl.startsWith(currentDomain)) {
            console.error('Invalid API URL:', apiUrl);
            updateStatus(apiName, 'Error: Invalid API URL');
            requestQueue.shift();
            processQueue();
            return;
        }

        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        })
            .then(response => response.json())
            .then(result => {
                // console.log(result);
                $date = '';

                if (result.date != false) {
                    $date = result.date;

                } else {
                    $date = new Date().toLocaleString();
                }

                updateStatus(apiName, result.message);
                updateDateComplete(apiName, $date);

                requestQueue.shift();
                processQueue();
            })
            .catch(error => {
                console.error(error);

                updateStatus(apiName, 'Error');

                requestQueue.shift();
                processQueue();
            });
    } else {
        queueIsRunning = false;
        isProcessing = false; // Reset processing flag
        showElementAfterAllRequestsComplete();
    }
}

function updateStatus(apiName, newStatus) {
    const statusElement = document.getElementById(`status_${apiName}`);
    if (statusElement) {
        statusElement.textContent = newStatus;
    }
}

function updateDateComplete(apiName, date) {

    const dateCompleteElement = document.getElementById(`dateComplete_${apiName}`);
    if (dateCompleteElement) {
        dateCompleteElement.textContent = date;

        // Update the request data with the completion date
        const request = requestQueue.find(req => req.apiName === apiName);
        if (request) {
            request.data.date = date;
        }
    }
}

function showElementAfterAllRequestsComplete() {
    const elementToShow = document.getElementById('visit-homepage');
    const elementText = document.getElementById('new-element');
 
    if (requestQueue.length === 0) {
        elementToShow.style.display = 'block';
        elementText.textContent = 'Your theme setup is already done. Please click the link below to proceed.';
    }
}



// Add an event listener for beforeunload
window.addEventListener('beforeunload', function (e) {
    if (isProcessing) {

        e.preventDefault();
        e.returnValue = 'There are pending requests. Are you sure you want to leave this page?';
    }
});

// Function to manually trigger re-run for a specific API
function reRun(apiName, apiUrl, data) {
    addToQueue(apiName, apiUrl);
    updateTable(); // Update the table after re-run
}

function updateTable() {
    const tableBody = document.getElementById('apiTableBody');

    
    requestQueue.forEach(({ apiName, status, dateComplete, showReRunButton, apiUrl, data }) => {


     
        let existingRow = document.getElementById(`row_${apiName}`);

        if (!existingRow) {
            const newRow = document.createElement('div');
            newRow.className = 'aios-installation__table--row';
            newRow.id = `row_${apiName}`;

            const cell1 = document.createElement('div');
            cell1.className = 'aios-installation__table--cell';
            cell1.textContent = apiName;
            newRow.appendChild(cell1);

            const cell2 = document.createElement('div');
            cell2.className = 'aios-installation__table--cell';
            cell2.id = `status_${apiName}`;
            cell2.textContent = status;
            newRow.appendChild(cell2);

            const cell3 = document.createElement('div');
            cell3.className = 'aios-installation__table--cell';
            cell3.id = `dateComplete_${apiName}`;
            cell3.textContent = dateComplete;
            newRow.appendChild(cell3);

            // const cell4 = document.createElement('div');
            // cell4.className = 'aios-installation__table--button';
            // if (showReRunButton) {
            //     const reRunButton = document.createElement('button');
            //     reRunButton.textContent = 'Re-run';
            //     reRunButton.onclick = () => reRun(apiName, apiUrl, data);
            //     cell4.appendChild(reRunButton);
            // }
            // newRow.appendChild(cell4);

            tableBody.appendChild(newRow);
        } else {
            const statusElement = document.getElementById(`status_${apiName}`);
            const dateCompleteElement = document.getElementById(`dateComplete_${apiName}`);

            if (statusElement) {
                statusElement.textContent = status;
            }

            if (dateCompleteElement) {
                dateCompleteElement.textContent = dateComplete;
            }
        }
    });

    showElementAfterAllRequestsComplete();
}

const apiRequests = [
    { name: 'Settings', endpoint: `settings`, showReRunButton: false },
    { name: 'Default Pages', endpoint: `initial-setup-pages`, showReRunButton: false },
    { name: 'Forms', endpoint: `form`, showReRunButton: false },
    { name: 'Page', endpoint: `page-populate`, showReRunButton: false },
    { name: 'Post', endpoint: `post-populate`, showReRunButton: false },
    { name: 'Testimonials', endpoint: `testimonials`, showReRunButton: false },
    { name: 'Communities', endpoint: `communities`, showReRunButton: false },
    { name: 'Agents', endpoint: `agents`, showReRunButton: false },
    { name: 'Listings', endpoint: `listings`, showReRunButton: false },
    { name: 'Buyers', endpoint: `roadmaps-buyers`, showReRunButton: false },
    { name: 'Sellers', endpoint: `roadmaps-sellers`, showReRunButton: false },
    { name: 'Financing', endpoint: `roadmaps-financing`, showReRunButton: false },
    { name: 'About and Contact', endpoint: `about-contact`, showReRunButton: false },
    { name: 'Slideshow', endpoint: `slider`, showReRunButton: false },
    { name: 'Menu', endpoint: `menu`, showReRunButton: false },
    { name: 'Widgets', endpoint: `widgets`, showReRunButton: true },
];

apiRequests.forEach(request => {
    addToQueue(request.name, request.endpoint, request.showReRunButton);
});

// Call updateTable after the initial requests are added
updateTable();