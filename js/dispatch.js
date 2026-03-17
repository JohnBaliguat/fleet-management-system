const table = new DataTable('#table-data', {
    processing: true,
    serverSide: true,
    ajax: {
      url: 'table-fetch/dispatch-table.php',
      type: 'POST'
    },
    responsive: true
  });


  $('#table-data tbody').on('click', 'tr', function () {

    const data = table.row(this).data();
    const dispatch_id = $(this).attr("data-id");

    if(dispatch_id){
        loadTrips(dispatch_id);
    }

  });

  function loadTrips(dispatch_id){

    $.ajax({
      url: 'table-fetch/get-trips.php',
      method: 'POST',
      data: {dispatch_id: dispatch_id},
      success: function(response){

        $("#tripTableBody").html(response);

        const modal = new bootstrap.Modal(document.getElementById('tripModal'));
        modal.show();

      }
    });

  }
    $(document).ready(function() {
      $("#addRecord").click(function (e) {
        e.preventDefault();

        let driver_id = $("#driverId").val();
        let truck = $("#assignUnitName1").val();
        let costumer = $("#costumer").val();
        let booking_activity = $("#booking_activity").val();
        let hauling_segment = $("#hauling_segment").val();
        let trip_receipt = $("#tr").val();

        if (!trip_receipt) {
            Swal.fire({
                text: 'Required Trip Receipt',
                icon: 'info'
            });
            return;
        }

        if (!driver_id || !truck || !costumer) {
            Swal.fire({
                text: 'Please fill in all required fields',
                icon: 'info'
            });
            return;
        }

        /* ===============================
          CHECK DRIVER VIOLATION FIRST
          =============================== */
        $.ajax({
            url: 'php/operations/check_driver_violation.php',
            type: 'POST',
            data: { driver_id: driver_id },
            dataType: 'json',
            success: function (res) {

                if (res.status === "violation") {

                    let violationText = '';

                    res.violations.forEach(function (v, i) {
                        violationText += `
                            <div style="text-align:left; margin-bottom:8px;">
                                <strong>${i + 1}. ${v.vr_type}</strong><br>
                                ${v.vr_description}<br>
                                <small>Date: ${v.vr_date}</small>
                            </div>
                            <hr>
                        `;
                    });

                    Swal.fire({
                        icon: 'warning',
                        title: 'DISPATCH BLOCKED 🚫',
                        html: `
                            <p><strong>Driver has active compliance issue(s):</strong></p>
                            ${violationText}
                            <strong style="color:red;">
                                Please coordinate with HR to clear the driver compliance issue.
                            </strong>
                        `,
                        confirmButtonText: 'OK'
                    });

                    return;
                }

                /* ===============================
                  CONFIRM DISPATCH
                  =============================== */
                Swal.fire({
                    title: 'Confirm Dispatch',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Confirm'
                }).then((result) => {

                    if (!result.isConfirmed) return;

                    let formData = new FormData($('#addForm')[0]);

                    $.ajax({
                        url: 'php/crud/add/addRecord.php',
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        cache: false,
                        processData: false,
                        success: function (response) {

                            try {
                                const json = JSON.parse(response);
                                // console.log("Full response:", json);
                                // console.log("Insert ID:", json.insert_id);

                                if (json.status === 'success') {

                                    // Open print window immediately (during user interaction) before any alerts
                                    window.open(
                                        "index.php?route=print&id=" + json.insert_id,
                                        "_blank"
                                    );

                                    $('#addForm')[0].reset();

                                    Swal.fire({
                                        text: json.message,
                                        icon: 'success',
                                        showConfirmButton: false,
                                        timer: 1200
                                    }).then(() => {
                                        location.reload();
                                    });

                                } else {
                                    Swal.fire({
                                        text: json.message || "Error occurred",
                                        icon: 'error'
                                    });
                                }

                            } catch (err) {
                                Swal.fire({
                                    text: 'Unexpected response: ' + response,
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
                });
            },
            error: function () {
                Swal.fire({
                    text: 'Unable to verify driver violation status.',
                    icon: 'error'
                });
            }
        });
    });

      $('#editRecord').click(function(e) {
        e.preventDefault(); // Prevent default form submit

        var formData = new FormData($('#editForm')[0]);

        Swal.fire({
          title: 'Confirm Update Dispatch',
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Confirm'
        }).then((result) => {
          if (result.isConfirmed) {
            $.ajax({
              url: 'php/crud/update/updateRecord.php',
              type: 'POST',
              data: formData,
              contentType: false,
              processData: false,
              success: function(response) {
                Swal.fire({
                  text: response,
                  icon: 'success',
                  showConfirmButton: false,
                  timer: 1200
                });
                $('#editModal').modal('hide');
                setTimeout(() => {
                  location.reload();
                }, 1300);
              },
              error: function(xhr, status, error) {
                Swal.fire({
                  text: 'Error: ' + error,
                  icon: 'error'
                });
              }
            });
          }
        });
      });
      $('#printRecord').click(function(e) {
        e.preventDefault();
        var id = $("#edit_record_id").val();
        setTimeout(() => {
          window.open("index.php?route=print&id=" + id, "_blank");
          location.reload();
        }, 1300);

      });
    });

    function editDispatch(d_id) {
      $.ajax({
        url: 'php/fetch/get_dispatch.php',
        type: 'POST',
        data: {
          d_id: d_id
        },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            const dispatch = response.dispatch;
            const trip1 = response.trip1;
            const trip2 = response.trip2;

            $('#edit_record_id').val(dispatch.d_id);
            $('#edit_drivers_name').val(dispatch.d_driverName);
            $('#edit_truck').val(dispatch.d_truck);
            $('#edit_trailer').val(dispatch.d_trailer);
            $('#edit_genset').val(dispatch.d_genset);
            $('#edit_tr').val(dispatch.d_tripReceipt);
            $('#edit_ecs').val(dispatch.d_ecs);

            // Trip 1
            if (trip1) {
              $('#edit_container_no').val(trip1.trip_container);
              $('#edit_container_status').val(trip1.trip_containerStat);
              $('#edit_hauling_segment').val(trip1.trip_haulingSegment);
              $('#edit_destination_from').val(trip1.trip_from);
              $('#edit_destination_to').val(trip1.trip_to);
            }

            // Trip 2
            if (trip2) {
              $('#edit_container_no2').val(trip2.trip_container);
              $('#edit_container_status2').val(trip2.trip_containerStat);
              $('#edit_hauling_segment2').val(trip2.trip_haulingSegment);
              $('#edit_destination_from1').val(trip2.trip_from);
              $('#edit_destination_to1').val(trip2.trip_to);
            }

            $('#editModal').modal('show');
          } else {
            alert('Failed to fetch dispatch data.');
          }
        },
        error: function(xhr, status, error) {
          console.error('AJAX Error:', error);
          alert('An error occurred while fetching data.');
        }
      });
    }

    function deleteDispatch(d_id) {
      var d_Id = d_id;

      var form_data = {
        d_Id: d_Id

      };
      Swal.fire({
        title: 'Confirm Remove Record',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Confirm'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: "php/crud/delete/deleteRecord.php",
            type: "POST",
            data: form_data,
            dataType: "json",
            success: function(response) {
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

    function markDone(id) {
      Swal.fire({
        title: 'Are you sure?',
        text: "This dispatch and trips will be marked as Done.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, mark as Done'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: 'php/crud/update/update_status.php',
            type: 'POST',
            data: {
              id: id
            },
            dataType: 'json',
            success: function(response) {
              if (response.success) {
                Swal.fire(
                  'Updated!',
                  'The dispatch and trips are marked as Done.',
                  'success'
                ).then(() => {
                  $('#yourDataTableID').DataTable().ajax.reload(); // reload datatable
                });
              } else {
                Swal.fire('Error!', 'Failed to update status.', 'error');
              }
            },
            error: function() {
              Swal.fire('Error!', 'AJAX request failed.', 'error');
            }
          });
        }
      });
    }
