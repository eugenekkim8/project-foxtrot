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
         <ul><li>subscribe for daily mental health check-ins: leave whenever </li>
         <li>no identifying information except your phone number</li></ul>

         <?php
			function generateRandomString($length = 10){
    			return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    		}

			if(isset($_POST["phoneNum"])){
				$dbopts = parse_url(getenv('DATABASE_URL'));

				$connect_str = "host = " . $dbopts["host"] . " port = " . $dbopts["port"] . " dbname = " . ltrim($dbopts["path"], "/") . " user = " . $dbopts["user"] . " password = " . $dbopts["pass"];

				$conn = pg_connect($connect_str) or die("Could not connect" . pg_last_error());

        // is there an already existing account?

        // if not, send validation link

				$pass = generateRandomString(); 

				$query = "INSERT INTO users (phone_num, carrier, password, text_consent, is_active, subscribe_ts) VALUES (" . $_POST["phoneNum"] . ", '" . $_POST["carrier"] . "', '" . $pass . "', 'T', 'T', NOW())";
				$results = pg_query($query) or die ("Query failed:" . pg_last_error());

				echo('<div class="alert alert-success" role="alert"><h4 class="alert-heading">Thanks for subscribing!</h4><p class="mb-0">You should receive your first text within 24 hours.</div>');
			}

		?>

         <form method="POST" class="needs-validation" action="index.php" novalidate>
          <div class="form-floating mb-3">
            <input type="tel" placeholder="(123)456-7890" class="form-control" name="phoneNum" id="phoneNum" aria-describedby="phoneHelp" pattern="^\d{10}$" required>
            <label for="phoneNum">Phone number</label>
            <div id="phoneHelp" class="form-text">We'll never share your number with anyone else.</div>
            <div class="invalid-feedback">Please provide a valid 10-digit phone number (e.g., 1234567890).</div>
          </div>
          <div class = "form-floating mb-3">
            <select class="form-select" name="carrier" id="carrier" aria-label="Carrier select" required>
              <option selected disabled value="">Select...</option>
              <option>Verizon</option>
              <option>T-Mobile</option>
              <option>AT&T</option>
              <option>Boost</option>
              <option>Cricket</option>
              <option>Google Fi</option>
              <option>Mint</option>
              <option>Ting</option>
              <option>Visible</option>
            </select>
            <label for="carrier">Your carrier</label>
            <div class="invalid-feedback">Please select a carrier.</div>
          </div>
          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="consent" required>
            <label class="form-check-label" for="consent">I agree to receive text messages at this number.</label>
            <div class="invalid-feedback">This service operates through text messages.</div>
          </div>
          <input type="submit" class="btn btn-primary" value="Submit">
        </form>
        <footer class="pt-5 my-5 text-muted border-top">
          &copy; 2021 Eugene K. Kim &middot; Hosted on Heroku & GitHub
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

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