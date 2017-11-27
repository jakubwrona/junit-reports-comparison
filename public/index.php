<?php
    $config = include('../config/app.php');

    $res = [];
    $total = [];
    $features = [];

    foreach ($config['reports'] as $report) {
        $total[$report['label']] = 0;
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        try {
            $doc->loadXML(trim(file_get_contents($report['path'])));
        } catch (Exception $e) {
            die($e->getMessage());
        }
        $domXpath = new DOMXPath($doc);
        $tests = $domXpath->query($config['query']);
        foreach ($tests as $item) {
            $counter = 2;
            $feature = substr(strrchr($item->getAttribute('file'), '/'), 1);
            $name = ltrim(str_replace('&', 'amp;', strrchr($item->getAttribute('name'), '|')), '| ');
            while (isset($res[$feature][$name][$report['label']])) {
                $name = strrchr($item->getAttribute('name'), '|') . ' example '.$counter++;
            }
            $res[$feature][$name][$report['label']] = $item->getAttribute('time');
            $total[$report['label']]+=$item->getAttribute('time');
            $features[$feature][$report['label']]+=$item->getAttribute('time');
        }
    }
?>
<!doctype html>
<html lang="en">
  <head>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js?chof=validate"></script>
    <script type="text/javascript">
      google.charts.load("current", {"packages":["corechart", "bar"]});
      <?php
      $counter = 1;
      echo "\n".'google.charts.setOnLoadCallback(drawMainChart);';
      /*foreach ($res as $feature => $tests) {
          echo "\n".'google.charts.setOnLoadCallback(drawChart_' . $counter . ');';
          echo "\n".'google.charts.setOnLoadCallback(drawChart2_' . $counter++ . ');';
      }*/
      ?>

      function drawMainChart() {
          var data = google.visualization.arrayToDataTable([
              ["API", "Total response time"],
              <?php
              $subCounter = 1;
              foreach ($total as $label => $value) {
                  echo '["' . $label . '", ' . $value . ']';
                  if ($subCounter < count($total)) {
                      echo ',';
                  }
                  $subCounter++;
              }
              ?>]);
          var options = {
              title: "Total execution time",
              is3D: true
          };
          var chart = new google.visualization.PieChart(document.getElementById("mainPieChart"));
          chart.draw(data, options);
      }
      <?php
        $counter = 1;
        foreach ($res as $feature => $tests) {
            echo "\n\n".'function drawChart_' . $counter . '() {';
            echo "\n".'var data = google.visualization.arrayToDataTable([';
            echo '["API", "Total response time"],';
            $ifLastCounter = 1;
            foreach ($features[$feature] as $label => $value) {
                if ($ifLastCounter < count($config['reports'])) {
                    echo '["' . $label . '", ' . $value . '],';
                } else {
                    echo '["' . $label . '", ' . $value . ']';
                }
                $ifLastCounter++;
            }
            echo ']);';

            echo "\n".'var options = {
              title: "Total execution time",
              is3D: true
          };';

            echo "\n".'var chart = new google.visualization.PieChart(document.getElementById("featureChart_' . $counter . '")); ';
            echo "\n".'chart.draw(data, options);';
            echo "\n".'}';
            echo "\n\n".'function drawChart2_' . $counter . '() {';
            echo "\n".'var data = google.visualization.arrayToDataTable([';
            echo json_encode(array_merge(['Example'], array_column($config['reports'], 'label'))).','."\n";
            $ifLastCounter = 1;
            foreach ($tests as $example => $data) {
                echo json_encode(array_merge([$example], array_values($data)), JSON_NUMERIC_CHECK);
                if ($ifLastCounter < count($res[$feature])) {
                    echo ','."\n";
                }
                $ifLastCounter++;
            }
            echo ']);';
            /*echo "\n".'var formatter = new google.visualization.NumberFormat({
              fractionDigits: 3,
              suffix: " s"
        });';
            for ($i = 1; $i <= count($config['reports']); $i++) {
                echo "\n".'formatter.format(data, ' . $i . ');';
            }*/
            echo "\n".'var options = {
          chart: {
            title: "' . $feature . ' response times comparison"
          },
          bars: "horizontal", // Required for Material Bar Charts.
          hAxis: {
              gridlines: {
                  count: 30
              }}
        };';
            echo "\n".'var chart = new google.charts.Bar(document.getElementById("barchartMaterial_' . $counter . '"));';
            echo "\n".'chart.draw(data, google.charts.Bar.convertOptions(options));';
            echo "\n".'}';
            $counter++;
        }
        ?>
    </script>
    <script type="text/javascript" src="//code.jquery.com/jquery-1.12.4.js"></script>
    <script type="text/javascript" src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
    <style type="text/css">
        td.right {
            text-align: right;
        }  
    </style>
    <title>Compare tests results from the performance perspective</title>
  </head>
  <div>
    <div id="tabs">
        <ul>
            <li><a href="#tabs-0">All features</a></li>
            <?php
            $counter = 1;
            foreach ($res as $feature => $data) {
                echo '<li><a href="#tabs-'.$counter++.'">'.$feature.'</a></li>';
            } ?>
        </ul>
        <div id="tabs-0">
            <div id="mainPieChart" style="width: 100%; height: 500px;"></div>
        </div>
        <?php
        $counter = 1;
        foreach ($res as $feature => $data) {
            echo '<div id="tabs-' . $counter . '">';
            echo '<div id="featureChart_' . $counter . '" style="width: 1200px; height: 500px;"></div><br>';
            echo '<div id="barchartMaterial_' . $counter . '" style="width: 1200px; height: 600px;"></div><br>';
            echo '<div class="content">';
                echo '<table id="example_' . $counter . '" width="100%" cellspacing="0">'; ?>
                    <thead>
                    <tr>
                        <th>API</th>
                        <?php foreach ($config['reports'] as $report) {
                            echo '<th>' . $report['label'] . '</th>';

                        }
                        foreach ($config['reports'] as $report) {
                            if ($report['label'] != $config['primary']) {
                                echo '<th>save vs ' . $config['primary'] . ' (ms)</th>';
                                echo '<th>save % vs ' . $config['primary'] . '</th>';
                            }
                        } ?></tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($data as $example => $results) {
                        echo "\n" . '<tr><td>' . $example . '</td>';
                        foreach ($results as $label => $value) {
                            echo '<td class="right">' . sprintf('%1.2f', $value * 1000) . '</td>';
                        }
                        foreach ($results as $label => $value) {
                            if ($label !== $config['primary']) {
                                echo '<td class="right">' . sprintf('%1.2f', ($results[$config['primary']] - $value) * 1000)
                                    . '</td>';
                                echo '<td class="right">' . sprintf('%1.2f',
                                        ((($results[$config['primary']] - $value) / $results[$config['primary']]) * 100))
                                    . '%</td>';
                            }
                        }
                        echo '</tr>';
                    }
                    ?>
                    </tbody>
                    </table>
            </div>
    </div>
  <?php
  $counter++;
  }
  ?>
    <script type="text/javascript">
        $(document).ready(function() {
            <?php
            $counter = 1;
            foreach ($res as $feature) {
                echo '$("#example_'.$counter++.'").DataTable({"pageLength": '.$config['table']['pageLength'].'});';
            } ?>

            $("#tabs").tabs({
                activate: function (event, ui) {

                }
            });

            $("#tabs").on('tabsactivate', function (event, ui) {
                var active = $('#tabs').tabs('option', 'active');
                var counter = $("#tabs ul>li a").eq(active).attr("href").substring(6);
                window["drawChart_"+counter]();
                window["drawChart2_"+counter]();
            });
        });
    </script>
  </body>
</html>