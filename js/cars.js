function editCar(id, name, available) {
    document.getElementById('edit_car_id').value = id;
    document.getElementById('edit_car_name').value = name;
    document.getElementById('edit_available').checked = available;
    new bootstrap.Modal(document.getElementById('editCarModal')).show();
}

function deleteCar(id, name) {
    document.getElementById('delete_car_id').value = id;
    document.getElementById('delete_car_name').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteCarModal')).show();
}
