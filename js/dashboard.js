function loadTripCounts() {
        fetch('php/fetch/get_trip_counts.php')
          .then(res => res.json())
          .then(data => {
            document.getElementById("dailyCount").innerText = data.daily.toLocaleString();
            document.getElementById("avgCount").innerText = data.average.toLocaleString();
            document.getElementById("totalCount").innerText = data.total.toLocaleString();
          });
      }

      // Load on page load
      loadTripCounts();

      // Refresh every 30 seconds
      setInterval(loadTripCounts, 30000);


// CHART

let chart; // keep chart instance outside

function loadChart(fromDate = '', toDate = '') {
  // Fetch data via AJAX with optional date params
  fetch(`chartjs/get_trips_done.php?fromDate=${fromDate}&toDate=${toDate}`)
    .then(response => response.json())
    .then(data => {
      const seriesData = Object.keys(data).map(customer => ({
        name: customer,
        data: data[customer]
      }));

      const options = {
        series: seriesData,
        chart: {
          type: 'area',
          height: 350,
          stacked: true,
          toolbar: { show: true }
        },
        colors: ['#008FFB', '#00E396', '#CED4DC', '#FEB019', '#FF4560'],
        dataLabels: { enabled: false },
        stroke: { curve: 'monotoneCubic' },
        fill: {
          type: 'gradient',
          gradient: { opacityFrom: 0.6, opacityTo: 0.8 }
        },
        legend: {
          position: 'top',
          horizontalAlign: 'left'
        },
        xaxis: { type: 'datetime' },
        tooltip: {
          shared: true,
          y: { formatter: val => val + " trips" }
        }
      };

      if (chart) {
        chart.updateOptions(options);
      } else {
        chart = new ApexCharts(document.querySelector("#trip-overview"), options);
        chart.render();
      }
    });
}

// Load initial chart
loadChart();

// Handle filter form
document.getElementById('dateFilterForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const fromDate = document.getElementById('fromDate').value;
  const toDate = document.getElementById('toDate').value;
  loadChart(fromDate, toDate);
});

let truckChart; // keep chart instance

function loadTruckChart(fromDate = '', toDate = '') {
  fetch(`chartjs/get_units_done_trips.php?fromDate=${fromDate}&toDate=${toDate}`)
    .then(res => res.json())
    .then(data => {
      let categories = data.map(item => item.unit_name);
      let values = data.map(item => item.done_trips);

      // Dynamic height: 40px per bar (minimum 350px)
      let chartHeight = Math.max(350, categories.length * 40);

      let options = {
        series: [{
          data: values
        }],
        chart: {
          type: 'bar',
          height: chartHeight
        },
        plotOptions: {
          bar: {
            borderRadius: 4,
            borderRadiusApplication: 'end',
            horizontal: true,
            dataLabels: {
              position: 'center'
            }
          }
        },
        dataLabels: {
          enabled: true,
          formatter: function (val) {
            return val;
          },
          style: {
            colors: ['#fff'],
            fontSize: '14px',
            fontWeight: 'bold'
          }
        },
        xaxis: {
          categories: categories
        }
      };

      if (truckChart) {
        truckChart.updateOptions(options);
      } else {
        truckChart = new ApexCharts(document.querySelector("#Truckchart"), options);
        truckChart.render();
      }
    });
}

// Load initial Truck chart
loadTruckChart();

// Hook into same date filter form
document.getElementById('dateFilterForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const fromDate = document.getElementById('fromDate').value;
  const toDate = document.getElementById('toDate').value;
  loadTruckChart(fromDate, toDate);
});


let trailerChart; // keep chart instance

function loadTrailerChart(fromDate = '', toDate = '') {
  fetch(`chartjs/get_trailers_done_trips.php?fromDate=${fromDate}&toDate=${toDate}`)
    .then(res => res.json())
    .then(data => {
      let categories = data.map(item => item.trailer_name);
      let values = data.map(item => item.done_trips);

      let chartHeight = Math.max(350, categories.length * 40);

      let trailerOptions = {
        series: [{ data: values }],
        chart: { type: 'bar', height: chartHeight },
        plotOptions: {
          bar: {
            borderRadius: 4,
            borderRadiusApplication: 'end',
            horizontal: true,
            dataLabels: { position: 'center' }
          }
        },
        dataLabels: {
          enabled: true,
          formatter: val => val,
          style: {
            colors: ['#fff'],
            fontSize: '14px',
            fontWeight: 'bold'
          }
        },
        xaxis: { categories: categories }
      };

      if (trailerChart) {
        trailerChart.updateOptions(trailerOptions);
      } else {
        trailerChart = new ApexCharts(document.querySelector("#Trailerchart"), trailerOptions);
        trailerChart.render();
      }
    });
}

// Load initial Trailer chart
loadTrailerChart();

// Hook into same date filter form
document.getElementById('dateFilterForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const fromDate = document.getElementById('fromDate').value;
  const toDate = document.getElementById('toDate').value;
  loadTrailerChart(fromDate, toDate);
});

let driverChart; // keep chart instance

function loadDriverChart(fromDate = '', toDate = '') {
  fetch(`chartjs/get_drivers_done_trips.php?fromDate=${fromDate}&toDate=${toDate}`)
    .then(res => res.json())
    .then(data => {
      let categories = data.map(item => item.driver_name);
      let values = data.map(item => item.done_trips);

      let chartHeight = Math.max(350, categories.length * 40);

      let driverOptions = {
        series: [{ data: values }],
        chart: { type: 'bar', height: chartHeight },
        plotOptions: {
          bar: {
            borderRadius: 4,
            borderRadiusApplication: 'end',
            horizontal: true,
            dataLabels: { position: 'center' }
          }
        },
        dataLabels: {
          enabled: true,
          formatter: val => val,
          style: {
            colors: ['#fff'],
            fontSize: '14px',
            fontWeight: 'bold'
          }
        },
        xaxis: { categories: categories }
      };

      if (driverChart) {
        driverChart.updateOptions(driverOptions);
      } else {
        driverChart = new ApexCharts(document.querySelector("#Driverchart"), driverOptions);
        driverChart.render();
      }
    });
}

// Load initial Driver chart
loadDriverChart();

// Hook into same date filter form
document.getElementById('dateFilterForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const fromDate = document.getElementById('fromDate').value;
  const toDate = document.getElementById('toDate').value;
  loadDriverChart(fromDate, toDate);
});

