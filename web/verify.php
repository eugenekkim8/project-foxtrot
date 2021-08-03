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
  <body class="py-4">
    <div class="container">
         <h1>foxtrot: a web-based mental health diary</h1>
         
         <?php

    		if (isset($_GET["p"])){
    			$dbopts = parse_url(getenv('DATABASE_URL'));

    			$connect_str = "host = " . $dbopts["host"] . " port = " . $dbopts["port"] . " dbname = " . ltrim($dbopts["path"], "/") . " user = " . $dbopts["user"] . " password = " . $dbopts["pass"];

    			$conn = pg_connect($connect_str) or die("Could not connect" . pg_last_error());

                $query = "UPDATE users SET verified_num = 'T' WHERE password = $1";
                $results = pg_query_params($conn, $query, array($_GET["p"])) or die ("Query failed:" . pg_last_error());

                if (pg_affected_rows($results) == 0){

                    echo '<div class="alert alert-danger" role="alert">No user with the specified key. Please use the link sent in your activation message.</div>';

                } else { 

                    echo '<div class="alert alert-success" role="alert"><h4 class="alert-heading">Thank you!</h4><p class="mb-0">Your account has been activated. Expect a check-in text within 24 hours.</div>';

                }

    		}

    	?>

        <footer class="pt-5 my-5 text-muted border-top">
          &copy; 2021 Eugene K. Kim &middot; Hosted on Heroku & GitHub
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

  </body>
</html>