(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.chartLoader = {
    attach: function (context, settings) {
      $('#visualizador_programas').once().each(function() {
        var links = [];
        $(document).ready(function() {

          $.ajax({
            type    : 'POST',
            dataType : 'json',
            url     : '/visualizador-data',
            success : function(data) {
              var visualizador_programas = document.getElementById('visualizador_programas');
              visualizador_programas.classList.add("row");
              window.Chart.defaults.font.size = 14;
              var colores = [];
              for (var i in data.base.data) {
                colores.push(dynamicColors());
              }
              // BASE
              var base = document.createElement("canvas");
              var div1 = document.createElement("div");
              div1.appendChild(base);
              div1.classList.add("col-md-6");
              base.id = "baseChart";
              visualizador_programas.appendChild(div1);
              base.getContext('2d');
              window.baseChart = new Chart(base, {
                type: 'bar',
                data: {
                  labels: data.base.labels,
                  datasets: [{
                    label: 'Programas',
                    data: data.base.data,
                    url: data.base.url,
                    backgroundColor: colores,
                    borderColor: 'rgba(200, 200, 200, 0.75)',
                    hoverBorderColor: 'rgba(200, 200, 200, 1)',
                    borderWidth: 1
                  }]
                },
                options: {
                  indexAxis: 'y',
                  responsive: true,
                  plugins: {
                    datalabels: {
                      anchor: 'end',
                      align: 'top',
                      formatter: Math.round,
                      font: {
                        weight: 'bold'
                      }
                    },
                    title: {
                      display: true,
                      text: 'Programas'
                    },
                    legend: {
                      display: false
                    }
                  },
                  scales: {
                    y: {
                      beginAtZero: true
                    },
                  }
                }
              });
              base.onclick = (evt) => {
                const res = window.baseChart.getElementsAtEventForMode(evt,'nearest',{ intersect: true },true);
                if (res.length === 0) {
                  return;
                }
                window.open(data.base.url[res[0].index], '_blank').focus();
              };

              // RC
              var rc = document.createElement("canvas");
              var div2 = document.createElement("div");
              div2.appendChild(rc);
              div2.classList.add("col-md-6");
              rc.id = "rc";
              visualizador_programas.appendChild(div2);
              rc.getContext('2d');
              colores = [];
              for (var i in data.rc.labels) {
                colores.push(dynamicColors());
              }
              window.rc = new Chart(rc, {
                type: 'bar',
                data: {
                  labels: data.rc.labels,
                  datasets: [{
                    label: "Vencimientos1 Registro <br>Calificado en Programas Académicos",
                    data: data.rc.data,
                    url: data.rc.url,
                    backgroundColor: colores,
                    borderColor: 'rgba(200, 200, 200, 0.75)',
                    hoverBorderColor: 'rgba(200, 200, 200, 1)',
                    borderWidth: 1
                  }]
                },
                options: {
                  indexAxis: 'y',
                  responsive: true,
                  plugins: {
                    datalabels: {
                      anchor: 'end',
                      align: 'top',
                      formatter: Math.round,
                      font: {
                        weight: 'bold'
                      }
                    },
                    title: {
                      display: true,
                      text: 'Vencimientos Registro Calificado en Programas Académicos',
                    },
                    legend: {
                      display: false
                    }
                  },
                  scales: {
                    y: {
                      beginAtZero: true
                    }
                  },

                }
              });
              rc.onclick = (evt) => {
                const res = window.rc.getElementsAtEventForMode(evt,'nearest',{ intersect: true },true);
                if (res.length === 0) {
                  return;
                }
                window.open(data.rc.url[res[0].index], '_blank').focus();
              };

              // AC
              var ac = document.createElement("canvas");
              var div3 = document.createElement("div");
              div3.appendChild(ac);
              div3.classList.add("col-md-6");
              ac.id = "ac";
              colores = [];
              for (var i in data.ac.labels) {
                colores.push(dynamicColors());
              }
              visualizador_programas.appendChild(div3);
              ac.getContext('2d');
              window.ac = new Chart(ac, {
                type: 'bar',
                data: {
                  labels: data.ac.labels,
                  datasets: [{
                    label: 'Vencimientos Acreditaciones en Programas Académicos',
                    data: data.ac.data,
                    backgroundColor: colores,
                    url: data.ac.url,
                    borderColor: 'rgba(200, 200, 200, 0.75)',
                    hoverBorderColor: 'rgba(200, 200, 200, 1)',
                    borderWidth: 1
                  }]
                },
                options: {
                  indexAxis: 'y',
                  responsive: true,
                  plugins: {
                    datalabels: {
                      anchor: 'end',
                      align: 'top',
                      formatter: Math.round,
                      font: {
                        weight: 'bold'
                      }
                    },
                    title: {
                      display: true,
                      text: 'Vencimientos Acreditaciones Programas Académicos',
                    },
                    legend: {
                      display: false
                    }
                  },
                  scales: {
                    y: {
                      beginAtZero: true
                    }
                  }
                }
              });
              ac.onclick = (evt) => {
                const res = window.ac.getElementsAtEventForMode(evt,'nearest',{ intersect: true },true);
                if (res.length === 0) {
                  return;
                }
                window.open(data.ac.url[res[0].index], '_blank').focus();
              };

              // AC
              var men = document.createElement("canvas");
              var div4 = document.createElement("div");
              div4.appendChild(men);
              div4.classList.add("col-md-6");
              men.id = "men";
              visualizador_programas.appendChild(div4);
              men.getContext('2d');
              colores = [];
              for (var i in data.men.labels) {
                colores.push(dynamicColors());
              }
              window.men = new Chart(men, {
                type: 'bar',
                data: {
                  labels: data.men.labels,
                  datasets: [{
                    label: 'Programas en espera del MEN',
                    data: data.men.data,
                    url: data.men.url,
                    backgroundColor: colores,
                    borderColor: 'rgba(200, 200, 200, 0.75)',
                    hoverBorderColor: 'rgba(200, 200, 200, 1)',
                    borderWidth: 1
                  }]
                },
                options: {
                  indexAxis: 'y',
                  responsive: true,
                  plugins: {
                    datalabels: {
                      anchor: 'end',
                      align: 'top',
                      formatter: Math.round,
                      font: {
                        weight: 'bold'
                      }
                    },
                    title: {
                      display: true,
                      text: 'Programas en espera del MEN'
                    },
                    legend: {
                      display: false
                    }
                  },
                  scales: {
                    y: {
                      beginAtZero: true
                    }
                  }
                }
              });
              men.onclick = (evt) => {
                const res = window.men.getElementsAtEventForMode(evt,'nearest',{ intersect: true },true);
                if (res.length === 0) {
                  return;
                }
                window.open(data.men.url[res[0].index], '_blank').focus();
              };
            }
          });
        });
      });
    }
  };
})(jQuery, Drupal);

var dynamicColors = function() {
  var r = Math.floor(Math.random() * 255);
  var g = Math.floor(Math.random() * 255);
  var b = Math.floor(Math.random() * 255);
  return "rgb(" + r + "," + g + "," + b + ")";
};
