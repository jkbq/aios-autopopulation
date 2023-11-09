document.addEventListener('DOMContentLoaded', function () {

    const currentDomain = window.location.origin;
    const apiUrl = `${currentDomain}/wp-json/aios-populate/v1/widgets`;

    // Select elements by class name
    const fetchDataButton = document.querySelector('.aios-repopulate-widgets');

    // Data to be sent in the request
    const postData = {
        repopulate: true,
    };


    fetchDataButton.addEventListener('click', function () {
        Swal.fire({
            title: "Please wait for as we setup your theme ",
            html: `<div class="lds-facebook"><div></div><div></div><div></div></div><a href="${currentDomain}" class="wpui-secondary-button text-uppercase auto-populate-api-status">Visit Homepage</a>`,
            showConfirmButton: false,
            didOpen: () => {
               

                // Create the fetch request with a POST method and include the data in the body
                fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(postData), // Convert the data to JSON
                }).then(response => response.json())
                    .then(data => {
                        // Handle the response from the API
                    
                        $buttonStatus = Swal.getPopup().querySelector(".auto-populate-api-status");
                        $loader = Swal.getPopup().querySelector(".lds-facebook");
                        $title = Swal.getPopup().querySelector(".swal2-title");
                        $loader.style.display = "none";
                        $title.textContent = 'Your theme setup is already done. Please click the link below to proceed.'; 
                        $buttonStatus.style.display = "inline-block";
                        
                    })
                    .catch(error => {
                        console.error('Error sending data:', error);
                    });
                
            },
        })


    });
});