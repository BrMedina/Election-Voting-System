<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <link rel="stylesheet" href="register.css">
    <title>Register</title>
</head>
    <body>
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="card brand-card">
                        <div class="card-body p-4 p-md-5">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div>
                                    <h1 class="h3 mb-0">Create your account</h1>
                                </div>
                            </div>

                            <form action="register.php" method="post" enctype="multipart/form-data">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="first_name">First name</label>
                                        <input class="form-control" type="text" id="fname" name="fname" placeholder="Jane" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="last_name">Last name</label>
                                        <input class="form-control" type="text" id="lname" name="lname" placeholder="Doe" required>
                                    </div>
                                </div>
                                <div class="mb-3 mt-3">
                                    <label class="form-label" for="username">Username</label>
                                    <input class="form-control" type="text" id="username" name="username" placeholder="janedoe" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="email">Email address</label>
                                    <input class="form-control" type="email" id="email" name="email" placeholder="jane@example.com" required>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="date_of_birth">Date of birth</label>
                                        <input class="form-control" type="date" id="date_of_birth" name="date_of_birth" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="gender">Gender</label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="" selected disabled>Select</option>
                                            <option value="Female">Female</option>
                                            <option value="Male">Male</option>
                                            <option value="Other">Other</option>
                                            <option value="Prefer not to say">Prefer not to say</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3 mt-3">
                                    <label class="form-label" for="phone">Contact information (phone number)</label>
                                    <input class="form-control" type="tel" id="phone" name="phone" placeholder="0917 123 4567" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="password">Password</label>
                                        <input class="form-control" type="password" id="password" name="password" placeholder="Create a password" required>
                                </div>
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms">
                                        I confirm my information is accurate.
                                    </label>
                                </div>
                                <button type="submit" name="sub" class="btn brand-btn text-white w-100 mt-4">Register</button>
                            </form>

                            <div class="text-center mt-4">
                                <span class="text-muted">Already registered?</span>
                                <a class="text-decoration-none" href="login.php" style="color: #b52232">Sign in</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</html>




<?php
//for debugging purposes naka connect lang muna sa table remove require_once if done
require_once 'dbelection.php';
require_once 'VerifyOTPAddress.php';


if(isset($_POST['sub'])){

    $fname = ($_POST['fname']);
    $lname = ($_POST['lname']);
    $username = ($_POST['username']);
    $email = ($_POST['email']);
    $dateofbirth = ($_POST['date_of_birth']);
    $gender = ($_POST['gender']);
    $phone = ($_POST['phone']);
    $password = MD5($_POST['password']);

    $fullname = $fname ." ". $lname;

    $otp = rand(000000,999999);

    $insertSql = "INSERT INTO tbl_users (full_name, username, email, date_of_birth, role, phone, gender, password, otp, status)
    VALUES ('$fullname', '$username', '$email', '$dateofbirth', 'Voters', '$phone', '$gender', '$password', '$otp', 'Pending')";

    $res = $conn->query($insertSql);


    if ($res == true) {
        send_verification($fullname, $email, $otp);
        ?>
        <script>
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'Your work has been saved',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.href = "OTPVerification.php";
            });
        </script>
        <?php
    } else {
        echo $conn->error;
    }

};
?>