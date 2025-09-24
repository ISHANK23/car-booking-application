<?php
require 'includes/connection.inc.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf_token('admin_login', $_POST['csrf_token'] ?? null);

    $username = sanitize_text($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please provide both username and password.';
    } else {
        $statement = $con->prepare('SELECT id, UserName, Password FROM admin WHERE UserName = ? LIMIT 1');
        $statement->bind_param('s', $username);
        $statement->execute();
        $result = $statement->get_result();
        $admin = $result->fetch_assoc();
        $statement->close();

        if ($admin && password_verify($password, $admin['Password'])) {
            session_regenerate_id(true);
            $_SESSION['id'] = (int)$admin['id'];
            $_SESSION['admin'] = $admin['UserName'];
            header('Location: dashboard.php');
            exit;
        }

        $error = 'Invalid username or password.';
    }
}
?>
<!doctype html>
<html lang="en" class="no-js">

<head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
        <meta name="description" content="">
        <meta name="author" content="">

        <title>Car Rental Portal | Admin Login</title>
        <link rel="stylesheet" href="css/font-awesome.min.css">
        <link rel="stylesheet" href="css/bootstrap.min.css">
        <link rel="stylesheet" href="css/dataTables.bootstrap.min.css">
        <link rel="stylesheet" href="css/bootstrap-social.css">
        <link rel="stylesheet" href="css/bootstrap-select.css">
        <link rel="stylesheet" href="css/fileinput.min.css">
        <link rel="stylesheet" href="css/awesome-bootstrap-checkbox.css">
        <link rel="stylesheet" href="css/style.css">
</head>

<body>

        <div class="login-page bk-img" style="background-image: url(img/vehicleimages/bg.png);">
                <div class="form-content">
                        <div class="container">
                                <div class="row">
                                        <div class="col-md-6 col-md-offset-3">
                                                <h1 class="text-center text-bold text-light mt-4x">Sign in</h1>
                                                <div class="well row pt-2x pb-3x bk-light">
                                                        <div class="col-md-8 col-md-offset-2">
                                                                <?php if ($error): ?>
                                                                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                                                                <?php endif; ?>
                                                                <form method="post">
                                                                        <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token('admin_login')); ?>">
                                                                        <label for="username" class="text-uppercase text-sm">Your Username </label>
                                                                        <input type="text" placeholder="Username" name="username" id="username" class="form-control mb" required>

                                                                        <label for="password" class="text-uppercase text-sm">Password</label>
                                                                        <input type="password" placeholder="Password" name="password" id="password" class="form-control mb" required>

                                                                        <button class="btn btn-primary btn-block" name="login" type="submit">LOGIN</button>

                                                                </form>
                                                        </div>
                                                </div>
                                        </div>
                                </div>
                        </div>
                </div>
        </div>

        <!-- Loading Scripts -->
        <script src="js/jquery.min.js"></script>
        <script src="js/bootstrap-select.min.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/jquery.dataTables.min.js"></script>
        <script src="js/dataTables.bootstrap.min.js"></script>
        <script src="js/Chart.min.js"></script>
        <script src="js/fileinput.js"></script>
        <script src="js/chartData.js"></script>
        <script src="js/main.js"></script>

</body>

</html>
