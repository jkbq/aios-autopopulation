document.addEventListener('DOMContentLoaded', function () {

    const currentDomain = window.location.origin;
    const nonce = (typeof data !== 'undefined' && data.nonce) ? data.nonce : '';

    // ── Main "Generate All" button ────────────────────────────────────────────
    const fetchDataButton = document.querySelector('.aios-repopulate-widgets');

    if (fetchDataButton) {
        fetchDataButton.addEventListener('click', function (e) {

            e.preventDefault();

            const swalWithBootstrapButtons = Swal.mixin({
                customClass: {
                    confirmButton: "btn btn-success",
                    cancelButton: "btn btn-danger"
                },
                buttonsStyling: false
            });
            swalWithBootstrapButtons.fire({
                title: "Are you sure?",
                text: "Doing This will Delete Form, Current Menu and Slideshow",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Proceed!",
                cancelButtonText: "Cancel!",
                reverseButtons: true,
                allowOutsideClick: false,
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "Please wait for as we setup your theme ",
                        html: `<div class="lds-facebook"><div></div><div></div><div></div></div><a href="${currentDomain}" class="wpui-secondary-button text-uppercase auto-populate-api-status">Visit Homepage</a>`,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        didOpen: () => {

                            window.addEventListener('beforeunload', function (e) {
                                e.preventDefault();
                                e.returnValue = 'There are pending requests. Are you sure you want to leave this page?';
                            });

                            const fetchData = async () => {
                                const url1 = `${currentDomain}/wp-json/aios-populate/v1/widgets`;
                                const url2 = `${currentDomain}/wp-json/aios-populate/v1/form`;
                                const url3 = `${currentDomain}/wp-json/aios-populate/v1/menu`;
                                const url4 = `${currentDomain}/wp-json/aios-populate/v1/regeneratecontents`;

                                const headers = {
                                    'Content-Type': 'application/json',
                                    'X-WP-Nonce': nonce,
                                };

                                const postData = {
                                    repopulate: true,
                                    date: new Date().toLocaleString()
                                };

                                try {
                                    const requests = [url1, url2, url3, url4].map(url =>
                                        fetch(url, {
                                            method: 'POST',
                                            headers,
                                            body: JSON.stringify(postData),
                                        })
                                    );

                                    const responses = await Promise.all(requests);
                                    const dataPromises = responses.map(response => response.json());
                                    const results = await Promise.all(dataPromises);

                                    $buttonStatus = Swal.getPopup().querySelector(".auto-populate-api-status");
                                    $loader = Swal.getPopup().querySelector(".lds-facebook");
                                    $title = Swal.getPopup().querySelector(".swal2-title");
                                    $loader.style.display = "none";
                                    $title.textContent = 'Your theme setup is already done. Please click the link below to proceed.';
                                    $buttonStatus.style.display = "inline-block";

                                    // Poll /status every 5s to refresh the Logs tab
                                    const statusUrl = `${currentDomain}/wp-json/aios-populate/v1/status`;
                                    const poll = setInterval(async () => {
                                        try {
                                            const res = await fetch(statusUrl, {
                                                headers: { 'X-WP-Nonce': nonce },
                                            });
                                            const statuses = await res.json();
                                            let allDone = true;
                                            Object.entries(statuses).forEach(([key, api]) => {
                                                const slug = key.toLowerCase().replace(/\s+/g, '-');
                                                const statusCell = document.querySelector(`[data-status-cell="${slug}"]`);
                                                const dateCell   = document.querySelector(`[data-date-cell="${slug}"]`);
                                                if (statusCell) statusCell.querySelector('strong').textContent = api.status ? 'Generated' : '';
                                                if (dateCell)   dateCell.querySelector('strong').textContent   = api.date || '';
                                                if (!api.status) allDone = false;
                                            });
                                            if (allDone) clearInterval(poll);
                                        } catch (e) {
                                            console.error('Status poll error:', e);
                                        }
                                    }, 5000);

                                } catch (error) {
                                    console.error('Error fetching data:', error);
                                }
                            };

                            fetchData();
                        },
                    });

                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    swalWithBootstrapButtons.fire({
                        title: "Cancelled",
                        icon: "error"
                    });
                }
            });
        });
    }

    // ── Per-route Repopulate buttons ──────────────────────────────────────────
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.aios-repopulate-route-btn');
        if (!btn) return;

        e.preventDefault();

        const endpoint = btn.dataset.endpoint;
        const name     = btn.dataset.name;
        const slug     = btn.dataset.slug;

        Swal.fire({
            title: 'Repopulate ' + name + '?',
            text: 'This will overwrite existing data for this section.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, repopulate',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-danger',
            },
            buttonsStyling: false,
        }).then(function (result) {
            if (!result.isConfirmed) return;

            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Working…';

            fetch(currentDomain + '/wp-json/aios-populate/v1/' + endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce,
                },
                body: JSON.stringify({ repopulate: true, date: new Date().toLocaleString() }),
            })
            .then(function (r) { return r.json(); })
            .then(function (response) {
                btn.disabled    = false;
                btn.textContent = originalText;

                // Update status badge in the Repopulate tab
                const repopStatus = document.querySelector('[data-repop-status="' + slug + '"]');
                if (repopStatus) {
                    repopStatus.textContent = 'Generated';
                    repopStatus.classList.add('is-generated');
                }

                // Mirror update into the Logs tab
                const statusCell = document.querySelector('[data-status-cell="' + slug + '"]');
                const dateCell   = document.querySelector('[data-date-cell="' + slug + '"]');
                if (statusCell) statusCell.querySelector('strong').textContent = 'Generated';
                if (dateCell)   dateCell.querySelector('strong').textContent   = response.date || new Date().toLocaleString();

                Swal.fire({
                    title: name + ' repopulated!',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                });
            })
            .catch(function (err) {
                btn.disabled    = false;
                btn.textContent = originalText;
                console.error('Repopulate error:', err);
                Swal.fire({
                    title: 'Error',
                    text: 'Repopulation failed. Check the browser console for details.',
                    icon: 'error',
                });
            });
        });
    });
});
