<?php
require 'inc/header.inc.php';

$error = null;
$email = '';
$flashError = $_SESSION['flash_error'] ?? null;
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

if ($flashError && !$error) {
    $error = $flashError;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf_token('login', $_POST['csrf_token'] ?? null);

    $email = sanitize_email($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password === '') {
        $error = 'Please enter your password.';
    } else {
        $statement = $con->prepare('SELECT id, email, password, username, oauth_provider FROM users WHERE email = ? LIMIT 1');
        $statement->bind_param('s', $email);
        $statement->execute();
        $result = $statement->get_result();
        $user = $result->fetch_assoc();
        $statement->close();

        if (!$user || empty($user['password'])) {
            $error = 'Invalid credentials. If you registered with an external provider please use that method to sign in.';
        } elseif (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['username'] = $user['email'];
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['display_name'] = $user['username'];

            header('Location: my_account.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>

<section class="login">
    <div class="container">
        <h2>Login</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>
        <?php if ($flashSuccess): ?>
            <div class="alert alert-success" role="alert">
                <?php echo e($flashSuccess); ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token('login')); ?>">
            <div class="form-group col-lg-6">
                <label for="login-email">Email address</label>
                <input type="email" class="form-control" name="username" id="login-email" value="<?php echo e($email); ?>" placeholder="Enter your email" required>
            </div>

            <div class="form-group col-lg-6">
                <label for="login-password">Password</label>
                <input type="password" class="form-control" name="password" id="login-password" placeholder="Enter your password" required>
            </div>

            <div class="form-group col-lg-6">
                <button type="submit" name="login" class="btn btn-primary">Login</button>
            </div>
        </form>
        <div class="form-group col-lg-6">
            <a class="btn btn-outline-danger" href="oauth/google_login.php">Sign in with Google</a>
        </div>
        <div class="container-fluid">
            <p class="text-secondary">Not registered? <a href="register.php">Create Account</a></p>
        </div>
    </div>
</section>

<?php
require 'inc/footer.inc.php';
