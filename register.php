<?php
require 'inc/header.inc.php';

$errors = [];
$username = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf_token('register', $_POST['csrf_token'] ?? null);

    $username = sanitize_text($_POST['username'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_phone($_POST['phone'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '') {
        $errors[] = 'Name is required.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    if ($phone === '') {
        $errors[] = 'A valid phone number is required.';
    }

    if ($password === '' || strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if (!$errors) {
        $query = $con->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $query->bind_param('s', $email);
        $query->execute();
        $query->store_result();

        if ($query->num_rows > 0) {
            $errors[] = 'An account already exists for this email address.';
        }
        $query->close();
    }

    if (!$errors) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $statement = $con->prepare('INSERT INTO users (username, email, phone, password) VALUES (?, ?, ?, ?)');
        $statement->bind_param('ssss', $username, $email, $phone, $hashedPassword);
        $statement->execute();
        $userId = $statement->insert_id ?: $con->insert_id;
        $statement->close();

        session_regenerate_id(true);
        $_SESSION['username'] = $email;
        $_SESSION['user_id'] = $userId;
        $_SESSION['display_name'] = $username;

        header('Location: my_account.php');
        exit;
    }
}
?>

<section class="register">
    <div class="container">
        <h2>Create account</h2>
        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form id="form" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token('register')); ?>">
            <div class="form-group col-lg-6">
                <label for="username">Name</label>
                <input type="text" class="form-control" name="username" id="username" value="<?php echo e($username); ?>" placeholder="Enter your name" required>
            </div>

            <div class="form-group col-lg-6">
                <label for="email">Email address</label>
                <input type="email" class="form-control" name="email" id="email" value="<?php echo e($email); ?>" placeholder="Enter your email" required>
            </div>

            <div class="form-group col-lg-6">
                <label for="phone">Phone</label>
                <input type="text" class="form-control" name="phone" id="phone" value="<?php echo e($phone); ?>" placeholder="Enter your phone number" required>
            </div>

            <div class="form-group col-lg-6">
                <label for="password">Password</label>
                <input type="password" class="form-control" name="password" id="password" placeholder="Enter your password" minlength="8" required>
            </div>

            <div class="form-group col-lg-6">
                <button type="submit" name="submit" class="btn btn-success">Register</button>
            </div>

            <div class="container-fluid">
                <p class="text-secondary">Already Registered <a href="login.php">Login</a></p>
            </div>
        </form>
    </div>
</section>

<?php
require 'inc/footer.inc.php';
