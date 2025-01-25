document.getElementById("checkAvailabilityBtn").addEventListener("click", function () {
    const form = document.getElementById("checkAvailabilityForm");
    const formData = new FormData(form);

    fetch("check_availability.php", {
        method: "POST",
        body: formData,
    })
        .then((response) => response.json())
        .then((data) => {
            const resultDiv = document.getElementById("availabilityResult");
            if (data.success) {
                resultDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            } else {
                resultDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch((error) => {
            console.error("Error:", error);
        });
});
