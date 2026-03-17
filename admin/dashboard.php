<?php
session_start();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === "Admin") {


?>
  <!doctype html>
  <html lang="en">

  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link rel="shortcut icon" type="image/png" href="assets/images/logos/LogoFleet.png" />
    <link rel="stylesheet" href="assets/css/styles.min.css" />
    <link rel="stylesheet" href="assets/css/enhancements.css" />
  </head>

  <body>
    <!--  Body Wrapper -->
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
      data-sidebar-position="fixed" data-header-position="fixed">

      <!--  App Topstrip -->
      <div class="app-topstrip bg-dark py-6 px-3 w-100 d-lg-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center justify-content-center gap-5 mb-2 mb-lg-0">
          <a class="d-flex justify-content-center" href="#">
            <img src="assets/images/logos/pantrucks1.png" alt="" width="122">
          </a>
        </div>

        <div class="d-lg-flex align-items-center gap-2">
          <h3 class="text-white mb-2 mb-lg-0 fs-5 text-center">Pantrucks Fleet Management System</h3>
          <div class="d-flex align-items-center justify-content-center gap-2">
          </div>
        </div>

      </div>
      <!-- Sidebar Start -->
      <?php include 'sidebar.php'; ?>
      <!--  Sidebar End -->
      <!--  Main wrapper -->
      <div class="body-wrapper">
        <!--  Header Start -->
        <?php include 'navbar.php'; ?>
        <!--  Header End -->
        <div class="body-wrapper-inner">
          <div class="container-fluid">
            <div class="row">
              <div class="col-md-7"></div>
              <div class="col-md-5">
                <div class="card overflow-hidden">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center">
                        <form id="filterForm">
                          <div class="row">
                            <div class="col-1">
                              <label for="fromDate" class="col-form-label">From</label>
                            </div>
                            <div class="col-4">
                              <input type="date" id="fromDate" name="fromDate" class="form-control">
                            </div>

                            <div class="col-2 text-end">
                              <label for="toDate" class="col-form-label">To</label>
                            </div>
                            <div class="col-4">
                              <input type="date" id="toDate" name="toDate" class="form-control">
                            </div>

                            <div class="col-1">
                              <button type="button" id="submit" class="btn btn-primary">
                                Generate
                              </button>
                            </div>
                          </div>
                        </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4">
                <div class="card overflow-hidden">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center">
                      <span class="btn btn-primary rounded-circle round-48 hstack justify-content-center">
                        <i class="ti ti-clock fs-6"></i>
                      </span>
                      <div class="ms-3">
                        <h5 class="mb-0 fw-bolder fs-4">Daily Transaction</h5>
                      </div>
                      <div class="ms-auto">
                        <span id="dailyCount" class="badge bg-secondary-subtle text-dark" style="font-size: 20px; font-weight: 600;">0</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="card overflow-hidden">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center">
                      <span class="btn btn-primary rounded-circle round-48 hstack justify-content-center">
                        <i class="ti ti-chart-line fs-6"></i>
                      </span>
                      <div class="ms-3">
                        <h5 class="mb-0 fw-bolder fs-4">Average Transaction</h5>
                      </div>
                      <div class="ms-auto">
                        <span id="avgCount" class="badge bg-secondary-subtle text-dark" style="font-size: 20px; font-weight: 600;">0</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="card overflow-hidden">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center">
                      <span class="btn btn-primary rounded-circle round-48 hstack justify-content-center">
                        <i class="ti ti-database fs-6"></i>
                      </span>
                      <div class="ms-3">
                        <h5 class="mb-0 fw-bolder fs-4">Total Transaction</h5>
                      </div>
                      <div class="ms-auto">
                        <span id="totalCount" class="badge bg-secondary-subtle text-dark" style="font-size: 20px; font-weight: 600;">0</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4">
                <div class="card overflow-hidden">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center">
                      <span class="btn btn-primary rounded-circle round-48 hstack justify-content-center">
                        <i class="ti ti-truck fs-6"></i>
                      </span>
                      <div class="ms-3">
                        <h5 class="mb-0 fw-bolder fs-4">TRUCK</h5>
                      </div>
                      <div class="ms-auto">
                        <span id="truckCount" class="badge bg-secondary-subtle text-dark" style="font-size: 20px; font-weight: 600;">0</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="card overflow-hidden">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center">
                      <span class="btn btn-primary rounded-circle round-48 hstack justify-content-center">
                        <i class="ti ti-box fs-6"></i>
                      </span>
                      <div class="ms-3">
                        <h5 class="mb-0 fw-bolder fs-4">TRAILER</h5>
                      </div>
                      <div class="ms-auto">
                        <span id="trailerCount" class="badge bg-secondary-subtle text-dark" style="font-size: 20px; font-weight: 600;">0</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="card overflow-hidden">
                  <div class="card-body pb-0">
                    <div class="pb-3 d-flex align-items-center">
                      <span class="btn btn-primary rounded-circle round-48 hstack justify-content-center">
                        <i class="ti ti-database fs-6"></i>
                      </span>
                      <div class="ms-3">
                        <h5 class="mb-0 fw-bolder fs-4">GENSET</h5>
                      </div>
                      <div class="ms-auto">
                        <span id="gensetCount" class="badge bg-secondary-subtle text-dark" style="font-size: 20px; font-weight: 600;">0</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              
            </div>
            <div class="row">
              <div class="col-lg-3">
                <div class="card w-100">
                  <div class="card-body">
                    <div class="d-md-flex align-items-center">
                      <div>
                        <h4 class="card-title">Fleet</h4>
                        <p class="card-subtitle">

                        </p>
                      </div>
                    </div>
                    <div id="chart"></div>
                  </div>
                </div>
              </div>
              <div class="col-lg-9">
                <div class="card w-100">
                  <div class="card-body">
                    <div class="d-md-flex align-items-center">
                      <div>
                      </div>
                    </div>
                    <div id="chart1"></div>
                  </div>
                </div>
              </div>
              <div class="col-lg-3">
                <div class="row">
                  <div class="col-md-12">
                    <div class="card w-100">
                      <div class="card-body">
                        <div class="d-md-flex align-items-center">
                          <div>
                            <h4 class="card-title">Truck Utilization</h4>
                          </div>
                        </div>
                        <div id="chart2"></div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-12">
                    <div class="card w-100">
                      <div class="card-body">
                        <div class="d-md-flex align-items-center">
                          <div>
                            <h4 class="card-title">Trailer Utilization</h4>
                          </div>
                        </div>
                        <div id="chart3"></div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
              <div class="col-lg-9">
                <div class="card w-100" style="height: 480px;">
                  <div class="card-body">
                    <div class="d-md-flex align-items-center">
                      <div>
                      </div>
                    </div>
                    <div id="chart4"></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="py-6 px-6 text-center">
              <p class="mb-0 fs-4">Design and Developed by JA Baliguat | 2025</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script src="assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/sidebarmenu.js"></script>
    <script src="assets/js/app.min.js"></script>
    <script src="assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="assets/libs/simplebar/dist/simplebar.js"></script>


    <!-- solar icons -->
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  </body>
  <script>
    function loadUnitCounts() {
    $.ajax({
        url: 'php/fetch/getUnitCounts.php',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            $('#truckCount').text(res.truck);
            $('#gensetCount').text(res.genset);
            $('#trailerCount').text(res.trailer);
        }
    });
}

// auto load
loadUnitCounts();

    $.ajax({
      url: 'chartjs/getUnitsSummary.php',
      method: 'GET',
      dataType: 'json',
      success: function(data) {

        var options1 = {
          series: [
            data.truck,
            data.trailer,
            data.genset
          ],
          chart: {
            width: 380,
            type: 'pie'
          },
          labels: ['Truck', 'Trailer', 'Genset'],
          responsive: [{
            breakpoint: 480,
            options: {
              chart: {
                width: 200
              },
              legend: {
                position: 'bottom'
              }
            }
          }]
        };

        var chart1 = new ApexCharts(
          document.querySelector("#chart"),
          options1
        );

        chart1.render();
      },
      error: function() {
        alert('Failed to load unit data');
      }
    });

    // TRIP VS USED TRUCKS

    let chart;
    let isFirstLoad = true;

    function loadChart(fromDate = '', toDate = '') {
      $.ajax({
        url: 'chartjs/getTripsVsTruck.php',
        type: 'GET',
        dataType: 'json',
        data: {
          fromDate,
          toDate
        },
        success: function(res) {

          const series = [{
              name: 'Trips',
              type: 'column',
              data: res.dates.map((d, i) => ({
                x: new Date(d).getTime(),
                y: res.trips[i]
              }))
            },
            {
              name: 'Used Truck',
              type: 'line',
              data: res.dates.map((d, i) => ({
                x: new Date(d).getTime(),
                y: res.trucks[i]
              }))
            }
          ];

          if (!chart) {
            const now = new Date();
            const monthStart = new Date(now.getFullYear(), now.getMonth(), 1).getTime();
            const monthEnd = new Date(now.getFullYear(), now.getMonth() + 1, 0).getTime();

            const options = {
              series: series,
              chart: {
                height: 280,
                type: 'line',
                zoom: {
                  enabled: true,
                  autoScaleYaxis: true
                }
              },
              stroke: {
                width: [0, 4]
              },
              title: {
                text: 'Total Trips & Used Truck'
              },
              dataLabels: {
                enabled: true,
                enabledOnSeries: [1]
              },
              xaxis: {
                type: 'datetime',
                min: monthStart,
                max: monthEnd
              }
            };

            chart = new ApexCharts(document.querySelector("#chart1"), options);
            chart.render();
          } else {
            chart.updateSeries(series);

            // 🔥 remove forced zoom after first load
            if (isFirstLoad) {
              chart.updateOptions({
                xaxis: {
                  min: undefined,
                  max: undefined
                }
              });
              isFirstLoad = false;
            }
          }
        }
      });
    }

    // initial load
    loadChart();

    // 🔹 Apply filter
    $('#submit').on('click', function() {
      const fromDate = $('#fromDate').val();
      const toDate = $('#toDate').val();
      loadChart(fromDate, toDate);
    });

    // Semi Circle Chart
    let chart2;

    function loadTruckUtilization(fromDate = '', toDate = '') {
      $.ajax({
        url: 'chartjs/getTruckUtilization.php',
        type: 'GET',
        dataType: 'json',
        data: {
          fromDate,
          toDate
        },
        success: function (res) {

          if (!chart2) {
            const options2 = {
              series: [res.utilization],
              chart: {
                type: 'radialBar',
                height: 280,
                offsetY: -20,
                sparkline: { enabled: true }
              },
              plotOptions: {
                radialBar: {
                  startAngle: -90,
                  endAngle: 90,
                  track: {
                    background: "#e7e7e7",
                    strokeWidth: '97%',
                    margin: 5,
                    dropShadow: {
                      enabled: true,
                      top: 2,
                      left: 0,
                      color: '#444',
                      opacity: 0.3,
                      blur: 2
                    }
                  },
                  dataLabels: {
                    name: { show: false },
                    value: {
                      formatter: function (val) {
                        return val + "%";
                      },
                      offsetY: -2,
                      fontSize: '22px'
                    }
                  }
                }
              },
              fill: {
                type: 'gradient',
                gradient: {
                  shade: 'light',
                  shadeIntensity: 0.4,
                  opacityFrom: 1,
                  opacityTo: 1,
                  stops: [0, 50, 53, 91]
                }
              },
              labels: ['Truck Utilization']
            };

            chart2 = new ApexCharts(
              document.querySelector("#chart2"),
              options2
            );
            chart2.render();
          } else {
            chart2.updateSeries([res.utilization]);
          }
        },
        error: function () {
          alert('Failed to load utilization data');
        }
      });
    }

    // 🔹 initial load
    loadTruckUtilization();

    // 🔹 Apply same filter button
    $('#submit').on('click', function () {
      const fromDate = $('#fromDate').val();
      const toDate = $('#toDate').val();
      loadTruckUtilization(fromDate, toDate);
    });

    let chart3;

    function loadTrailerUtilization(fromDate = '', toDate = '') {
      $.ajax({
        url: 'chartjs/getTrailerUtilization.php',
        type: 'GET',
        dataType: 'json',
        data: {
          fromDate,
          toDate
        },
        success: function (res) {

          if (!chart3) {
            const options3 = {
              series: [res.utilization],
              chart: {
                type: 'radialBar',
                height: 280,
                offsetY: -20,
                sparkline: { enabled: true }
              },
              plotOptions: {
                radialBar: {
                  startAngle: -90,
                  endAngle: 90,
                  track: {
                    background: "#e7e7e7",
                    strokeWidth: '97%',
                    margin: 5,
                    dropShadow: {
                      enabled: true,
                      top: 2,
                      left: 0,
                      color: '#444',
                      opacity: 0.3,
                      blur: 2
                    }
                  },
                  dataLabels: {
                    name: { show: false },
                    value: {
                      formatter: function (val) {
                        return val + "%";
                      },
                      offsetY: -2,
                      fontSize: '22px'
                    }
                  }
                }
              },
              fill: {
                type: 'gradient',
                gradient: {
                  shade: 'light',
                  shadeIntensity: 0.4,
                  opacityFrom: 1,
                  opacityTo: 1,
                  stops: [0, 50, 53, 91]
                }
              },
              labels: ['Truck Utilization']
            };

            chart3 = new ApexCharts(
              document.querySelector("#chart3"),
              options3
            );
            chart3.render();
          } else {
            chart3.updateSeries([res.utilization]);
          }
        },
        error: function () {
          alert('Failed to load utilization data');
        }
      });
    }

    // 🔹 initial load
    loadTrailerUtilization();

    // 🔹 Apply same filter button
    $('#submit').on('click', function () {
      const fromDate = $('#fromDate').val();
      const toDate = $('#toDate').val();
      loadTrailerUtilization(fromDate, toDate);
    });

   
    let chart4;

    function loadShopRescueChart(fromDate = '', toDate = '') {
      $.ajax({
        url: 'chartjs/getShopRescueCount.php',
        type: 'GET',
        dataType: 'json',
        data: {
          fromDate,
          toDate
        },
        success: function(res) {

          const series = [
            {
              name: 'Shop Unit',
              data: res.shop
            },
            {
              name: 'Rescue Unit',
              data: res.rescue
            }
          ];

          if (!chart4) {
            const options4 = {
              series: series,
              chart: {
                height: 350,
                type: 'line',
                toolbar: { show: false },
                zoom: { enabled: true }
              },
              stroke: {
                curve: 'smooth',
                width: 3
              },
              dataLabels: {
                enabled: true
              },
              title: {
                text: 'Shop vs Rescue Units by Date',
                align: 'left'
              },
              xaxis: {
                categories: res.dates,
                title: { text: 'Date' }
              },
              yaxis: {
                title: { text: 'Number of Units' },
                min: 0
              },
              legend: {
                position: 'top'
              }
            };

            chart4 = new ApexCharts(document.querySelector("#chart4"), options4);
            chart4.render();
          } else {
            chart4.updateOptions({
              xaxis: { categories: res.dates }
            });

            chart4.updateSeries(series);
          }
        }
      });
    }

    // 🔹 initial load
    loadShopRescueChart();

    // 🔹 Apply filter
    $('#submit').on('click', function () {
      const fromDate = $('#fromDate').val();
      const toDate = $('#toDate').val();
      loadShopRescueChart(fromDate, toDate);
    });
    
     // ✅ Load trip counts with same filter
          function loadTripCounts(fromDate = '', toDate = '') {
            fetch(`php/fetch/get_trip_counts1.php?fromDate=${fromDate}&toDate=${toDate}`)
              .then(res => res.json())
              .then(data => {
                document.getElementById("dailyCount").innerText = data.daily.toLocaleString();
                document.getElementById("avgCount").innerText = data.average.toLocaleString();
                document.getElementById("totalCount").innerText = data.total.toLocaleString();
              })
              .catch(err => console.error('Error loading trip counts:', err));
          }

          // ✅ Form submission (filter both chart + trip counts)
          document.getElementById('filterForm').addEventListener('submit', e => {
            e.preventDefault();
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;
            loadTripCounts(fromDate, toDate);
          });

          loadTripCounts();

          // ✅ Optional auto-refresh every 30s
          setInterval(() => {
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;
            loadTripCounts(fromDate, toDate);
          }, 30000);
  </script>

  </html>
<?php
} else {
  header("Location: index.php?route=login");
  exit();
} ?>
