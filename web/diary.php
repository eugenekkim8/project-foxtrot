<?php
    // post-redirect-get mechanism to avoid double entries
    // form can be submitted only if p is valid, so no need to redo verification

    require '../vendor/autoload.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    // establish connection
    $dbopts = parse_url(getenv('DATABASE_URL'));

    $connect_str = "host = " . $dbopts["host"] . " port = " . $dbopts["port"] . " dbname = " . ltrim($dbopts["path"], "/") . " user = " . $dbopts["user"] . " password = " . $dbopts["pass"];

    $conn = pg_connect($connect_str) or die("Could not connect" . pg_last_error());

    $alert_text = "";
    $alert_type = 0;

    // switch subscription status if user has requested
    if (isset($_POST["toggleSubscribe"])){
        $query = "UPDATE users SET is_active = NOT(is_active) WHERE password = $1";
        $results = pg_query_params($conn, $query, array($_GET["p"])) or die ("Query failed:" . pg_last_error());

        $query = "SELECT is_active FROM users WHERE password = $1";
        $results = pg_query_params($conn, $query, array($_GET["p"])) or die ("Query failed:" . pg_last_error());
        
        $this_user = pg_fetch_array($results); // only one user should be returned because password must be UNIQUE
        $msg_text = ($this_user["is_active"] == 't') ? 'subscribed' : 'unsubscribed';
        $alert_text = 'You have successfully ' . $msg_text . '!';

        header("Location: " . $_SERVER['REQUEST_URI'] . "&alert_text=" . $alert_text); 
        exit();
    }

    // if user has clicked on submit button
    if (isset($_POST["submitButton"])){
        
        $query = "INSERT INTO diaries (password, diary_ts, score, comment, local_ts) VALUES ($1, NOW(), $2, $3, $4)";
        $results = pg_query_params($conn, $query, array($_GET["p"], $_POST["score"], $_POST["comment"], $_POST["local_date"])) or die ("Query failed:" . pg_last_error());

        $alert_text = 'Entry submitted!';

        header("Location: " . $_SERVER['REQUEST_URI'] . "&alert_text=" . $alert_text); 
        exit();
    }

    // if user wants to share their scores

    if (isset($_POST["shareButton"])){
        
        $query = "SELECT id FROM users where phone_num = $1";
        $results = pg_query_params($conn, $query, array($_POST["phoneNum"])) or die ("Query failed:" . pg_last_error());

        if (pg_num_rows($results) == 0){ // no user with that phone number

            $alert_text = 'No user with that phone number!';
            $alert_type = 'alert-warning';

        } else { // otherwise, see if recipient is a user

            $recipient = pg_fetch_array($results); // only one user should be returned because password must be UNIQUE
            $recipient_id = $recipient["id"];

            if($recipient_id == $_POST["sender_id"]){ // prohibit sharing with self
                $alert_text = 'Can\'t share a score with yourself!';
                $alert_type = 'alert-danger';

            } else { //see if scores are already shared

                $query = "SELECT id FROM shares where sender_id = $1 AND recipient_id = $2";
                $results = pg_query_params($conn, $query, array($_POST["sender_id"], $recipient_id)) or die ("Query failed:" . pg_last_error());

                if (pg_num_rows($results) != 0){

                    $alert_text = 'Scores are already shared!';
                    $alert_type = 'alert-danger';

                } else{

                    $query = "INSERT INTO shares (sender_id, recipient_id, share_ts) VALUES ($1, $2, NOW())";
                    $results = pg_query_params($conn, $query, array($_POST["sender_id"], $recipient_id)) or die ("Query failed:" . pg_last_error());

                    $format_num = '('.substr($_POST["phoneNum"], 0, 3).') '.substr($_POST["phoneNum"], 3, 3).'-'.substr($_POST["phoneNum"],6);

                    $alert_text = 'Score shared with '. $format_num . '!';
                    $alert_type = 'alert-success';

                }
                
            }
        }

        header("Location: " . $_SERVER['REQUEST_URI'] . "&share_text=" . $alert_text . "&share_alert_type=" . $alert_type); 
        exit();
    }

    if(isset($_POST["diaryId"])){ //if user wants to heart someone's post

        # insert
        $query = "INSERT INTO reactions (sender_id, diary_id, seen, reaction_ts) VALUES ($1, $2, 'F', NOW())";
        $results = pg_query_params($conn, $query, array($_POST["sender_id"], $_POST["diaryId"])) or die ("Query failed:" . pg_last_error());
        $alert_text = "Heart sent!";
        $alert_type = 'alert-success';

        # redirect
        header("Location: " . $_SERVER['REQUEST_URI'] . "&heart_text=" . $alert_text . "&heart_alert_type=" . $alert_type); 
        exit();

    }

    if(isset($_POST["email"])){ // if user is inviting someone

        # connect to server

        # send email

        # redirect
        $alert_text = 'Invitation sent to ' . $_POST["address"]. '!';

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

<!-- Score and comment entry -->

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

<!-- Dashboard -->
         
        <hr>
        <div class="row">
          <div class="col-5"><h2>Dashboard</h2></div>
          <div class="col-7">
            <ul class="nav nav-pills justify-content-end" role="tablist">
                <?php
                    if(isset($_GET["share_text"])){ // if they just attempted to share, activate social tab
                        echo('<li class="nav-item">
                                <button class="nav-link" id="pills-table-tab" data-bs-toggle="pill" data-bs-target="#pills-table" type="button" role="tab" aria-controls="pills-table" aria-selected="false"><i class="bi-table"></i></button>
                              </li>
                              <li class="nav-item">
                                <button class="nav-link" id="pills-graph-tab" data-bs-toggle="pill" data-bs-target="#pills-graph" type="button" role="tab" aria-controls="pills-graph" aria-selected="false"><i class="bi-graph-up"></i></button>
                              </li>
                              <li class="nav-item">
                                <button class="nav-link active" id="pills-social-tab" data-bs-toggle="pill" data-bs-target="#pills-social, #pills-social-head" type="button" role="tab" aria-controls="pills-social" aria-selected="true"><i class="bi-people-fill"></i></button> 
                              </li>');
                    } else{ // show default table tab
                        echo('<li class="nav-item">
                                <button class="nav-link active" id="pills-table-tab" data-bs-toggle="pill" data-bs-target="#pills-table" type="button" role="tab" aria-controls="pills-table" aria-selected="true"><i class="bi-table"></i></button>
                              </li>
                              <li class="nav-item">
                                <button class="nav-link" id="pills-graph-tab" data-bs-toggle="pill" data-bs-target="#pills-graph" type="button" role="tab" aria-controls="pills-graph" aria-selected="false"><i class="bi-graph-up"></i></button>
                              </li>
                              <li class="nav-item">
                                <button class="nav-link" id="pills-social-tab" data-bs-toggle="pill" data-bs-target="#pills-social, #pills-social-head" type="button" role="tab" aria-controls="pills-social" aria-selected="false"><i class="bi-people-fill"></i></button> 
                              </li>');

                    }

                ?>

            </ul>
          </div>
        </div>

<!-- Table -->

        <div class="tab-content" id="pills-tabContent">
            <?php
                if(isset($_GET["share_text"]) or isset($_GET["heart_text"])){ // if they just attempted to share or heart, activate social tab
                    echo('<div class="tab-pane fade" id="pills-table" role="tabpanel" aria-labelledby="pills-table-tab">');
                } else{ // show default table tab
                    echo('<div class="tab-pane fade show active" id="pills-table" role="tabpanel" aria-labelledby="pills-table-tab">');
                }
            ?>
          
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

<!-- Graph -->

          <div class="tab-pane fade" id="pills-graph" role="tabpanel" aria-labelledby="pills-graph-tab">
            <div id="chart-container table-responsive">
              <canvas id="graphCanvas" style="min-height:250px" class="table"></canvas>
            </div>
          </div>

<!-- Social -->

          <?php
                if(isset($_GET["share_text"]) or isset($_GET["heart_text"])){ // if they just attempted to share or heart, activate social tab
                    echo('<div class="tab-pane fade show active" id="pills-social" role="tabpanel" aria-labelledby="pills-social-tab">');
                } else{ // show default table tab, with collapsed share accordion
                    echo('<div class="tab-pane fade" id="pills-social" role="tabpanel" aria-labelledby="pills-social-tab">');
                }

                if(isset($_GET["share_text"])){ // if they just attempted to share, activate sharing accordion
                    echo('<div class="accordion mt-3 mb-3" id="shareScores">
                                  <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingOne">
                                      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        Share my scores
                                      </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#shareScores">
                                      <div class="accordion-body">');
                } else{ // show collapsed share accordion
                    echo('<div class="accordion mb-3" id="shareScores">
                              <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                    Share my scores
                                  </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#shareScores">
                                  <div class="accordion-body">');
                }

                if (isset($_GET["p"])){ // prep form for sharing
                    echo '<form action="diary.php?p=' . $_GET["p"] . '" method="POST" class="row g-3 needs-validation" novalidate>';
                } else{
                    echo '<form class="row g-3 needs-validation" novalidate>';
                }

            ?>

                      <div class="col-md-6">
                        <input type="tel" placeholder="Recipient's phone number" class="form-control" name="phoneNum" id="phoneNum" pattern="^\d{10}$" aria-describedby="phoneHelp" required>
                        <div id="phoneHelp" class="form-text">They'll receive your daily scores, but not your comments.</div>
                        <div class="invalid-feedback">Please provide a valid 10-digit phone number (e.g., 1234567890).</div>
                      </div>
                      <div class="col-md-6">

                        <?php

                            if (isset($_GET["p"])){

                                // see if there is a user with password = p, and if so, get user id

                                $query = "SELECT id FROM users WHERE password = $1";
                                $results = pg_query_params($conn, $query, array($_GET["p"])) or die ("Query failed:" . pg_last_error());

                                // if no such user, disable submit button

                                if (pg_num_rows($results) == 0){

                                    echo '<button type="submit" class="btn btn-primary" name="shareButton" value="set" disabled>Submit</button>';

                                } else { // otherwise, display submit button 
                                    
                                    $this_user = pg_fetch_array($results); // only one user should be returned because password must be UNIQUE
                                    $sender_id = $this_user["id"];

                                    echo '<input type="hidden" name="p" value="' . $_GET["p"] . '"><input type="hidden" name="sender_id" value="' . $sender_id . '"> <button type="submit" class="btn btn-primary" name="shareButton" value="set">Submit</button> ';

                                }

                                echo '</div>';

                                if (isset($_GET["share_text"])){ // show alert if share was attempted
                                    if($_GET["share_text"] == "No user with that phone number!"){ // yuck. figure out if a hyperlink can be passed through GET
                                        echo '<div class="alert ' . $_GET["share_alert_type"] . ' alert-dismissible fade show mt-3" role="alert">' . $_GET["share_text"] . ' <a href="" data-bs-toggle="modal" data-bs-target="#invite">Send an email invitation?</a><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                                    } else{
                                        echo '<div class="alert ' . $_GET["share_alert_type"] . ' alert-dismissible fade show mt-3" role="alert">' . $_GET["share_text"] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                                    }
                                }

                            } else {
                                echo '<button type="submit" class="btn btn-primary" name="shareButton" value="set" disabled>Submit</button></div>';
                            }

                        ?>
                    </form>
                  </div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                    Unshare my scores
                  </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#shareScores">
                  <div class="accordion-body">
                    Coming soon!
                  </div>
                </div>
              </div>

            <?php

                echo '<div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            Notifications';

                $query = "SELECT u2.phone_num, to_char(d.local_ts, 'Mon DD') AS diary_date, d.score AS score FROM reactions r 
                            LEFT JOIN diaries d on d.id = r.diary_id
                            LEFT JOIN users u on u.password = d.password
                            LEFT JOIN users u2 on u2.id = r.sender_id
                            WHERE u.password = $1 AND r.seen = 'F'
                            LIMIT 10";
                $results = pg_query_params($conn, $query, array($_GET["p"])) or die ("Query failed:" . pg_last_error());

                // if no new notifications

                if (pg_num_rows($results) == 0){

                    echo '</button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#shareScores">
                              <div class="accordion-body">
                                No new notifications!';

                } else { // otherwise, display new badge and notification alerts, then mark notifications as seen 
                
                    echo '&nbsp;<span class="badge bg-danger">New</span>
                              </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#shareScores">
                              <div class="accordion-body">';

                    while ($this_entry = pg_fetch_array($results)){

                        $format_num = '('.substr($this_entry["phone_num"], 0, 3).') '.substr($this_entry["phone_num"], 3, 3).'-'.substr($this_entry["phone_num"],6);

                        echo '<div class="alert alert-success alert-dismissible fade show mt-3" role="alert"><strong>' . $format_num . '</strong> sends a <i class="bi-heart-fill"></i> for your ' . $this_entry["diary_date"] . ' entry (score ' . $this_entry["score"] . ') <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';

                    }

                    $query = "UPDATE reactions r SET seen = 'T' 
                                FROM diaries d, users u
                                WHERE d.id = r.diary_id AND
                                u.password = d.password AND
                                u.password = $1";

                    $results = pg_query_params($conn, $query, array($_GET["p"])) or die ("Query failed:" . pg_last_error());

                }

                echo '</div></div></div>';

            ?>

            </div>
            <div class="row">              
              <div class="col-md">
                <?php

                    if (isset($_GET["p"])){
                        $query = "SELECT id FROM users WHERE password = $1";
                        $results = pg_query_params($conn, $query, array($_GET["p"])) or die ("Query failed:" . pg_last_error());

                        // if no such user, disable submit

                        if (pg_num_rows($results) == 0){
                            echo '<form action="diary.php?p=' . $_GET["p"] . '" method="POST">';
                        } else { // otherwise, enable submit and store ID
                            echo '<form action="diary.php?p=' . $_GET["p"] . '" method="POST">';
                            $this_user = pg_fetch_array($results); // only one user should be returned because password must be UNIQUE
                            $sender_id = $this_user["id"];

                            echo '<input type="hidden" name="sender_id" value="' . $sender_id . '">';

                        }

                    } else{
                        echo '<form>';
                    }

                    if (isset($_GET["heart_text"])){
                        echo '<div class="alert ' . $_GET["heart_alert_type"] . ' alert-dismissible fade show mt-3" role="alert">' . $_GET["heart_text"] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    }

                ?>

                <table class="table" id="community">
                  <thead>
                    <tr>
                      <th class="col-3" scope="col">Date</th>
                      <th class="col-4" scope="col">User</th>
                      <th class="col-3" scope="col">Score</th>
                      <th class="col-2" scope="col">Tools</th>
                    </tr>
                  </thead>

                  <?php
                    if (isset($_GET["p"])){
                        $dbopts = parse_url(getenv('DATABASE_URL'));

                        $connect_str = "host = " . $dbopts["host"] . " port = " . $dbopts["port"] . " dbname = " . ltrim($dbopts["path"], "/") . " user = " . $dbopts["user"] . " password = " . $dbopts["pass"];

                        $conn = pg_connect($connect_str) or die("Could not connect" . pg_last_error());

                        $query = "SELECT d.id AS id, u.phone_num, to_char(d.local_ts, 'DD Mon YYYY') AS diary_date, d.score, r.id AS reaction FROM diaries d 
                                    LEFT JOIN users u ON u.password = d.password 
                                    LEFT JOIN shares s ON u.id = s.sender_id 
                                    LEFT JOIN users u2 ON u2.id = s.recipient_id
                                    LEFT JOIN reactions r ON r.diary_id = d.id AND r.sender_id = s.recipient_id
                                    WHERE u2.password = $1
                                    ORDER BY d.local_ts DESC
                                    LIMIT 10";
                        $results = pg_query_params($conn, $query, array($_GET["p"])) or die ("Query failed:" . pg_last_error());

                        while ($this_entry = pg_fetch_array($results)){

                            $this_score = $this_entry["score"];
                            $color = "";

                            $format_num = '('.substr($this_entry["phone_num"], 0, 3).') '.substr($this_entry["phone_num"], 3, 3).'-'.substr($this_entry["phone_num"],6);


                            if ($this_score  <= 3.5){
                                $color = "table-danger";
                            } elseif ($this_score <= 6.5){
                                $color = "table-warning";
                            } else {
                                $color = "table-success";
                            }

                            echo '<tr>';
                            echo '<th scope="row">' . $this_entry["diary_date"] . '</th>';
                            echo '<td>' . $format_num . '</td>';
                            echo '<td class="' . $color . '">' . $this_entry["score"] . '</td>';

                            if($this_entry["reaction"] == NULL){
                                echo '<td><button type="submit" name="diaryId" value=' . $this_entry["id"] .' class="btn btn-primary"><i class="bi-heart-fill"></i></button></td>';
                            } else{
                                echo '<td><button type="submit" class="btn btn-primary" disabled><i class="bi-heart-fill"></i></button></td>';
                            }

                            
                            echo '</tr>';

                        }
                    }
                ?>                

                </table>
                </form>
              </div>
            </div> 
          </div>  
        </div>
        <footer class="pt-5 my-5 text-muted border-top">
          &copy; 2021 Eugene K. Kim &middot; Hosted on Heroku & <a href="https://github.com/eugenekkim8/project-foxtrot" class="link-primary">GitHub</a>
          <?php

                if (isset($_GET["p"])){
                    echo '&middot; <a href="comment.php?p=' . $_GET["p"] . '" class="link-secondary">Leave a comment</a>';
                } else {
                    echo '&middot; <a href="comment.php" class="link-secondary">Leave a comment</a>';
                }  

          ?>
        </footer>
    </div>

    <div class="modal fade" id="invite" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Send an email invitation!</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                Invite your friend to foxtrot so you can share scores.
                <?php
                    if (isset($_GET["p"])){ 
                        echo '<form action="diary.php?p=' . $_GET["p"] . '" method="POST" class="mt-3 needs-validation" novalidate>';
                    } else{
                        echo '<form class="mt-3 needs-validation" novalidate>';
                    }
                ?>  
                  <div class="row mb-3"> <div class="col-md-4"> Email address:</div><div class="col-md-8"><input type="email" class="form-control" placeholder="name@example.com" name="address" required> <div class="invalid-feedback">Please provide a valid email address.</div> </div></div>
                  <div class="row"> <div class="col-md-4"> Your name:</div><div class="col-md-8"><input type="text" class="form-control" describedby="nameHelp" name="name" required><div id="nameHelp" class="form-text">We won't save this.</div> </div></div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="email" class="btn btn-primary">Send</button></form>
              </div>
            </div>
          </div>
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

        function padded_moving_average(array, win){
            var result = [];
            for(var k = 0; k < win - 1; k++){
                result.push(null);
            }
            for(var i = 0; i < array.length - win + 1; i++){
                var subset_sum = 0;
                for(var j = i; j < i + win; j++){
                    subset_sum += array[j];
                }
                result.push(subset_sum / win); 
            }
            return result;
        }

        function showGraph() {

            <?php
                if (isset($_GET["p"])){
                    $query = "SELECT * FROM (SELECT AVG(score) AS daily_avg_score, DATE(local_ts) AS local_date FROM diaries WHERE password=$1 GROUP BY DATE(local_ts) ORDER BY DATE(local_ts) DESC LIMIT 50) t ORDER BY t.local_date ASC";
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

            for (var i in data) {
                entry_dates.push(data[i].local_date);
                scores.push(parseFloat(data[i].daily_avg_score));
            }

            var sma = padded_moving_average(scores, 5);

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

    <script>
      (function () {
      'use strict'

      // Fetch all the forms we want to apply custom Bootstrap validation styles to
      var forms = document.querySelectorAll('.needs-validation')

      // Loop over them and prevent submission
      Array.prototype.slice.call(forms)
        .forEach(function (form) {
          form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
              event.preventDefault()
              event.stopPropagation()
            }

            form.classList.add('was-validated')
          }, false)
        })
    })()
    </script>

  </body>
</html>
