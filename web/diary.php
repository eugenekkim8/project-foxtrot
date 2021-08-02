<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <title>foxtrot | a web-based mental health diary</title>
  </head>
  <script>

    function updateTextInput(val) {
      document.getElementById('rangeValue').value=val; 
    }

  </script>
  <body class="py-4">
    <div class="container">

        <?php

            echo "<h1>".date("d F Y")."</h1>";

        ?>

         <form action="diary.php" method="GET">
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
            <textarea class="form-control" id="comments" name="comment" placeholder="Today was a wonderful/terrible day." rows="3"></textarea>
          </div>

        <?php

            if (isset($_GET["p"])){
                echo '<input type="hidden" name="p" value="' . $_GET["p"] . '"> <button type="submit" class="btn btn-primary mb-3">Submit</button>';

                if (isset($_GET["score"])){
                    $dbopts = parse_url(getenv('DATABASE_URL'));

                    $connect_str = "host = " . $dbopts["host"] . " port = " . $dbopts["port"] . " dbname = " . ltrim($dbopts["path"], "/") . " user = " . $dbopts["user"] . " password = " . $dbopts["pass"];

                    $conn = pg_connect($connect_str) or die("Could not connect" . pg_last_error());

                    $query = "INSERT INTO diaries (password, diary_ts, score, comment) VALUES ($1, NOW(), $2, $3)";
                    $results = pg_query_params($conn, $query, array($_GET["p"], $_GET["score"], $_GET["comment"])) or die ("Query failed:" . pg_last_error());

                    echo('<div class="alert alert-success" role="alert">Entry submitted!</div>');
                }

            } else {
                echo '<div class="alert alert-danger" role="alert">No user specified. Please use the link sent in the daily message.</div>';
            }


        ?>
          
        </form>
        <hr>
        <h2>Past entries:</h2>
        <table class="table">
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

                $query = "SELECT score, comment, to_char(diary_ts, 'DD Mon YY') AS diary_date FROM diaries WHERE password = '" . $_GET["p"] . "' ORDER BY diary_ts DESC";
                $results = pg_query($query) or die ("Query failed:" . pg_last_error());

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
        <footer class="pt-5 my-5 text-muted border-top">
          &copy; 2021 Eugene K. Kim &middot; Hosted on Heroku & GitHub
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

  </body>
</html>
