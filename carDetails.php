<?php
require 'inc/header.inc.php';

$vehicleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$vehicleId) {
    http_response_code(404);
    echo 'Vehicle not found.';
    require 'inc/footer.inc.php';
    exit;
}

$vehicleStatement = $con->prepare('SELECT * FROM cars WHERE id = ?');
$vehicleStatement->bind_param('i', $vehicleId);
$vehicleStatement->execute();
$vehicleResult = $vehicleStatement->get_result();
$vehicle = $vehicleResult->fetch_assoc();
$vehicleStatement->close();

if (!$vehicle) {
    http_response_code(404);
    echo 'Vehicle not found.';
    require 'inc/footer.inc.php';
    exit;
}

$bookingErrors = [];
$bookingSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    if (empty($_SESSION['username'])) {
        header('Location: login.php');
        exit;
    }

    require_valid_csrf_token('book_vehicle', $_POST['csrf_token'] ?? null);

    $fromDate = sanitize_text($_POST['fromDate'] ?? '');
    $toDate = sanitize_text($_POST['toDate'] ?? '');
    $message = sanitize_multiline($_POST['message'] ?? '');
    $message = strip_tags($message);

    if ($fromDate === '' || $toDate === '') {
        $bookingErrors[] = 'Both start and end dates are required.';
    }

    if (strlen($message) > 1000) {
        $bookingErrors[] = 'Message is too long.';
    }

    if (!$bookingErrors) {
        $insert = $con->prepare('INSERT INTO carbooking (userEmail, VehicleId, FromDate, ToDate, message, Status) VALUES (?, ?, ?, ?, ?, 0)');
        $userEmail = $_SESSION['username'];
        $insert->bind_param('sisss', $userEmail, $vehicleId, $fromDate, $toDate, $message);
        $insert->execute();
        $insert->close();
        $bookingSuccess = true;
    }
}
?>
<section class="car-details">
   <div class="container">
      <div class="row">
         <div class="col-md-12">
            <div id="custCarousel" class="carousel slide" data-ride="carousel" align="center">
               <div class="carousel-inner">
                  <div class="carousel-item active"> <img src="<?php echo 'admin/img/vehicleimages/' . e($vehicle['Vimage1']); ?>" alt="Vehicle image"> </div>
                  <?php for ($i = 2; $i <= 4; $i++): $key = 'Vimage' . $i; if (!empty($vehicle[$key])): ?>
                      <div class="carousel-item"> <img src="<?php echo 'admin/img/vehicleimages/' . e($vehicle[$key]); ?>" alt="Vehicle image"> </div>
                  <?php endif; endfor; ?>
               </div>
               <a class="carousel-control-prev" href="#custCarousel" data-slide="prev"> <span class="carousel-control-prev-icon"></span> </a>
               <a class="carousel-control-next" href="#custCarousel" data-slide="next"> <span class="carousel-control-next-icon"></span> </a>
               <ol class="carousel-indicators list-inline">
                   <?php for ($i = 1; $i <= 4; $i++): $key = 'Vimage' . $i; if (!empty($vehicle[$key])): ?>
                       <li class="list-inline-item<?php echo $i === 1 ? ' active' : ''; ?>">
                           <a id="carousel-selector-<?php echo $i - 1; ?>" data-slide-to="<?php echo $i - 1; ?>" data-target="#custCarousel">
                               <img src="<?php echo 'admin/img/vehicleimages/' . e($vehicle[$key]); ?>" class="img-fluid" alt="Vehicle thumbnail">
                           </a>
                       </li>
                   <?php endif; endfor; ?>
               </ol>
            </div>
         </div>
      </div>
   </div>
</section>
<section class="details">
   <div class="container">
   <h2 class="text-center"><?php echo e($vehicle['VehiclesTitle']); ?></h2>
   <div class="row">
      <div class="col-3">
         <h5>Registered Year</h5>
         <i class="fas fa-calendar-alt fa-3x"></i>
         <?php echo e($vehicle['ModelYear']); ?>
      </div>
      <div class="col-3">
         <h5>Fuel Type</h5>
         <i class="fas fa-gas-pump fa-3x"></i>
         <?php echo e($vehicle['FuelType']); ?>
      </div>
      <div class="col-3">
         <h5>No of Seats</h5>
         <i class="fas fa-user-plus fa-3x"></i>
         <?php echo e($vehicle['SeatingCapacity']); ?>
      </div>
      <div class="col-3">
         <h5>Price Per Day</h5>
         <i class="fas fa-dollar-sign fa-3x"></i>
         <?php echo 'Rs ' . e($vehicle['PricePerDay']); ?>
      </div>
   </div>
</section>
<section class="book-now">
   <div class="col-md-10 text-right">
      <button type="button" class="btn btn-success" data-toggle="modal"
         data-target="<?php echo isset($_SESSION['username']) ? '#exampleModalScrollable' : '#warning'; ?>">
      Book Now
      </button>
   </div>
   <div class="modal fade" id="exampleModalScrollable" tabindex="-1" role="dialog" aria-labelledby="exampleModalScrollableTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-scrollable" role="document">
         <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title" id="exampleModalScrollableTitle">Booking Information</h5>
               <button type="button" class="close" data-dismiss="modal" aria-label="Close">
               <span aria-hidden="true">&times;</span>
               </button>
            </div>
            <div class="modal-body">
                <?php if ($bookingSuccess): ?>
                    <div class="alert alert-success">Booking request submitted successfully.</div>
                <?php elseif ($bookingErrors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($bookingErrors as $error): ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
               <form method="POST">
                  <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token('book_vehicle')); ?>">
                  <div class="form-group">
                     <label for="fromDate">From Date</label>
                     <input data-date-format="dd/mm/yyyy" name="fromDate" id="datepicker" class="form-control" required>
                  </div>
                  <div class="form-group">
                     <label for="toDate">To Date</label>
                     <input data-date-format="dd/mm/yyyy" name="toDate" id="datepicker2" class="form-control" required>
                  </div>
                  <div class="form-group">
                     <label for="exampleFormControlTextarea1">Message</label>
                     <textarea class="form-control" name="message" id="exampleFormControlTextarea1" rows="3" maxlength="1000"></textarea>
                  </div>
                  <div class="form-group">
                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                      <button type="submit" name="book" class="btn btn-primary">Submit</button>
                  </div>
               </form>
            </div>
         </div>
      </div>
   </div>
   <div class="modal fade" id="warning" tabindex="-1" role="dialog" aria-labelledby="exampleModalScrollableTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-scrollable" role="document">
         <div class="modal-content">
            <div class="modal-header">
               <button type="button" class="close" data-dismiss="modal" aria-label="Close">
               <span aria-hidden="true">&times;</span>
               </button>
            </div>
            <div class="modal-body">
               <h2 class="text-danger">Please login to the Account</h2>
            </div>
         </div>
      </div>
   </div>
</section>
<section class="features">
   <div class="container">
      <h2>Feature of car</h2>
      <div class="card">
         <div class="card-header">Options of <?php echo e($vehicle['VehiclesTitle']); ?> </div>
         <div class="card-body">
            <table class="table table-bordered">
               <thead>
                  <tr>
                     <th scope="col">Features</th>
                     <th scope="col">Available</th>
                  </tr>
               </thead>
               <tbody>
                  <?php
                  $features = [
                      'AirConditioner' => 'AC',
                      'PowerDoorLocks' => 'Power Door Locks',
                      'AntiLockBrakingSystem' => 'Anti Lock BrakingSystem',
                      'BrakeAssist' => 'Brake Assist',
                      'PowerSteering' => 'Power Steering',
                      'DriverAirbag' => 'Driver Air Bag',
                      'PassengerAirbag' => 'Passenger Air Bag',
                      'PowerWindows' => 'Power Windows',
                      'CDPlayer' => 'CD Player',
                      'CentralLocking' => 'Central Locking',
                      'CrashSensor' => 'Crash Sensor',
                      'LeatherSeats' => 'Leather Seats',
                  ];
                  foreach ($features as $column => $label): ?>
                      <tr>
                          <td><?php echo e($label); ?></td>
                          <td><?php echo ((int)$vehicle[$column] === 1) ? '<i class="fas fa-check"></i>' : '<i class="fas fa-times"></i>'; ?></td>
                      </tr>
                  <?php endforeach; ?>
               </tbody>
            </table>
         </div>
      </div>
   </div>
</section>
<?php
require 'inc/footer.inc.php';
?>
<script type="text/javascript">
   $('#datepicker').datepicker({
       weekStart: 1,
       daysOfWeekHighlighted: "6,0",
       autoclose: true,
       todayHighlight: true,
   });

   $('#datepicker2').datepicker({
       weekStart: 1,
       daysOfWeekHighlighted: "6,0",
       autoclose: true,
       todayHighlight: true,
   });
</script>
