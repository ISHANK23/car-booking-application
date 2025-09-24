<?php
require 'inc/header.inc.php';

if (empty($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$userEmail = $_SESSION['username'];

$statement = $con->prepare('SELECT cars.VehiclesTitle, cars.Vimage1, carbooking.VehicleId, carbooking.FromDate, carbooking.ToDate, carbooking.Status FROM cars JOIN carbooking ON cars.id = carbooking.VehicleId WHERE carbooking.userEmail = ? ORDER BY carbooking.FromDate DESC');
$statement->bind_param('s', $userEmail);
$statement->execute();
$result = $statement->get_result();
?>

<section class="my-account">
    <div class="container">
        <h2>My Bookings</h2>
        <div class="row">
            <div class="col-2">
                <div class="list-group">
                    <a href="my_account.php" class="list-group-item list-group-item-action active">My Bookings</a>
                    <a href="logout.php" class="list-group-item list-group-item-action">Logout</a>
                </div>
            </div>
            <div class="col-10">
                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">Car</th>
                        <th scope="col">Image</th>
                        <th scope="col">From Date</th>
                        <th scope="col">To Date</th>
                        <th scope="col">Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo e($row['VehiclesTitle']); ?></td>
                            <td>
                                <?php if (!empty($row['Vimage1'])): ?>
                                    <img class="card-img-top" src="<?php echo 'admin/img/vehicleimages/' . e($row['Vimage1']); ?>" alt="Vehicle image">
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($row['FromDate']); ?></td>
                            <td><?php echo e($row['ToDate']); ?></td>
                            <td>
                                <?php if ((int)$row['Status'] === 0): ?>
                                    <span class="text-danger">Pending</span>
                                <?php elseif ((int)$row['Status'] === 1): ?>
                                    <span class="text-success">Confirmed</span>
                                <?php else: ?>
                                    <span class="text-secondary">Cancelled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php
$statement->close();
require 'inc/footer.inc.php';
