(function(){

const requestQueue = [];
let queueIsRunning = false;
let isProcessing = false;
let completedSteps = 0;
const totalSteps = 17;
let startTime = null;
let elapsedInterval = null;

const currentDomain = window.location.origin;
const wordpressApiBaseUrl = `${currentDomain}/wp-json/aios-populate/v1`;

function addToQueue(apiName, apiEndpoint, showReRunButton = true) {
    const apiUrl = `${wordpressApiBaseUrl}/${apiEndpoint}`;
    const data = { date: '' };
    const request = { apiName, apiUrl, data, status: 'On Queue', showReRunButton };
    requestQueue.push(request);

    if (!queueIsRunning) {
        queueIsRunning = true;
        isProcessing = true;
        if (!startTime) {
            startTime = Date.now();
            elapsedInterval = setInterval(() => {
                const secs = Math.floor((Date.now() - startTime) / 1000);
                const el = document.getElementById('aios-elapsed');
                if (el) el.textContent = `${Math.floor(secs / 60)}m ${secs % 60}s elapsed`;
            }, 1000);
        }
        processQueue();
    }
}

function processQueue() {
    if (requestQueue.length > 0) {
        const { apiName, apiUrl, data } = requestQueue[0];

        let currentDate = new Date().toLocaleString();
        const request = requestQueue.find(req => req.apiName === apiName);
        if (request) request.data.date = currentDate;

        updateCurrentStep(apiName);
        updateStatus(apiName, 'Generating Please Wait...');

        if (!apiUrl.startsWith(currentDomain)) {
            console.error('Invalid API URL:', apiUrl);
            updateStatus(apiName, 'Error: Invalid API URL');
            requestQueue.shift();
            processQueue();
            return;
        }

        const apiEndpoint = apiUrl.replace(wordpressApiBaseUrl + '/', '');

        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-AIOS-Token': (typeof aiosFrontendData !== 'undefined' && aiosFrontendData.installToken) ? aiosFrontendData.installToken : '',
            },
            body: JSON.stringify(data),
        })
        .then(response => response.json())
        .then(result => {
            let $date = result.date ? result.date : new Date().toLocaleString();
            updateStatus(apiName, result.message || 'Done');
            updateDateComplete(apiName, $date);
            completedSteps++;
            updateProgress(completedSteps, totalSteps);
            requestQueue.shift();
            processQueue();
        })
        .catch(error => {
            console.error(error);
            updateStatus(apiName, 'Error', apiName, apiEndpoint);
            requestQueue.shift();
            processQueue();
        });
    } else {
        queueIsRunning = false;
        isProcessing = false;
        showElementAfterAllRequestsComplete();
    }
}

function getStatusIcon(status) {
    if (status === 'Generating Please Wait...') return '<span class="aios-icon--spinner"></span>';
    if (status === 'Error' || status.startsWith('Error:')) return '✗';
    if (status === 'On Queue') return '–';
    return '✓';
}

function updateCurrentStep(name) {
    const banner = document.getElementById('aios-current-step');
    const nameEl = document.getElementById('aios-current-step-name');
    if (banner) banner.style.display = 'flex';
    if (nameEl) {
        const step = apiRequests.find(r => r.name === name);
        nameEl.textContent = step ? step.description : name + '...';
        nameEl.classList.remove('aios-step-name-enter');
        void nameEl.offsetWidth;
        nameEl.classList.add('aios-step-name-enter');
    }
}

function updateProgress(completed, total) {
    const pct = Math.round((completed / total) * 100);
    const fill = document.getElementById('aios-progress-fill');
    const label = document.getElementById('aios-progress-label');
    if (fill)  fill.style.width = pct + '%';
    if (label) label.textContent = `${completed} of ${total} complete`;
}

function updateStatus(apiName, newStatus, retryApiName, retryEndpoint) {
    const chip = document.getElementById(`row_${apiName}`);
    const statusEl = document.getElementById(`status_${apiName}`);
    if (!statusEl) return;

    if (chip) {
        chip.classList.remove('is-active', 'is-done', 'is-error');
        if (newStatus === 'Generating Please Wait...')           chip.classList.add('is-active');
        else if (newStatus === 'Error' || newStatus.startsWith('Error:')) chip.classList.add('is-error');
        else if (newStatus !== 'On Queue')                       chip.classList.add('is-done');
    }

    statusEl.innerHTML = getStatusIcon(newStatus);

    if (retryApiName && (newStatus === 'Error' || newStatus.startsWith('Error:'))) {
        const retryBtn = document.createElement('button');
        retryBtn.textContent = 'Retry';
        retryBtn.className = 'aios-retry-btn';
        retryBtn.onclick = () => { retryBtn.remove(); addToQueue(retryApiName, retryEndpoint); };
        if (chip) chip.appendChild(retryBtn);
        else statusEl.appendChild(retryBtn);
    }
}

function updateDateComplete(apiName, date) {
    const chip = document.getElementById(`row_${apiName}`);
    if (chip) chip.dataset.date = date;
    const request = requestQueue.find(req => req.apiName === apiName);
    if (request) request.data.date = date;
}

function showElementAfterAllRequestsComplete() {
    const elementToShow = document.getElementById('visit-homepage');
    const elementText   = document.querySelector('.textAlert');
    const stepsGrid     = document.getElementById('aios-steps');
    const banner        = document.getElementById('aios-current-step');
    const newElement    = document.querySelector('#new-element');

    if (requestQueue.length === 0) {
        clearInterval(elapsedInterval);
        updateProgress(totalSteps, totalSteps);

        if (stepsGrid)  stepsGrid.style.display  = 'none';
        if (banner)     banner.style.display      = 'none';
        if (newElement) newElement.style.display  = 'none';
        elementToShow.style.display = 'block';
        elementText.textContent = 'You will be redirected to the homepage automatically in 30 seconds.';

        let countdown = 30;
        const interval = setInterval(() => {
            countdown--;
            if (countdown > 0) {
                elementText.textContent = `You will be redirected to the homepage automatically in ${countdown} seconds.`;
            } else {
                clearInterval(interval);
            }
        }, 1000);

        setTimeout(function() {
            const newUrl = window.location.origin + window.location.pathname.replace(/\/aios-installation.*$/, '');
            window.location.href = newUrl;
        }, 30000);
    }
}

window.addEventListener('beforeunload', function (e) {
    if (isProcessing) {
        e.preventDefault();
        e.returnValue = 'There are pending requests. Are you sure you want to leave this page?';
    }
});

window.addEventListener('unload', function () {
    if (isProcessing) {
        console.log('User exited the page while requests were still processing.');
    }
});

function initSteps() {
    const container = document.getElementById('aios-steps');
    if (!container) return;

    apiRequests.forEach(({ name }, index) => {
        const chip = document.createElement('div');
        chip.className = 'aios-step-chip';
        chip.id = `row_${name}`;
        chip.style.animationDelay = (index * 40) + 'ms';
        chip.addEventListener('animationend', () => {
            chip.style.animationDelay = '';
        }, { once: true });

        const iconEl = document.createElement('span');
        iconEl.className = 'aios-step-chip__icon';
        iconEl.id = `status_${name}`;
        iconEl.textContent = '–';

        const nameEl = document.createElement('span');
        nameEl.className = 'aios-step-chip__name';
        nameEl.textContent = name;

        chip.appendChild(iconEl);
        chip.appendChild(nameEl);
        container.appendChild(chip);
    });
}

const apiRequests = [
    { name: 'Settings',               endpoint: 'settings',            showReRunButton: false, description: 'Applying theme settings...' },
    { name: 'Default Pages',          endpoint: 'initial-setup-pages', showReRunButton: false, description: 'Creating default pages...' },
    { name: 'Forms',                  endpoint: 'form',                showReRunButton: false, description: 'Setting up contact forms...' },
    { name: 'Page',                   endpoint: 'page-populate',       showReRunButton: false, description: 'Populating site pages...' },
    { name: 'Post',                   endpoint: 'post-populate',       showReRunButton: false, description: 'Migrating blog posts...' },
    { name: 'Buyers',                 endpoint: 'roadmaps-buyers',     showReRunButton: false, description: 'Building buyer roadmaps...' },
    { name: 'Sellers',                endpoint: 'roadmaps-sellers',    showReRunButton: false, description: 'Building seller roadmaps...' },
    { name: 'Financing',              endpoint: 'roadmaps-financing',  showReRunButton: false, description: 'Building financing roadmaps...' },
    { name: 'About and Contact',      endpoint: 'about-contact',       showReRunButton: false, description: 'Populating about & contact...' },
    { name: 'Slideshow',              endpoint: 'slider',              showReRunButton: false, description: 'Configuring slideshow...' },
    { name: 'Menu',                   endpoint: 'menu',                showReRunButton: false, description: 'Building navigation menus...' },
    { name: 'Widgets',                endpoint: 'widgets',             showReRunButton: true,  description: 'Configuring sidebar widgets...' },
    { name: 'Finalizing Installation',endpoint: 'deactivate',          showReRunButton: true,  description: 'Finalizing your installation...' },
];

initSteps();

apiRequests.forEach(request => {
    addToQueue(request.name, request.endpoint, request.showReRunButton);
});

})();
