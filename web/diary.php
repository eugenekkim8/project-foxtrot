<?php
    // post-redirect-get mechanism to avoid double entries
    // form can be submitted only if p is valid, so no need to redo verification

    // establish connection
    $dbopts = parse_url(getenv('DATABASE_URL'));

    $connect_str = "host = " . $dbopts["host"] . " port = " . $dbopts["port"] . " dbname = " . ltrim($dbopts["path"], "/") . " user = " . $dbopts["user"] . " password = " . $dbopts["pass"];

    $conn = pg_connect($connect_str) or die("Could not connect" . pg_last_error());

    $alert_text = "";

    // switch subscription status if user has requested
    if (isset($_POST["toggleSubscribe"])){
        $query = "UPDATE users SET is_active = NOT(is_active) WHERE password = $1";
        $results = pg_query_params($conn, $query, array($_GET["p"])) or die ("Query failed:" . pg_last_error());

        $query = "SELECT is_active FROM users WHERE password = $1";
        $results = pg_query_params($conn, $query, array($_GET["p"])) or die ("Query failed:" . pg_last_error());
        
        $this_user = pg_fetch_array($results); // only one user should be returned because password must be UNIQUE
        $msg_text = ($this_user["is_active"] == 't') ? 'subscribed' : 'unsubscribed';
        $alert_text = 'You have successfully ' . $msg_text . '!';
    }

    // if user has clicked on submit button
    if (isset($_POST["submitButton"])){
        
        $query = "INSERT INTO diaries (password, diary_ts, score, comment, local_ts) VALUES ($1, NOW(), $2, $3, $4)";
        $results = pg_query_params($conn, $query, array($_GET["p"], $_POST["score"], $_POST["comment"], $_POST["local_date"])) or die ("Query failed:" . pg_last_error());

        $alert_text = 'Entry submitted!';
    }

    if ($_POST){
        header("Location: " . $_SERVER['REQUEST_URI'] . "&alert_text=" . $alert_text); 
        exit();
    }

?>


<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.0.1/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">

    <title>foxtrot | a web-based mental health tracker</title>
  </head>
  <script>

    function updateTextInput(val) {
      document.getElementById('rangeValue').value=val; 
    }

  </script>
  <body class="py-4">
    <div class="container">

        <h1 id="today_date"></h1>

        <?php

            if (isset($_GET["p"])){
                echo '<form action="diary.php?p=' . $_GET["p"] . '" method="POST">';
            } else{
                echo '<form>';
            }

        ?>

          <input type="hidden" name="local_date" id="local_date">
          <div class="row mb-3">
            <div class="col-9">
              <label for="score" class="form-label">How was your day? (0 = worst, 10 = best)</label>
              <input type="range" class="form-range" id="score" name="score" min="0" max="10" step="0.5" onchange="updateTextInput(this.value);">
            </div>
            <div class="col-3 align-middle">
              <input type="text" class="form-control" id="rangeValue" value="5" disabled readonly>
            </div>
          </div>
          <div class = "mb-3">
            <label for="comments" class="form-label">Tell me more:</label>
            <textarea class="form-control" id="comments" name="comment" placeholder="Today was a wonderful/terrible day." maxlength="255" rows="3"></textarea>
          </div>

        <?php

            if (isset($_GET["p"])){

                // see if there is a user with password = p, and if so, subscription status

                $query = "SELECT is_active FROM users WHERE password = $1";
                $results = pg_query_params($conn, $query, array($_GET["p"])) or die ("Query failed:" . pg_last_error());

                // if no such user, display error message

                if (pg_num_rows($results) == 0){

                    echo '<div class="alert alert-danger" role="alert">No user with the specified key. Please use the link sent in your daily message.</div>';

                } else { // otherwise, display submit button and (un)subscribe button
                    
                    $this_user = pg_fetch_array($results); // only one user should be returned because password must be UNIQUE
                    $user_active = ($this_user["is_active"] == 't') ? TRUE : FALSE;

                    echo '<input type="hidden" name="p" value="' . $_GET["p"] . '"> <button type="submit" class="btn btn-primary" name="submitButton" value="set">Submit</button> ';

                    $button_text = $user_active ? 'Unsubscribe' : 'Subscribe';

                    echo '<button type="submit" class="btn btn-outline-secondary" name="toggleSubscribe" value="set">' . $button_text . '</button>';

                    
                    if (isset($_GET["alert_text"])){
                        echo '<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">' . $_GET["alert_text"] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    }

                }

            } else {
                echo '<div class="alert alert-danger" role="alert">No user specified. Please use the link sent in your daily message.</div>';
            }

        echo ('</form>');

        ?>
         
        <hr>
        <div class="row">
          <div class="col-6"><h2>Past entries:</h2></div>
          <div class="col-6">
            <ul class="nav nav-pills justify-content-end" role="tablist">
              <li class="nav-item">
                <button class="nav-link active" id="pills-table-tab" data-bs-toggle="pill" data-bs-target="#pills-table" type="button" role="tab" aria-controls="pills-table" aria-selected="true"><i class="bi-table"></i></button>
              </li>
              <li class="nav-item">
                <button class="nav-link" id="pills-graph-tab" data-bs-toggle="pill" data-bs-target="#pills-graph" type="button" role="tab" aria-controls="pills-graph" aria-selected="false"><i class="bi-graph-up"></i></button>
              </li>
            </ul>
          </div>
        </div>
        <div class="tab-content" id="pills-tabContent">
          <div class="tab-pane fade show active" id="pills-table" role="tabpanel" aria-labelledby="pills-table-tab">
            <table class="table" id="entries">
              <thead>
                <tr>
                  <th class="col-2" scope="col">Date</th>
                  <th class="col-2" scope="col">Score</th>
                  <th class="col-8" scope="col">Comments</th>
                </tr>
              </thead>

            <?php
                if (isset($_GET["p"])){
                    $dbopts = parse_url(getenv('DATABASE_URL'));

                    $connect_str = "host = " . $dbopts["host"] . " port = " . $dbopts["port"] . " dbname = " . ltrim($dbopts["path"], "/") . " user = " . $dbopts["user"] . " password = " . $dbopts["pass"];

                    $conn = pg_connect($connect_str) or die("Could not connect" . pg_last_error());

                    $query = "SELECT score, comment, to_char(local_ts, 'DD Mon YYYY') AS diary_date FROM diaries WHERE password = $1 ORDER BY diary_ts DESC";
                    $results = pg_query_params($conn, $query, array($_GET["p"])) or die ("Query failed:" . pg_last_error());

                    while ($this_entry = pg_fetch_array($results)){

                        $this_score = $this_entry["score"];
                        $color = "";

                        if ($this_score  <= 3.5){
                            $color = "table-danger";
                        } elseif ($this_score <= 6.5){
                            $color = "table-warning";
                        } else {
                            $color = "table-success";
                        }

                        echo '<tr>';
                        echo '<th scope="row">' . $this_entry["diary_date"] . '</th>';
                        echo '<td class="' . $color . '">' . $this_entry["score"] . '</td>';
                        echo '<td>' . $this_entry["comment"] . '</td>';
                        echo '</tr>';

                    }
                }
            ?>

            </table>
          </div>
          <div class="tab-pane fade" id="pills-graph" role="tabpanel" aria-labelledby="pills-graph-tab">
            <div id="chart-container table-responsive">
              <canvas id="graphCanvas" style="min-height:250px" class="table"></canvas>
            </div>
          </div>
        </div>
        <footer class="pt-5 my-5 text-muted border-top">
          &copy; 2021 Eugene K. Kim &middot; Hosted on Heroku & <a href="https://github.com/eugenekkim8/project-foxtrot" class="link-primary">GitHub</a>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="luxon.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon"></script>
    <script src="jquery-3.6.0.slim.min.js"></script>
    <script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap5.min.js"></script>
    <script>
        var DateTime = luxon.DateTime;
        document.getElementById("today_date").innerHTML = DateTime.now().toFormat('dd LLLL y');
        document.getElementById("local_date").value = DateTime.now().toFormat('MM/dd/yyyy, TT');

        $(document).ready(function() {
            $('#entries').DataTable({
              lengthChange: false,
              searching: false,
              ordering: false
            });
        } );

        $(document).ready(function () {
            showGraph();
        });

        function showGraph() {

            <?php
                if (isset($_GET["p"])){
                    $query = "SELECT AVG(score) AS daily_avg_score, DATE(local_ts) AS local_date FROM diaries WHERE password=$1 GROUP BY DATE(local_ts) ORDER BY DATE(local_ts) ASC";
                    $results = pg_query_params($conn, $query, array($_GET["p"])) or die ("Query failed:" . pg_last_error());

                    $data = array();
                    while ($row = pg_fetch_array($results)) {
                        $data[] = $row;
                    }
                }
            ?>

            var data = <?php echo json_encode($data); ?>;

            var entry_dates = [];
            var scores = [];
            var sma = [null, null, null, null, 6.8, 6.4, 6.5, 6.2, 6.2];

            for (var i in data) {
                entry_dates.push(data[i].local_date);
                scores.push(data[i].daily_avg_score);
            }

            var chartdata = {
                labels: entry_dates,
                datasets: [
                    {
                        label: 'Daily Score',
                        backgroundColor: '#49e2ff',
                        borderColor: '#46d5f1',
                        hoverBackgroundColor: '#CCCCCC',
                        hoverBorderColor: '#666666',
                        data: scores
                    },
                    {
                        label: '5d Moving Average',
                        backgroundColor: '#2a7fb8',
                        borderColor: '#246d9e',
                        //backgroundColor: Utils.CHART_COLORS.blue,
                        //borderColor: Utils.transparentize(Utils.CHART_COLORS.blue, 0.5),
                        hoverBackgroundColor: '#CCCCCC',
                        hoverBorderColor: '#666666',
                        data: sma
                    }
                ]
            };

            var graphTarget = document.getElementById("graphCanvas");

            var lineGraph = new Chart(graphTarget, {
              type: 'line',
              data: chartdata,
              options: {
                scales: {
                  x: {
                    type: 'time',
                    time: {
                      unit: 'day',
                      tooltipFormat: 'DD'
                    }
                  },
                  y: {
                    type: 'linear',
                    grace: '10%'
                  }
                },
                interaction: {
                  mode: 'index'
                },
                responsive: true,
                maintainAspectRatio: false
              }
            });
        }

    </script>

  </body>
</html>
