<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
	<link rel="stylesheet" href="login.css">
	<title>Login</title>
</head>
<body>
	<div class="container py-5">
		<div class="row justify-content-center">
			<div class="col-lg-5 col-md-7">
				<div class="card brand-card">
					<div class="card-body p-4 p-md-5">
						<div class="d-flex align-items-center gap-2 mb-3">
							<div>
								<h1 class="h3 mb-0">Sign in</h1>
							</div>
						</div>

						<form action="login.php" method="post">
							<div class="mb-3">
								<label class="form-label" for="username">Username</label>
								<input class="form-control" type="text" id="username" name="username" placeholder="janedoe" required>
							</div>
							<div class="mb-3">
								<label class="form-label" for="password">Password</label>
								<input class="form-control" type="password" id="password" name="password" placeholder="Enter your password" required>
							</div>
                            <button type="submit" name="sub" class="btn brand-btn text-white w-100 mt-2">Login</button>
						</form>

						<div class="text-center mt-4">
							<span class="text-muted">No account yet?</span>
							<a class="text-decoration-none" href="register.php" style="color: #b52232">Create one</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>


<?php

require_once 'dbelection.php';
session_start();


// button func
if(isset($_POST['sub'])) {
    //user input
    $username = $_POST['username'];
    $password = md5($_POST['password']);

    $loginsql = "Select * from tbl_users where username = '" . $username ."' and password = '". $password ."'";

    $result = $conn->query($loginsql);

    //check if there is a match record
    if ($result->num_rows == 1) {
        $fieldname = $result -> fetch_assoc();

        $fullname = $fieldname['full_name'];
        $usertype = $fieldname['role'];
        $id = $fieldname['user_id'];

        //session variable
        $_SESSION['user_type'] = $usertype;
        $_SESSION['fullname'] = $fullname;
        $_SESSION['id'] = $id;

        //to check the usertype
        echo $usertype;

        if ($usertype == "Voter" || $usertype == "Voters") {
            ?>
            <script>
                window.location.href = "voter_dashboard.php";
            </script>
            <?php
        }
        else if($usertype == "Organizer"){
            ?>
            <script>
                window.location.href = "organizer_dashboard.php";
            </script>
            <?php
        }
        else if ($usertype == "Admin") {
            ?>
            <script>
                window.location.href = "admin_dashboard.php";
            </script>
            <?php
        };
    } else {
    ?> 
        <script>
            Swal.fire({
            position: "center",
            icon: "error",
            title: "Invalid Account",
            showConfirmButton: false,
            timer: 1500
            });
        </script>
    <?php
    };
    

}


?>