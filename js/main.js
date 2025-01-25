(function ($) {
    "use strict";
    
    // Dropdown on mouse hover
    $(document).ready(function () {
        function toggleNavbarMethod() {
            if ($(window).width() > 992) {
                $('.navbar .dropdown').on('mouseover', function () {
                    $('.dropdown-toggle', this).trigger('click');
                }).on('mouseout', function () {
                    $('.dropdown-toggle', this).trigger('click').blur();
                });
            } else {
                $('.navbar .dropdown').off('mouseover').off('mouseout');
            }
        }
        toggleNavbarMethod();
        $(window).resize(toggleNavbarMethod);
    });


    // Date and time picker
    $('.date').datetimepicker({
        format: 'L'
    });
    $('.time').datetimepicker({
        format: 'LT'
    });
    
    
    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    });


    // Team carousel
    $(".team-carousel, .related-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1000,
        center: true,
        margin: 30,
        dots: false,
        loop: true,
        nav : true,
        navText : [
            '<i class="fa fa-angle-left" aria-hidden="true"></i>',
            '<i class="fa fa-angle-right" aria-hidden="true"></i>'
        ],
        responsive: {
            0:{
                items:1
            },
            576:{
                items:1
            },
            768:{
                items:2
            },
            992:{
                items:3
            }
        }
    });


    // Testimonials carousel
    $(".testimonial-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1500,
        margin: 30,
        dots: true,
        loop: true,
        center: true,
        responsive: {
            0:{
                items:1
            },
            576:{
                items:1
            },
            768:{
                items:2
            },
            992:{
                items:3
            }
        }
    });


    // Vendor carousel
    $('.vendor-carousel').owlCarousel({
        loop: true,
        margin: 30,
        dots: true,
        loop: true,
        center: true,
        autoplay: true,
        smartSpeed: 1000,
        responsive: {
            0:{
                items:2
            },
            576:{
                items:3
            },
            768:{
                items:4
            },
            992:{
                items:5
            },
            1200:{
                items:6
            }
        }
    });
    
})(jQuery);

// search_results
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('searchForm');
    
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Collect form data
        const pickupLocation = document.querySelector('select[name="pickup_location"]').value;
        const dropLocation = document.querySelector('select[name="drop_location"]').value;
        const pickupDate = document.querySelector('input[name="pickup_date"]').value;
        const pickupTime = document.querySelector('input[name="pickup_time"]').value;
        const selectedCar = document.querySelector('select[name="car_select"]').value;
        
        // Create FormData to send to PHP
        const formData = new FormData();
        formData.append('pickup_location', pickupLocation);
        formData.append('drop_location', dropLocation);
        formData.append('pickup_date', pickupDate);
        formData.append('pickup_time', pickupTime);
        formData.append('car_select', selectedCar);
        
        // Send AJAX request to PHP
        fetch('search_results.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            displaySearchResults(data);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while searching for cars.');
        });
    });
    
    function displaySearchResults(results) {
        const resultsContainer = document.getElementById('searchResultsContainer');
        resultsContainer.innerHTML = ''; // Clear previous results
        
        if (results.length === 0) {
            resultsContainer.innerHTML = '<p>No cars found matching your search criteria.</p>';
            return;
        }
        
        results.forEach(car => {
            const carCard = `
                <div class="col-lg-4 col-md-6 mb-2">
                    <div class="rent-item mb-4">
                        <img class="img-fluid mb-4" src="${car.image}" alt="${car.name}">
                        <h4 class="text-uppercase mb-4">${car.name}</h4>
                        <div class="d-flex justify-content-center mb-4">
                            <div class="px-2">
                                <i class="fa fa-car text-primary mr-1"></i>
                                <span>${car.year}</span>
                            </div>
                            <div class="px-2 border-left border-right">
                                <i class="fa fa-cogs text-primary mr-1"></i>
                                <span>${car.transmission}</span>
                            </div>
                            <div class="px-2">
                                <i class="fa fa-road text-primary mr-1"></i>
                                <span>${car.mileage}</span>
                            </div>
                        </div>
                        <a class="btn btn-primary px-3" href="booking.html">LKR ${car.price}/Day</a>
                    </div>
                </div>
            `;
            resultsContainer.innerHTML += carCard;
        });
    }
});

