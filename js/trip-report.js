const table = new DataTable('#table-data', {
  processing: true,
  serverSide: true,
  ajax: {
    url: 'table-fetch/trip-table.php',
    type: 'POST',
    data: function(d) {
      d.fromDate = $('#fromDate').val();
      d.toDate = $('#toDate').val();
      d.customer = $('#customer').val() ?? '';
    }
  },
  responsive: true,
});

// Reload table
$('#submit').on('click', function(e) {
  e.preventDefault();
  table.ajax.reload();
});
