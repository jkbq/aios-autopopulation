document.addEventListener('DOMContentLoaded', function () {

    const currentDomain = window.location.origin;
    const apiUrl = `${currentDomain}/wp-json/aios-populate/v1/widgets`;

    // Select elements by class name
    const fetchDataButton = document.querySelector('.aios-repopulate-widgets');

    // Data to be sent in the request
    const postData = {
        repopulate: true,
        date: new Date().toLocaleString()
    };


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

                        // Add an event listener for beforeunload
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
                                'X-WP-Nonce': (typeof data !== 'undefined' && data.nonce) ? data.nonce : '',
                            };

                            const postData = {

                                repopulate: true,
                                date: new Date().toLocaleString()
                            };

                            try {
                                const requests = [url1, url2, url3, url4].map(url =>
                                    fetch(url, {
                                        method: 'POST', // Adjust the method as needed (GET, POST, etc.)
                                        headers,
                                        body: JSON.stringify(postData),
                                    })
                                );

                                const responses = await Promise.all(requests);

                                // Process the responses
                                const dataPromises = responses.map(response => response.json());
                                const results = await Promise.all(dataPromises);

                                $buttonStatus = Swal.getPopup().querySelector(".auto-populate-api-status");
                                $loader = Swal.getPopup().querySelector(".lds-facebook");
                                $title = Swal.getPopup().querySelector(".swal2-title");
                                $loader.style.display = "none";
                                $title.textContent = 'Your theme setup is already done. Please click the link below to proceed.';
                                $buttonStatus.style.display = "inline-block";

                                // Poll /status every 5 s to refresh the Logs tab in real time
                                const statusUrl = `${currentDomain}/wp-json/aios-populate/v1/status`;
                                const nonce = (typeof data !== 'undefined' && data.nonce) ? data.nonce : '';
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

                                // Continue with further processing or UI updates
                            } catch (error) {
                                console.error('Error fetching data:', error);
                            }
                        };

                        // Call the function to initiate the fetching process
                        fetchData();

         

                    },
                });

            } else if (
                /* Read more about handling dismissals below */
                result.dismiss === Swal.DismissReason.cancel
            ) {
                swalWithBootstrapButtons.fire({
                    title: "Cancelled",
                    icon: "error"
                });
            }
        });




    });
});