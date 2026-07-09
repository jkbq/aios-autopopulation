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
                title: "Apply theme setup?",
                text: "This will update sidebar widgets, plugin theme settings, and About/Contact templates for the new theme. Menus, forms, and existing content will not be changed.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Proceed!",
                cancelButtonText: "Cancel!",
                reverseButtons: true,
                allowOutsideClick: false,
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "Please wait while we apply your theme setup",
                        html: `<div class="lds-facebook"><div></div><div></div><div></div></div><a href="${currentDomain}" class="wpui-secondary-button text-uppercase auto-populate-api-status">Visit Homepage</a>`,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        didOpen: () => {

                            window.addEventListener('beforeunload', function (e) {
                                e.preventDefault();
                                e.returnValue = 'There are pending requests. Are you sure you want to leave this page?';
                            });

                            const fetchData = async () => {
                                const themeSetupUrl = `${currentDomain}/wp-json/aios-populate/v1/theme-setup`;
                                const statusUrl = `${currentDomain}/wp-json/aios-populate/v1/status`;

                                const headers = {
                                    'Content-Type': 'application/json',
                                    'X-WP-Nonce': nonce,
                                };

                                const postData = {
                                    date: new Date().toLocaleString()
                                };

                                try {
                                    const response = await fetch(themeSetupUrl, {
                                        method: 'POST',
                                        headers,
                                        body: JSON.stringify(postData),
                                    });

                                    const result = await response.json();

                                    $buttonStatus = Swal.getPopup().querySelector(".auto-populate-api-status");
                                    $loader = Swal.getPopup().querySelector(".lds-facebook");
                                    $title = Swal.getPopup().querySelector(".swal2-title");
                                    $loader.style.display = "none";
                                    $title.textContent = result.message || 'Theme setup applied successfully.';
                                    $buttonStatus.style.display = "inline-block";

                                    const poll = setInterval(async () => {
                                        try {
                                            const res = await fetch(statusUrl, {
                                                headers: { 'X-WP-Nonce': nonce },
                                            });
                                            const statuses = await res.json();
                                            Object.entries(statuses).forEach(([key, api]) => {
                                                const slug = key.toLowerCase().replace(/\s+/g, '-');
                                                const statusCell = document.querySelector(`[data-status-cell="${slug}"]`);
                                                const dateCell   = document.querySelector(`[data-date-cell="${slug}"]`);
                                                const repopStatus = document.querySelector(`[data-repop-status="${slug}"]`);
                                                if (statusCell) statusCell.querySelector('strong').textContent = api.status ? 'Generated' : '';
                                                if (dateCell)   dateCell.querySelector('strong').textContent   = api.date || '';
                                                if (repopStatus && api.status) {
                                                    repopStatus.textContent = 'Generated';
                                                    repopStatus.classList.add('is-generated');
                                                }
                                            });
                                            clearInterval(poll);
                                        } catch (e) {
                                            console.error('Status poll error:', e);
                                        }
                                    }, 2000);

                                    setTimeout(() => location.reload(), 2500);

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

    function updateCannedUi(section, slug) {
        if (section === 'all') {
            document.querySelectorAll('.aios-delete-canned-btn').forEach(function (btn) {
                btn.disabled = true;
            });
            document.querySelectorAll('[data-canned-count]').forEach(function (el) {
                el.textContent = '0 item(s)';
            });
            const toolbarInfo = document.querySelector('.aios-repopulate-toolbar__info strong');
            if (toolbarInfo) toolbarInfo.textContent = '0';
            return;
        }

        const btn = document.querySelector('.aios-delete-canned-btn[data-section="' + section + '"]');
        if (btn) {
            btn.disabled = true;
        }

        const sectionCount = document.querySelector('[data-canned-count="' + section + '"]');
        if (sectionCount) {
            sectionCount.textContent = '0 item(s)';
        }

        let total = 0;
        document.querySelectorAll('[data-canned-count]').forEach(function (item) {
            const match = item.textContent.match(/(\d+)/);
            if (match) total += parseInt(match[1], 10);
        });
        const toolbarInfo = document.querySelector('.aios-repopulate-toolbar__info strong');
        if (toolbarInfo) toolbarInfo.textContent = String(total);
        const allBtn = document.querySelector('.aios-delete-all-btn');
        if (allBtn) allBtn.disabled = total === 0;

        if (slug) {
            const repopStatus = document.querySelector('[data-repop-status="' + slug + '"]');
            if (repopStatus) {
                repopStatus.textContent = '—';
                repopStatus.classList.remove('is-generated');
            }

            const statusCell = document.querySelector('[data-status-cell="' + slug + '"]');
            const dateCell   = document.querySelector('[data-date-cell="' + slug + '"]');
            if (statusCell) statusCell.querySelector('strong').textContent = '';
            if (dateCell)   dateCell.querySelector('strong').textContent   = '';
        }
    }

    // ── Delete canned content buttons ─────────────────────────────────────────
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.aios-delete-canned-btn');
        if (!btn) return;

        e.preventDefault();

        const section = btn.dataset.section;
        const name    = btn.dataset.name;
        const slug    = btn.dataset.slug || '';

        Swal.fire({
            title: 'Delete ' + name + '?',
            text: 'This will permanently remove tracked canned content for this section.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-success',
            },
            buttonsStyling: false,
        }).then(function (result) {
            if (!result.isConfirmed) return;

            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Deleting…';

            fetch(currentDomain + '/wp-json/aios-populate/v1/delete-canned-content', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce,
                },
                body: JSON.stringify({ section: section }),
            })
            .then(function (r) { return r.json(); })
            .then(function (response) {
                btn.textContent = originalText;

                if (section === 'all') {
                    updateCannedUi('all');
                    document.querySelectorAll('.aios-delete-canned-btn[data-slug]').forEach(function (item) {
                        updateCannedUi(item.dataset.section, item.dataset.slug);
                    });
                } else {
                    updateCannedUi(section, slug);
                }

                Swal.fire({
                    title: name + ' deleted',
                    text: response.message || 'Canned content removed.',
                    icon: 'success',
                    timer: 2500,
                    showConfirmButton: false,
                });
            })
            .catch(function (err) {
                btn.disabled = false;
                btn.textContent = originalText;
                console.error('Delete canned content error:', err);
                Swal.fire({
                    title: 'Error',
                    text: 'Delete failed. Check the browser console for details.',
                    icon: 'error',
                });
            });
        });
    });
});
