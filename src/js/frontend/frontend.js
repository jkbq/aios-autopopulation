const requestQueue = [];
let queueIsRunning = false;
let isProcessing = false; // New flag to track processing status

const currentDomain = window.location.origin;
const wordpressApiBaseUrl = `${currentDomain}/wp-json/aios-populate/v1`;

function addToQueue(apiName, apiUrl, data, showReRunButton = true) {
    const request = {
        apiName,
        apiUrl,
        data,
        status: 'On Queue',
        dateComplete: '',
        showReRunButton
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

        updateStatus(apiName, 'Running');

        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        })
            .then(response => response.json())
            .then(result => {
                console.log(result);

                updateStatus(apiName, result.message);
                updateDateComplete(apiName, result.date);

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
    }
}

function showElementAfterAllRequestsComplete() {
    const elementToShow = document.getElementById('visit-homepage'); // Replace with the actual ID of your element

    if (requestQueue.length === 0) {
        elementToShow.style.display = 'block'; // Change 'block' to your desired display property
    }
}

// Function to manually trigger re-run for a specific API
function reRun(apiName, apiUrl, data) {
    addToQueue(apiName, apiUrl, data);
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
    { name: 'Settings', url: `${wordpressApiBaseUrl}/settings`, data: { key: 'value1' }, showReRunButton: false },
    { name: 'Forms', url: `${wordpressApiBaseUrl}/form`, data: { key: 'value2' }, showReRunButton: false },
    { name: 'Pages', url: `${wordpressApiBaseUrl}/contents`, data: { key: 'value3' }, showReRunButton: false },
    { name: 'Roadmaps', url: `${wordpressApiBaseUrl}/roadmaps`, data: { key: 'value4' }, showReRunButton: false },
    { name: 'Slideshow', url: `${wordpressApiBaseUrl}/slider`, data: { key: 'value5' }, showReRunButton: false },
    { name: 'Menu', url: `${wordpressApiBaseUrl}/menu`, data: { key: 'value6' }, showReRunButton: false },
    { name: 'Widgets', url: `${wordpressApiBaseUrl}/widgets`, data: { key: 'value7' }, showReRunButton: true },
];

apiRequests.forEach(request => {
    addToQueue(request.name, request.url, request.data, request.showReRunButton);
});

// Call updateTable after the initial requests are added
updateTable();