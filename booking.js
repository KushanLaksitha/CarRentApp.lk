document.addEventListener('DOMContentLoaded', function() {
    const bookingForm = document.getElementById('bookingForm');

    bookingForm.addEventListener('submit', function(event) {
        event.preventDefault();
        
        // Validation functions
        function validateName(name) {
            return name.length >= 2 && /^[A-Za-z\s]+$/.test(name);
        }

        function validateEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function validateMobile(mobile) {
            const phoneRegex = /^[0-9]{10}$/;
            return phoneRegex.test(mobile);
        }

        // Get form values
        const firstName = document.querySelector('input[name="first_name"]').value.trim();
        const lastName = document.querySelector('input[name="last_name"]').value.trim();
        const email = document.querySelector('input[name="email"]').value.trim();
        const mobile = document.querySelector('input[name="mobile"]').value.trim();
        const pickupLocation = document.querySelector('select[name="pickup_location"]').value;
        const dropoffLocation = document.querySelector('select[name="dropoff_location"]').value;
        const pickupDate = document.querySelector('input[name="pickup_date"]').value;
        const pickupTime = document.querySelector('input[name="pickup_time"]').value;
        const adults = document.querySelector('select[name="adults"]').value;
        const children = document.querySelector('select[name="children"]').value;
        const paymentMethod = document.querySelector('input[name="payment"]:checked');

        // Validation checks
        const errors = [];

        if (!validateName(firstName)) errors.push('Invalid first name');
        if (!validateName(lastName)) errors.push('Invalid last name');
        if (!validateEmail(email)) errors.push('Invalid email address');
        if (!validateMobile(mobile)) errors.push('Invalid mobile number');
        if (!pickupLocation) errors.push('Please select pickup location');
        if (!dropoffLocation) errors.push('Please select dropoff location');
        if (!pickupDate) errors.push('Please select pickup date');
        if (!pickupTime) errors.push('Please select pickup time');
        if (!adults) errors.push('Please select number of adults');
        if (!children) errors.push('Please select number of children');
        if (!paymentMethod) errors.push('Please select a payment method');

        // Display or submit
        if (errors.length > 0) {
            alert(errors.join('\n'));
            return;
        }

        // Form submission via AJAX
        const formData = new FormData(bookingForm);

        fetch('submit_booking.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                bookingForm.reset();
            } else {
                alert('Booking failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred during booking');
        });
    });

    // Optional: Date range validation
    const pickupDateInput = document.querySelector('input[name="pickup_date"]');
    const today = new Date().toISOString().split('T')[0];
    pickupDateInput.setAttribute('min', today);
});