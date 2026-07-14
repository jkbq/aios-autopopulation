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
            text: 'Unmodified canned content will be replaced. Edited items will be kept.',
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

                refreshCannedCounts();

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

    function refreshCannedCounts() {
        return fetch(currentDomain + '/wp-json/aios-populate/v1/canned-content-counts', {
            headers: { 'X-WP-Nonce': nonce },
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            const unmodified = data.unmodified || 0;
            const edited = data.edited || 0;
            const sections = data.sections || {};

            const toolbarCount = document.querySelector('[data-canned-count="all"]');
            if (toolbarCount) toolbarCount.textContent = String(unmodified);

            const editedTotalEl = document.querySelector('[data-canned-edited-total]');
            if (editedTotalEl) {
                editedTotalEl.textContent = edited > 0 ? ' · ' + edited + ' edited' : '';
            }

            const allBtn = document.querySelector('.aios-delete-all-btn');
            if (allBtn) {
                allBtn.disabled = unmodified <= 0;
            }

            Object.keys(sections).forEach(function (slug) {
                const info = sections[slug];
                const countEl = document.querySelector('[data-canned-count="' + slug + '"]');
                if (countEl) countEl.textContent = String(info.unmodified || 0);

                const editedEl = document.querySelector('[data-canned-edited="' + slug + '"]');
                if (editedEl) editedEl.textContent = String(info.edited || 0);

                const deleteBtn = document.querySelector('.aios-delete-canned-btn[data-section="' + slug + '"]');
                if (deleteBtn) deleteBtn.disabled = !info.can_delete;
            });

            return data;
        });
    }

    // Live re-check while Manage Content is open (pauses when the tab is hidden).
    if (document.querySelector('.aios-manage-table')) {
        const CANNED_POLL_MS = 10000;
        let cannedPollTimer = null;

        function startCannedPoll() {
            if (cannedPollTimer || document.hidden) return;
            cannedPollTimer = setInterval(function () {
                if (!document.hidden) refreshCannedCounts();
            }, CANNED_POLL_MS);
        }

        function stopCannedPoll() {
            if (!cannedPollTimer) return;
            clearInterval(cannedPollTimer);
            cannedPollTimer = null;
        }

        refreshCannedCounts();
        startCannedPoll();

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                stopCannedPoll();
            } else {
                refreshCannedCounts();
                startCannedPoll();
            }
        });
    }

    function applyCannedDeleteUi(section, slug, hasEdited) {
        const deleteBtn = document.querySelector('.aios-delete-canned-btn[data-section="' + section + '"]');
        if (deleteBtn) deleteBtn.disabled = true;

        const sectionCount = document.querySelector('[data-canned-count="' + section + '"]');
        if (sectionCount) sectionCount.textContent = '0';

        if (slug && !hasEdited) {
            const editedEl = document.querySelector('[data-canned-edited="' + section + '"]');
            if (editedEl) editedEl.textContent = '0';

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

        const confirmTitle = section === 'all'
            ? 'Delete unmodified canned content?'
            : 'Delete unmodified ' + name + '?';

        Swal.fire({
            title: confirmTitle,
            text: 'Only unmodified items will be removed. Edited content will be retained.',
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
                    document.querySelectorAll('.aios-delete-canned-btn[data-slug]').forEach(function (item) {
                        applyCannedDeleteUi(item.dataset.section, item.dataset.slug, (response.edited || 0) > 0);
                    });
                    const toolbarCount = document.querySelector('[data-canned-count="all"]');
                    if (toolbarCount) toolbarCount.textContent = '0';
                    const allBtn = document.querySelector('.aios-delete-all-btn');
                    if (allBtn) allBtn.disabled = true;
                } else {
                    applyCannedDeleteUi(section, slug, (response.edited || 0) > 0);
                }

                refreshCannedCounts();

                Swal.fire({
                    title: 'Deleted',
                    text: response.message || 'Unmodified canned content was removed. Edited items were kept.',
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
