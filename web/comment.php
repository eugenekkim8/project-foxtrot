<?php
    // post-redirect-get mechanism to avoid double entries
    
    $dbopts = parse_url(getenv('DATABASE_URL'));

    $connect_str = "host = " . $dbopts["host"] . " port = " . $dbopts["port"] . " dbname = " . ltrim($dbopts["path"], "/") . " user = " . $dbopts["user"] . " password = " . $dbopts["pass"];

    $conn = pg_connect($connect_str) or die("Could not connect" . pg_last_error());

    $alert_text = "";

    // if user has clicked on submit button
    if (isset($_POST["submitButton"])){
        
        $query = "INSERT INTO comments (comment, comment_ts) VALUES ($1, NOW())";
        $results = pg_query_params($conn, $query, array($_POST["comment"])) or die ("Query failed:" . pg_last_error());

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

    <title>foxtrot | a web-based mental health tracker</title>
  </head>
  <body class="py-4">
    <div class="container">

        <h2>Submit feedback anonymously</h2>
        <?php

            if (isset($_GET["p"])){
                echo '<a href="diary.php?p=' . $_GET["p"] . '">Return to entries</a>';
                echo '<form action="comment.php?p=' . $_GET["p"] . '" method="POST">';
            } else{
                echo '<form action="comment.php" method="POST">';
            }

        ?>

        <div class = "mb-3">
            <label for="comments" class="form-label">Leave a comment, suggestion, or message:</label>
            <textarea class="form-control" id="comments" name="comment" placeholder="How can this app be more useful?" maxlength="255" rows="3"></textarea>
        </div>
        <button type="submit" class="btn btn-primary" name="submitButton" value="set">Submit</button>

        <?php

            if (isset($_GET["p"])){
                echo '<a href="diary.php?p=' . $_GET["p"] . '" class="btn btn-outline-secondary">Return to my page</a>';
            }      

            if (isset($_GET["alert_text"])){
                echo '<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">' . $_GET["alert_text"] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
               
            echo ('</form>');

        ?>

        </form>      
     
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

  </body>
</html>
