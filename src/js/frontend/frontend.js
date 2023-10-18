// JavaScript code here
const requestQueue = [];
let queueIsRunning = false;

const currentDomain = window.location.origin;
const wordpressApiBaseUrl = `${currentDomain}/wp-json/aios-populate/v1`;

// const apiCredentials = {
//     apiKey: 'your_api_key', // Replace with your actual API key
//     // Add any other authentication details here
// };


function addToQueue(apiName, apiUrl, data, showReRunButton = true) {
    const request = {
        apiName,
        apiUrl,
        data,
        status: 'Pending',
        dateComplete: '',
        showReRunButton
    };
    requestQueue.push(request);

    if (!queueIsRunning) {
        queueIsRunning = true;
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
                // 'X-Custom-API-Key': apiCredentials.apiKey,
            },
            body: JSON.stringify(data),
        })
            .then(response => response.json())
            .then(result => {
                console.log(result);

                updateStatus(apiName, 'Complete');
                updateDateComplete(apiName, new Date().toLocaleString());

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
        showElementAfterAllRequestsComplete(); // New addition
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
            newRow.className = 'table-row';
            newRow.id = `row_${apiName}`;

            const cell1 = document.createElement('div');
            cell1.className = 'table-cell';
            cell1.textContent = apiName;
            newRow.appendChild(cell1);

            const cell2 = document.createElement('div');
            cell2.className = 'table-cell';
            cell2.id = `status_${apiName}`;
            cell2.textContent = status;
            newRow.appendChild(cell2);

            const cell3 = document.createElement('div');
            cell3.className = 'table-cell';
            cell3.id = `dateComplete_${apiName}`;
            cell3.textContent = dateComplete;
            newRow.appendChild(cell3);
            
            const cell4 = document.createElement('div');
            cell4.className = 'table-button';
            if (showReRunButton) {
                const reRunButton = document.createElement('button');
                reRunButton.textContent = 'Re-run';
                reRunButton.onclick = () => reRun(apiName, apiUrl, data);
                cell4.appendChild(reRunButton);
            }
            newRow.appendChild(cell4);

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

    showElementAfterAllRequestsComplete(); // New addition
}

// Trigger initial requests on page load
addToQueue('Settings', `${wordpressApiBaseUrl}/settings`, { key: 'value1' }, false);
addToQueue('Forms', `${wordpressApiBaseUrl}/settings`, { key: 'value2' }, false);
addToQueue('Pages', `${wordpressApiBaseUrl}/settings`, { key: 'value3' }, false);
addToQueue('Roadmaps', `${wordpressApiBaseUrl}/settings`, { key: 'value4' }, false);
addToQueue('Slideshow', `${wordpressApiBaseUrl}/settings`, { key: 'value5' }, false);
addToQueue('Menu', `${wordpressApiBaseUrl}/settings`, { key: 'value6' }, false);
addToQueue('Widgets', `${wordpressApiBaseUrl}/settings`, { key: 'value7' }, true);

// Call updateTable after the initial requests are added
updateTable();