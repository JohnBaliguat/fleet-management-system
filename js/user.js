$(document).ready(function () {
    $("#Mnav").attr({
        "class": "nav-link dropdown-toggle active"
    });
    $('#table-data').DataTable();

    $("#AddModal").click(function () {
        $("#addmodal").modal("show");
    });
    $("#close1").click(function () {
        $('#addForm')[0].reset();
        $("#addmodal").modal("hide");
    });
    $('#addUser').click(function (e) {
        e.preventDefault();

        var user_fname = $("#user_fname").val();
        var user_lname = $("#user_lname").val();
        var user_mname = $("#user_mname").val();
        var username = $("#username").val();
        var user_email = $("#user_email").val();
        var user_pass = $("#user_pass").val();
        var user_type = $("#user_type").val();
        var user_assignLocation = $("#user_assignLocation").val();
        if (user_fname == "" || user_lname == "" || user_mname == "" || username == "" || user_email == "" || user_pass == "" || user_type == "" || user_assignLocation == "") {
            Swal.fire({
                text: 'Please fill in all required fields',
                icon: 'info'
            });
            return;
        }
        Swal.fire({
            title: 'Confirm Register User',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Confirm'
        }).then((result) => {
            if (result.isConfirmed) {
                var formData = new FormData($('#addForm')[0]);
                $.ajax({
                    url: 'php/crud/add/adduser.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function (data) {
                        Swal.fire({
                            text: data,
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1200
                        });
                        $('#addForm')[0].reset();
                        $("#addmodal").modal("hide");
                        setTimeout(() => {
                            location.reload();
                        }, 1300);
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

    $('#UpdateUser').click(function () {
        var id = $('#user_Id1').val();
        var formData = new FormData($('#editForm')[0]);

        Swal.fire({
            title: 'Confirm Update User',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Confirm'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'php/crud/update/updateuser.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function (data) {
                        Swal.fire({
                            text: data,
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1200
                        });
                        $('#editModal').modal('hide');
                        setTimeout(() => {
                            location.reload();
                        }, 1300);
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

function openUpdateUser(user_id, user_name, user_fname, user_lname, user_mname, user_assignLocation, user_email, user_pass, user_type, user_accountStat) {
    $('#user_Id1').val(user_id);
    $('#user_fname1').val(user_fname);
    $('#user_lname1').val(user_lname);
    $('#user_mname1').val(user_mname);
    $('#username1').val(user_name);
    $('#user_email1').val(user_email);
    $('#user_type1').val(user_type);
    $('#user_stat1').val(user_accountStat);
    $('#user_assignLocation1').val(user_assignLocation);

    $('#editModal').modal('show');
}

function deleteUser(user_id) {
    var user_Id = user_id;

    var form_data = {
        user_Id: user_Id

    };
    Swal.fire({
        title: 'Confirm Remove User',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Confirm'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "php/crud/delete/deleteuser.php",
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
