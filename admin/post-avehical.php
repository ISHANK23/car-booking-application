<?php
require 'includes/connection.inc.php';
require 'includes/session.php';

$errors = [];
$success = '';

function normalize_checkbox(string $key): int
{
    return isset($_POST[$key]) ? 1 : 0;
}

function upload_image(string $fieldName, bool $required, array $allowedMimeTypes, string $destinationDirectory): ?string
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        if ($required) {
            throw new RuntimeException('Missing required image upload: ' . $fieldName);
        }
        return null;
    }

    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Failed to upload file: ' . $fieldName);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!isset($allowedMimeTypes[$mimeType])) {
        throw new RuntimeException('Unsupported image format for ' . $fieldName);
    }

    $extension = $allowedMimeTypes[$mimeType];
    $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
    $targetPath = rtrim($destinationDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Unable to store uploaded file.');
    }

    return $fileName;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $images = [
        'img1' => null,
        'img2' => null,
        'img3' => null,
        'img4' => null,
        'img5' => null,
    ];

    try {
        require_valid_csrf_token('post_vehicle', $_POST['csrf_token'] ?? null);

        $vehicletitle = sanitize_text($_POST['vehicletitle'] ?? '');
        $brand = filter_var($_POST['brandname'] ?? '', FILTER_VALIDATE_INT);
        $vehicleoverview = sanitize_multiline($_POST['vehicalorcview'] ?? '');
        $priceperday = filter_var($_POST['priceperday'] ?? '', FILTER_VALIDATE_FLOAT);
        $fueltype = sanitize_text($_POST['fueltype'] ?? '');
        $modelyear = filter_var($_POST['modelyear'] ?? '', FILTER_VALIDATE_INT);
        $seatingcapacity = filter_var($_POST['seatingcapacity'] ?? '', FILTER_VALIDATE_INT);

        if ($vehicletitle === '') {
            $errors[] = 'Vehicle title is required.';
        }
        if ($brand === false) {
            $errors[] = 'A valid brand is required.';
        }
        if ($vehicleoverview === '') {
            $errors[] = 'Vehicle overview is required.';
        }
        if ($priceperday === false || $priceperday <= 0) {
            $errors[] = 'Price per day must be a positive value.';
        }
        if ($fueltype === '') {
            $errors[] = 'Fuel type is required.';
        }
        if ($modelyear === false || $modelyear < 1900) {
            $errors[] = 'Model year must be a valid year.';
        }
        if ($seatingcapacity === false || $seatingcapacity <= 0) {
            $errors[] = 'Seating capacity must be a positive integer.';
        }

        $uploadDir = __DIR__ . '/img/vehicleimages';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Unable to create upload directory.');
        }

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        foreach (['img1' => true, 'img2' => true, 'img3' => true, 'img4' => false, 'img5' => false] as $field => $required) {
            try {
                $images[$field] = upload_image($field, $required, $allowedTypes, $uploadDir);
            } catch (RuntimeException $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        if ($errors) {
            foreach ($images as $uploaded) {
                if ($uploaded) {
                    @unlink($uploadDir . DIRECTORY_SEPARATOR . $uploaded);
                }
            }
        } else {
            $brand = (int) $brand;
            $priceperday = (float) $priceperday;
            $modelyear = (int) $modelyear;
            $seatingcapacity = (int) $seatingcapacity;
            $statement = $con->prepare('INSERT INTO cars (VehiclesTitle, VehiclesBrand, VehiclesOverview, PricePerDay, FuelType, ModelYear, SeatingCapacity, Vimage1, Vimage2, Vimage3, Vimage4, Vimage5, AirConditioner, PowerDoorLocks, AntiLockBrakingSystem, BrakeAssist, PowerSteering, DriverAirbag, PassengerAirbag, PowerWindows, CDPlayer, CentralLocking, CrashSensor, LeatherSeats, RegDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');

            $airconditioner = normalize_checkbox('airconditioner');
            $powerdoorlocks = normalize_checkbox('powerdoorlocks');
            $antilockbrakingsys = normalize_checkbox('antilockbrakingsys');
            $brakeassist = normalize_checkbox('brakeassist');
            $powersteering = normalize_checkbox('powersteering');
            $driverairbag = normalize_checkbox('driverairbag');
            $passengerairbag = normalize_checkbox('passengerairbag');
            $powerwindow = normalize_checkbox('powerwindow');
            $cdplayer = normalize_checkbox('cdplayer');
            $centrallocking = normalize_checkbox('centrallocking');
            $crashcensor = normalize_checkbox('crashcensor');
            $leatherseats = normalize_checkbox('leatherseats');

            $image1 = $images['img1'];
            $image2 = $images['img2'];
            $image3 = $images['img3'];
            $image4 = $images['img4'] ?? '';
            $image5 = $images['img5'] ?? '';

            $statement->bind_param(
                'sisdsiissssssiiiiiiiiiiii',
                $vehicletitle,
                $brand,
                $vehicleoverview,
                $priceperday,
                $fueltype,
                $modelyear,
                $seatingcapacity,
                $image1,
                $image2,
                $image3,
                $image4,
                $image5,
                $airconditioner,
                $powerdoorlocks,
                $antilockbrakingsys,
                $brakeassist,
                $powersteering,
                $driverairbag,
                $passengerairbag,
                $powerwindow,
                $cdplayer,
                $centrallocking,
                $crashcensor,
                $leatherseats
            );
            $statement->execute();
            $statement->close();

            $success = 'Vehicle added successfully.';
        }
    } catch (RuntimeException $exception) {
        $errors[] = $exception->getMessage();
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
      <meta name="theme-color" content="#3e454c">
      <title>Car Rental Portal | Admin Post Vehicle</title>
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
                     <h2 class="page-title">Post A Vehicle</h2>
                     <?php if ($errors): ?>
                         <div class="errorWrap">
                             <strong>ERROR</strong>:
                             <ul class="mb-0">
                                 <?php foreach ($errors as $error): ?>
                                     <li><?php echo e($error); ?></li>
                                 <?php endforeach; ?>
                             </ul>
                         </div>
                     <?php elseif ($success): ?>
                         <div class="succWrap"><strong>SUCCESS</strong>:<?php echo e($success); ?></div>
                     <?php endif; ?>
                     <div class="row">
                        <div class="col-md-12">
                           <div class="panel panel-default">
                              <div class="panel-heading">Basic Info</div>
                              <div class="panel-body">
                                 <form method="post" class="form-horizontal" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token('post_vehicle')); ?>">
                                    <div class="form-group">
                                       <label class="col-sm-2 control-label">Vehicle Title<span style="color:red">*</span></label>
                                       <div class="col-sm-4">
                                          <input type="text" name="vehicletitle" class="form-control" required>
                                       </div>
                                       <label class="col-sm-2 control-label">Select Brand<span style="color:red">*</span></label>
                                       <div class="col-sm-4">
                                          <select class="selectpicker" name="brandname" required>
                                             <option value=""> Select </option>
                                             <?php
                                    $res = mysqli_query($con, 'SELECT id,BrandName FROM brands ORDER BY BrandName ASC');
                                    while ($row = mysqli_fetch_assoc($res)) {
                                          echo "<option value=".$row['id'].">".e($row['BrandName'])."</option>";
                                    }
                                    ?>
                                          </select>
                                       </div>
                                    </div>
                                    <div class="hr-dashed"></div>
                                    <div class="form-group">
                                       <label class="col-sm-2 control-label">Vehile Details<span style="color:red">*</span></label>
                                       <div class="col-sm-10">
                                          <textarea class="form-control" name="vehicalorcview" rows="3" required></textarea>
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <label class="col-sm-2 control-label">Price Per Day(in Rs)<span style="color:red">*</span></label>
                                       <div class="col-sm-4">
                                          <input type="number" step="0.01" name="priceperday" class="form-control" required>
                                       </div>
                                       <label class="col-sm-2 control-label">Select Fuel Type<span style="color:red">*</span></label>
                                       <div class="col-sm-4">
                                          <select class="selectpicker" name="fueltype" required>
                                             <option value=""> Select </option>
                                             <option value="Petrol">Petrol</option>
                                             <option value="Diesel">Diesel</option>
                                             <option value="CNG">CNG</option>
                                          </select>
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <label class="col-sm-2 control-label">Model Year<span style="color:red">*</span></label>
                                       <div class="col-sm-4">
                                          <input type="number" name="modelyear" class="form-control" min="1900" required>
                                       </div>
                                       <label class="col-sm-2 control-label">Seating Capacity<span style="color:red">*</span></label>
                                       <div class="col-sm-4">
                                          <input type="number" name="seatingcapacity" class="form-control" min="1" required>
                                       </div>
                                    </div>
                                    <div class="hr-dashed"></div>
                                    <div class="form-group">
                                       <div class="col-sm-12">
                                          <h4><b>Upload Images</b></h4>
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <div class="col-sm-4">
                                          Image 1 <span style="color:red">*</span><input type="file" name="img1" accept="image/*" required>
                                       </div>
                                       <div class="col-sm-4">
                                          Image 2<span style="color:red">*</span><input type="file" name="img2" accept="image/*" required>
                                       </div>
                                       <div class="col-sm-4">
                                          Image 3<span style="color:red">*</span><input type="file" name="img3" accept="image/*" required>
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <div class="col-sm-4">
                                          Image 4<input type="file" name="img4" accept="image/*">
                                       </div>
                                       <div class="col-sm-4">
                                          Image 5<input type="file" name="img5" accept="image/*">
                                       </div>
                                    </div>
                                    <div class="hr-dashed"></div>
                              </div>
                           </div>
                        </div>
                     </div>
                     <div class="row">
                     <div class="col-md-12">
                     <div class="panel panel-default">
                     <div class="panel-heading">Accessories</div>
                     <div class="panel-body">
                     <div class="form-group">
                     <div class="col-sm-3">
                     <div class="checkbox checkbox-inline">
                     <input type="checkbox" id="airconditioner" name="airconditioner" value="1">
                     <label for="airconditioner"> Air Conditioner </label>
                     </div>
                     </div>
                     <div class="col-sm-3">
                     <div class="checkbox checkbox-inline">
                     <input type="checkbox" id="powerdoorlocks" name="powerdoorlocks" value="1">
                     <label for="powerdoorlocks"> Power Door Locks </label>
                     </div></div>
                     <div class="col-sm-3">
                     <div class="checkbox checkbox-inline">
                     <input type="checkbox" id="antilockbrakingsys" name="antilockbrakingsys" value="1">
                     <label for="antilockbrakingsys"> AntiLock Braking System </label>
                     </div></div>
                     <div class="checkbox checkbox-inline">
                     <input type="checkbox" id="brakeassist" name="brakeassist" value="1">
                     <label for="brakeassist"> Brake Assist </label>
                     </div>
                     </div>
                     <div class="form-group">
                     <div class="col-sm-3">
                     <div class="checkbox checkbox-inline">
                     <input type="checkbox" id="powersteering" name="powersteering" value="1">
                     <label for="inlineCheckbox5"> Power Steering </label>
                     </div>
                     </div>
                     <div class="col-sm-3">
                     <div class="checkbox checkbox-inline">
                     <input type="checkbox" id="driverairbag" name="driverairbag" value="1">
                     <label for="driverairbag">Driver Airbag</label>
                     </div>
                     </div>
                     <div class="col-sm-3">
                     <div class="checkbox checkbox-inline">
                     <input type="checkbox" id="passengerairbag" name="passengerairbag" value="1">
                     <label for="passengerairbag"> Passenger Airbag </label>
                     </div></div>
                     <div class="checkbox checkbox-inline">
                     <input type="checkbox" id="powerwindow" name="powerwindow" value="1">
                     <label for="powerwindow"> Power Windows </label>
                     </div>
                     </div>
                     <div class="form-group">
                     <div class="col-sm-3">
                     <div class="checkbox checkbox-inline">
                     <input type="checkbox" id="cdplayer" name="cdplayer" value="1">
                     <label for="cdplayer"> CD Player </label>
                     </div>
                     </div>
                     <div class="col-sm-3">
                     <div class="checkbox h checkbox-inline">
                     <input type="checkbox" id="centrallocking" name="centrallocking" value="1">
                     <label for="centrallocking">Central Locking</label>
                     </div></div>
                     <div class="col-sm-3">
                     <div class="checkbox checkbox-inline">
                     <input type="checkbox" id="crashcensor" name="crashcensor" value="1">
                     <label for="crashcensor"> Crash Sensor </label>
                     </div></div>
                     <div class="col-sm-3">
                     <div class="checkbox checkbox-inline">
                     <input type="checkbox" id="leatherseats" name="leatherseats" value="1">
                     <label for="leatherseats"> Leather Seats </label>
                     </div>
                     </div>
                     </div>
                     <div class="form-group">
                     <div class="col-sm-8 col-sm-offset-2">
                     <button class="btn btn-default" type="reset">Cancel</button>
                     <button class="btn btn-primary" name="submit" type="submit">Save changes</button>
                     </div>
                     </div>
                     </form>
                     </div>
                     </div>
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
