// Drop and Drag
const dropArea = document.getElementById('drop-area');
const bookingFile = document.getElementById('bookingFile');
const fileNameDiv = document.getElementById('fileName');

dropArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropArea.style.background = '#f1f1f1';
});
dropArea.addEventListener('dragleave', (e) => {
    e.preventDefault();
    dropArea.style.background = '';
});
dropArea.addEventListener('drop', (e) => {
    e.preventDefault();
    dropArea.style.background = '';
    bookingFile.files = e.dataTransfer.files;
    showFileName();
});

bookingFile.addEventListener('change', showFileName);

function showFileName() {
    if (bookingFile.files.length > 0) {
        fileNameDiv.textContent = 'Selected file: ' + bookingFile.files[0].name;
    } else {
        fileNameDiv.textContent = '';
    }
}

document.getElementById('uploadForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const file = bookingFile.files[0];
    if (!file) {
        document.getElementById('uploadStatus').innerHTML = '<span style="color:red;">Please select an Excel file.</span>';
        return;
    }
    if (!(/\.(xlsx|xls)$/i).test(file.name)) {
        document.getElementById('uploadStatus').innerHTML = '<span style="color:red;">Only Excel files (.xlsx, .xls) are allowed.</span>';
        return;
    }
    const reader = new FileReader();
    reader.onload = function (e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const firstSheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[firstSheetName];
        const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
        displayBookings(jsonData);
        document.getElementById('uploadStatus').innerHTML = '<span style="color:green;">File uploaded successfully!</span>';
    };
    reader.readAsArrayBuffer(file);
});

function displayBookings(data) {
    const tbody = document.querySelector('#bookingTable tbody');
    tbody.innerHTML = '';
    for (let i = 1; i < data.length; i++) { // skip header
        const row = data[i];
        const tr = document.createElement('tr');
        for (let j = 0; j < 5; j++) {
            const td = document.createElement('td');
            td.className = 'px-0';
            td.textContent = row[j] !== undefined ? row[j] : '';
            tr.appendChild(td);
        }
        tbody.appendChild(tr);
    }
}

// END of Drop And Drag

// Ajax 
const table = new DataTable('#booking-table', {
          processing: true,
          serverSide: true,
          ajax: {
              url: 'table-fetch/booking-table.php',
              type: 'POST'
          },
          responsive: true
      });

    function fetchBookingNo() {
      fetch('php/fetch/get_next_booking_no.php')
        .then(response => response.text())
        .then(data => {
          document.getElementById('booking_no').value = data;
          document.getElementById('booking_no2').value = data;
        });
    }

    // Initial load
    fetchBookingNo();

    // Update every 2 seconds
    setInterval(fetchBookingNo, 2000);
     $(document).ready(function() {
        $("#addbooking").click(function () {
          $("#AddBookingModal").modal("show");
        });
        $("#addbooking1").click(function () {
          $("#AddBookingModal1").modal("show");
        });

        $("#saveBookingBtn").click(function (e) {
            e.preventDefault();

            // Get values for validation
            var costumer = $("#costumer").val();
            var booking_date = $("#booking_date").val();
            var container = $("#container").val();
            var container_status = $("#container_status").val();
            var hauling_segment = $("#hauling_segment").val();
            var trip_from = $("#trip_from").val();
            var trip_to = $("#trip_to").val();
            var quantity = $("#quantity").val();

            if (
                costumer === "" || booking_date === "" || container_status === "" || hauling_segment === "" ||
                trip_from === "" || trip_to === "" || quantity === ""
            ) {
                Swal.fire({
                    text: 'Please fill in all required fields',
                    icon: 'info'
                });
                return;
            }

            Swal.fire({
                title: 'Confirm Save Booking?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Confirm'
            }).then((result) => {
                if (result.isConfirmed) {
                    var formData = new FormData($('#addForm')[0]);

                    $.ajax({
                        url: 'php/crud/add/addbooking.php',
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        cache: false,
                        processData: false,
                        success: function (response) {
                            let res = JSON.parse(response);
                            Swal.fire({
                                text: res.message,
                                icon: res.status,
                                showConfirmButton: false,
                                timer: 1500
                            });
                            $('#addForm')[0].reset();
                            $('#AddBookingModal').modal('hide');
                            setTimeout(() => {
                                location.reload(); // or refresh DataTable
                            }, 1600);
                        },
                        error: function (xhr, status, error) {
                            Swal.fire({
                                text: 'Error: ' + error,
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });
        $("#saveChangesBtn").click(function (e) {
            e.preventDefault();

            // Get values for validation
            var booking_no1 = $("#booking_no1").val();
            var booking_date1 = $("#booking_date1").val();
            var customer1 = $("#costumer1").val(); // fixed spelling
            var container1 = $("#container1").val();
            var container_status1 = $("#container_status1").val();
            var hauling_segment1 = $("#hauling_segment1").val();
            var trip_from1 = $("#trip_from1").val();
            var trip_to1 = $("#trip_to1").val();
            var quantity1 = $("#quantity1").val();

            if (
                booking_no1 === "" || booking_date1 === "" || customer1 === "" || container_status1 === "" || hauling_segment1 === "" ||
                trip_from1 === "" || trip_to1 === "" || quantity1 === ""
            ) {
                Swal.fire({
                    text: 'Please fill in all required fields',
                    icon: 'info'
                });
                return;
            }

            Swal.fire({
                title: 'Confirm Save Changes?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Confirm'
            }).then((result) => {
                if (result.isConfirmed) {
                    var formData = new FormData($('#editForm')[0]);

                    $.ajax({
                        url: 'php/operations/editbooking.php',
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        cache: false,
                        processData: false,
                        success: function (response) {
                            try {
                                let res = JSON.parse(response);
                                Swal.fire({
                                    text: res.message,
                                    icon: res.status,
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                                $('#editForm')[0].reset();
                                $('#editModal').modal('hide');
                                setTimeout(() => {
                                    location.reload();
                                }, 1600);
                            } catch (e) {
                                Swal.fire({
                                    text: 'Invalid JSON: ' + response,
                                    icon: 'error'
                                });
                            }
                        },
                        error: function (xhr, status, error) {
                            Swal.fire({
                                text: 'Error: ' + error,
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });
    });

    function editBooking(booking_id) {
        $.getJSON('php/fetch/get_booking.php', { booking_id: booking_id }, function (res) {
            if (res.status !== 'success') {
                Swal.fire({ icon: 'error', text: res.message || 'Failed to load booking' });
                return;
            }
            var b = res.booking;
            $('#booking_id1').val(b.booking_id);
            $('#booking_no1').val(b.booking_no);
            $('#booking_type1').val(b.booking_type || 'Local');
            $('#booking_date1').val(b.booking_date);
            $('#booking_required1').val(b.booking_dateRequired);
            $('#costumer1').val(b.costumer);
            $('#container_seal1').val(b.container_seal);
            $('#container1').val(b.container);
            $('#booking_activity1').val(b.booking_activity);
            $('#container_status1').val(b.container_status);
            $('#hauling_segment1').val(b.hauling_segment);
            $('#trip_from1').val(b.trip_from);
            $('#trip_to1').val(b.trip_to);
            $('#quantity1').val(b.quantity);

            // Phase 2 — port fields.
            $('#vessel_name1').val(b.vessel_name || '');
            $('#voyage_no1').val(b.voyage_no || '');
            $('#container_no_port1').val(b.container_no_port || '');
            $('#bill_of_lading1').val(b.bill_of_lading || '');
            $('#port_location1').val(b.port_location || '');
            $('#customs_cleared1').prop('checked', b.customs_cleared == 1);

            // Trigger the booking-type toggle to show/hide port panel.
            $('#booking_type1').trigger('change');

            // If any quantity has been used, lock everything except quantity.
            var quantity_use = parseInt(b.quantity_use, 10) || 0;
            var lockable = '#booking_type1, #booking_no1, #booking_date1, #booking_required1, #costumer1, ' +
                '#container_seal1, #container1, #booking_activity1, #container_status1, #hauling_segment1, ' +
                '#trip_from1, #trip_to1, #vessel_name1, #voyage_no1, #container_no_port1, ' +
                '#bill_of_lading1, #port_location1';
            if (quantity_use !== 0) {
                $(lockable).prop('readonly', true);
                $('#booking_type1').prop('disabled', true);
                $('#quantity1').prop('readonly', false);
            } else {
                $(lockable + ', #quantity1').prop('readonly', false);
                $('#booking_type1').prop('disabled', false);
            }

            $('#editModal').modal('show');
        }).fail(function () {
            Swal.fire({ icon: 'error', text: 'Failed to load booking' });
        });
    }
        function deleteBooking(booking_id) {
            var booking_Id = booking_id;

            var form_data = {
                booking_Id: booking_Id

            };
            Swal.fire({
                title: 'Confirm Remove Booking',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Confirm'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "php/crud/delete/deletebooking.php",
                        type: "POST",
                        data: form_data,
                        dataType: "json",
                        success: function (response) {
                            if (response['valid'] == false) {
                                Swal.fire({
                                    text: response['msg'],
                                    icon: 'warning'
                                });
                            } else {
                                Swal.fire({
                                    text: response['msg'],
                                    icon: 'success',
                                    showConfirmButton: false,
                                    timer: 1200
                                });
                                setTimeout(() => {
                                    location.reload();
                                }, 1300);
                            }
                        }

                    });
                }
            });
        }
