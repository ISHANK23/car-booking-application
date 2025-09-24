<?php
require 'includes/connection.inc.php';
require 'includes/session.php';

$error = '';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf_token('manage_bookings', $_POST['csrf_token'] ?? null);
    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';

    if (!$bookingId) {
        $error = 'Invalid booking identifier provided.';
    } elseif (!in_array($action, ['confirm', 'cancel'], true)) {
        $error = 'Unsupported action.';
    } else {
        $status = $action === 'confirm' ? 1 : 2;
        $statement = $con->prepare('UPDATE carbooking SET Status = ? WHERE id = ?');
        $statement->bind_param('ii', $status, $bookingId);
        $statement->execute();
        $statement->close();

        $msg = $action === 'confirm' ? 'Booking confirmed' : 'Booking cancelled';
    }
}

$bookingsStatement = $con->prepare('SELECT carbooking.id, carbooking.userEmail, carbooking.FromDate, carbooking.ToDate, carbooking.message, carbooking.Status, cars.VehiclesTitle FROM carbooking JOIN cars ON cars.id = carbooking.VehicleId ORDER BY carbooking.FromDate DESC');
$bookingsStatement->execute();
$bookings = $bookingsStatement->get_result();
?>
<!doctype html>
<html lang="en" class="no-js">

<head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
        <meta name="description" content="">
        <meta name="author" content="">
        <meta name="theme-color" content="#3e454c">

        <title>Car Rental Portal | Admin Manage Bookings</title>

        <link rel="stylesheet" href="css/font-awesome.min.css">
        <link rel="stylesheet" href="css/bootstrap.min.css">
        <link rel="stylesheet" href="css/dataTables.bootstrap.min.css">
        <link rel="stylesheet" href="css/bootstrap-social.css">
        <link rel="stylesheet" href="css/bootstrap-select.css">
        <link rel="stylesheet" href="css/fileinput.min.css">
        <link rel="stylesheet" href="css/awesome-bootstrap-checkbox.css">
        <link rel="stylesheet" href="css/style.css">
  <style>
                .errorWrap {
    padding: 10px;
    margin: 0 0 20px 0;
    background: #fff;
    border-left: 4px solid #dd3d36;
    -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
}
.succWrap{
    padding: 10px;
    margin: 0 0 20px 0;
    background: #fff;
    border-left: 4px solid #5cb85c;
    -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
}
                </style>

</head>

<body>
        <?php include('includes/header.php');?>

        <div class="ts-main-content">
                <?php include('includes/leftbar.php');?>
                <div class="content-wrapper">
                        <div class="container-fluid">

                                <div class="row">
                                        <div class="col-md-12">

                                                <h2 class="page-title">Manage Bookings</h2>

                                                <div class="panel panel-default">
                                                        <div class="panel-heading">Bookings Info</div>
                                                        <div class="panel-body">
                                                        <?php if ($error): ?><div class="errorWrap"><strong>ERROR</strong>:<?php echo e($error); ?> </div><?php endif; ?>
                                <?php if ($msg && !$error): ?><div class="succWrap"><strong>SUCCESS</strong>:<?php echo e($msg); ?> </div><?php endif; ?>
                                                                <table id="zctb" class="display table table-striped table-bordered table-hover" cellspacing="0" width="100%">
                                                                        <thead>
                                                                                <tr>
                                                                                        <th>#</th>
                                                                                        <th>Name</th>
                                                                                        <th>Vehicle</th>
                                                                                        <th>From Date</th>
                                                                                        <th>To Date</th>
                                                                                        <th>Message</th>
                                                                                        <th>Status</th>
                                                                                        <th>Action</th>
                                                                                </tr>
                                                                        </thead>
                                                                        <tbody>
<?php $counter = 1; while ($row = $bookings->fetch_assoc()): ?>
                                                                                <tr>
                                                                                        <td><?php echo $counter++; ?></td>
                                                                                        <td><?php echo e($row['userEmail']); ?></td>
                                                                                        <td><?php echo e($row['VehiclesTitle']); ?></td>
                                                                                        <td><?php echo e($row['FromDate']); ?></td>
                                                                                        <td><?php echo e($row['ToDate']); ?></td>
                                                                                        <td><?php echo nl2br(e($row['message'])); ?></td>
                                                                                        <td><?php
if ((int)$row['Status'] === 0) {
    echo e('Not Confirmed yet');
} elseif ((int)$row['Status'] === 1) {
    echo e('Confirmed');
} else {
    echo e('Cancelled');
}
?></td>
                                                                                        <td>
                                                                                            <form method="post" class="d-inline">
                                                                                                <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token('manage_bookings')); ?>">
                                                                                                <input type="hidden" name="booking_id" value="<?php echo (int)$row['id']; ?>">
                                                                                                <input type="hidden" name="action" value="confirm">
                                                                                                <button type="submit" class="btn btn-link" onclick="return confirm('Do you really want to confirm this booking?');">Confirm</button>
                                                                                            </form>
                                                                                            <form method="post" class="d-inline">
                                                                                                <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token('manage_bookings')); ?>">
                                                                                                <input type="hidden" name="booking_id" value="<?php echo (int)$row['id']; ?>">
                                                                                                <input type="hidden" name="action" value="cancel">
                                                                                                <button type="submit" class="btn btn-link" onclick="return confirm('Do you really want to cancel this booking?');">Cancel</button>
                                                                                            </form>
                                                                                        </td>
                                                                                </tr>
                                                                        <?php endwhile; ?>

                                                                        </tbody>
                                                                </table>



                                                        </div>
                                                </div>



                                        </div>
                                </div>

                        </div>
                </div>
        </div>
        <?php $bookingsStatement->close(); ?>

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
