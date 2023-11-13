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
            confirmButtonText: "Yes, Proceed!",
            cancelButtonText: "No, cancel!",
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
                                // Add any other custom headers here
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
                                const data = await Promise.all(dataPromises);


                                $buttonStatus = Swal.getPopup().querySelector(".auto-populate-api-status");
                                $loader = Swal.getPopup().querySelector(".lds-facebook");
                                $title = Swal.getPopup().querySelector(".swal2-title");
                                $loader.style.display = "none";
                                $title.textContent = 'Your theme setup is already done. Please click the link below to proceed.';
                                $buttonStatus.style.display = "inline-block";

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