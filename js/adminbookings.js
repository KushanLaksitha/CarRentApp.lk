function viewBooking(id) {
    // Implement viewing booking details
    alert('Viewing booking #' + id);
}

function deleteBooking(id) {
    if (confirm('Are you sure you want to delete this booking?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input name="delete_booking" value="1"><input name="booking_id" value="${id}">`;
        document.body.append(form);
        form.submit();
    }
}
