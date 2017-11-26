<?php
    $oldV1doc = new DOMDocument();
    $oldV1doc->preserveWhiteSpace = false;
    //$oldV1doc->loadXML(trim(file_get_contents('/home/kuba/www/malachite-codeception-2/bdd/tests/_output/report-old-v1.xml')));
    $oldV1doc->loadXML(trim(file_get_contents('/home/kuba/www/malachite-codeception-2/bdd/tests/_output/report-old-2.xml')));


    $newV1doc = new DOMDocument();
    $newV1doc->preserveWhiteSpace = false;
    //$newV1doc->loadXML(trim(file_get_contents('/home/kuba/www/malachite-codeception-2/bdd/tests/_output/report-new-v1.xml')));
    $newV1doc->loadXML(trim(file_get_contents('/home/kuba/www/malachite-codeception-2/bdd/tests/_output/report-new-2.xml')));

    $domXpath = new DOMXPath($oldV1doc);
    $tests = $domXpath->query('//testcase');

    $res = [];
    $total = [
            'new' => 0,
            'old' => 0
    ];

    foreach ($tests as $item) {
        $feature = strrchr($item->getAttribute('name'), '/');
        $name = strrchr($item->getAttribute('name'), '|');
        if (isset($res[$feature][$name]['old'])) {
            $name .= ' example 2';
        }
        $res[$name]['old'] = $item->getAttribute('time');
        $total['old']+=$item->getAttribute('time');
    }

    $domXpath = new DOMXPath($newV1doc);
    $tests = $domXpath->query('//testcase');
    foreach ($tests as $item) {
        $feature = strrchr($item->getAttribute('name'), '/');
        $name = strrchr($item->getAttribute('name'), '|');
        if (isset($res[$feature][$name]['new'])) {
            $name .= ' example 2';
        }
        $res[$name]['new'] = $item->getAttribute('time');
        $total['new']+=$item->getAttribute('time');
    }
?>
<!doctype html>
<html lang="en">
  <head>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart', 'bar']});
      google.charts.setOnLoadCallback(drawChart);
      google.charts.setOnLoadCallback(drawChart2);

      function drawChart() {
          var data = google.visualization.arrayToDataTable([
              ['API', 'Total response time'],
              ['SaC OLD', <?php echo $total['old'];?>],
              ['SaC NEW', <?php echo $total['new'];?>]
          ]);

          var options = {
              title: 'Total execution time',
              is3D: true
          };

          var chart = new google.visualization.PieChart(document.getElementById('piechart'));

          chart.draw(data, options);
      }


      function drawChart2() {
        var data = google.visualization.arrayToDataTable([
          ['Example', 'OLD v1', 'NEW v1'],
          <?php
            foreach ($res as $example => $data) {
                echo '[\''.$example.'\', '.$data['old'].','.$data['new'].']';
                if ($example !== $name) {
                    echo ',';
                }
            }
            //echo '[\'TOTAL\', '.$total['old'].', '.$total['new'].']';
            ?>
        ]);
        var formatter = new google.visualization.NumberFormat({
              fractionDigits: 3,
              suffix: ' s'
        });
        formatter.format(data, 1);
        formatter.format(data, 2);

        var options = {
          chart: {
            title: 'Starting and Charging API (SaC) response times comparison',
            subtitle: 'PHP7 Laravel 4 not optimized VS PHP7 Laravel 5 optimized',
          },
          bars: 'horizontal', // Required for Material Bar Charts.
          hAxis: {
              gridlines: {
                  count: 30
              }}
        };

        var chart = new google.charts.Bar(document.getElementById('barchart_material'));

        chart.draw(data, google.charts.Bar.convertOptions(options));
      }
    </script>
    <script type="text/javascript" src="//code.jquery.com/jquery-1.12.4.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
    <style type="text/css">
        td.right {
            text-align: right;
        }  
    </style>
  </head>
  <body>
    <div id="barchart_material" style="width: 100%; height: 600px;"></div>
    <div id="piechart" style="width: 1200px; height: 500px;"></div>
    <div class="content">
        <table id="example" width="100%" cellspacing="0">
            <thead>
            <tr>
                <th>API</th>
                <th>OLD (ms)</th>
                <th>NEW (ms)</th>
                <th>diff (ms)</th>
                <th>diff %</th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($res as $example => $data) {
                echo "\n".'<tr><td>'.$example.'</td><td class="right">'.sprintf('%1.2f', $data['old'] * 1000).'</td><td class="right">'.sprintf('%1.2f', $data['new'] * 1000).'</td>';
                echo '<td class="right">'.sprintf('%1.2f', ($data['old'] - $data['new']) * 1000).'</td><td class="right">'.sprintf('%1.2f', ((($data['old'] - $data['new']) / $data['old']) * 100)).'%</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#example').DataTable();
        });
    </script>
  </body>
</html>
