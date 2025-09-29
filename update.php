
<?php
// Database connection setup
$servername = "localhost";
$username = "mdrrjvhm_xanxus47";
$password = "oneLASTsong32";
$dbname = "mdrrjvhm_test";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function sanitize_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
  return $data;
}

// Retrieve form data
 if (isset($_POST['DateAndTime']) || isset($_POST['ForecastDaily']) || isset($_POST['RatePerHour']) || 
        isset($_POST['Dailyrate']) || isset($_POST['WeeklyRate']) || isset($_POST['Speed']) ||
        isset($_POST['Gusetiness']) || isset($_POST['Direction']) || isset($_POST['Outdoor']) || 
        isset($_POST['Indoor']) || isset($_POST['Current']) || isset($_POST['TwelveHour']) ||
        isset($_POST['TwentyFourHour']) || isset($_POST['CagurayBridge']) || isset($_POST['NicolasBridge']) ||
        isset($_POST['InanggihanBridge']) || isset($_POST['StatusOfCoastalWater']) || isset($_POST['VehicularIncident']) ||
        isset($_POST['MedicalEmergency']) || isset($_POST['DrowningIncident']) || isset($_POST['CapsizedIncident']) ||
        isset($_POST['Fire']) || isset($_POST['InsurgencyIncident']) || isset($_POST['OtherIncident']) ||
        isset($_POST['Casualties']) || isset($_POST['TropicalCyclone']) || isset($_POST['Flood']) ||
        isset($_POST['Landslide']) || isset($_POST['Earthquake']) || isset($_POST['Drought']) ||
        isset($_POST['StormSurge']) || isset($_POST['Tsunami']) || isset($_POST['OtherHazard1']) || 
        isset($_POST['OtherHazard2']) || isset($_POST['OtherHazard3']) || isset($_POST['Blank1']) || isset($_POST['Blank2']) || isset($_POST['Blank3']) ||
        isset($_POST['Evacuation']) || isset($_POST['AffectedPopulation']) || isset($_POST['ClassSuspension']) || isset($_POST['WorkSuspension']) && isset($_POST['RoadAndBridges']) ||
        isset($_POST['POwer']) || isset($_POST['Water']) || isset($_POST['Communication']) || isset($_POST['Houses']) || isset($_POST['Agriculture']) ||
        isset($_POST['Infrastructures']) || isset($_POST['Death']) || isset($_POST['Injured']) || isset($_POST['Missing']) || isset($_POST['StateOfCalamity']) ||
        isset($_POST['MonitoringAlerts']) || isset($_POST['TeamLeader']) || isset($_POST['ReportGeneration'])  || isset($_POST['id1']) ){ 

    $id= sanitize_input($_POST['id1']);
    $DateAndTime = sanitize_input($_POST['DateAndTime']);
    $ForecastDaily = sanitize_input($_POST['ForecastDaily']);
    $RatePerHour= sanitize_input($_POST['RatePerHour']);
    $Dailyrate= sanitize_input($_POST['Dailyrate']);
    $WeeklyRate = sanitize_input($_POST['WeeklyRate']);
    $Speed = sanitize_input($_POST['Speed']);
    $Gusetiness = sanitize_input($_POST['Gusetiness']);
    $Direction = sanitize_input($_POST['Direction']);
    $Outdoor = sanitize_input($_POST['Outdoor']);
    $Indoor = sanitize_input($_POST['Indoor']);
    $Current = sanitize_input($_POST['Current']);
    $TwelveHour = sanitize_input($_POST['TwelveHour']);
    $TwentyFourHour = sanitize_input($_POST['TwentyFourHour']);
    $CagurayBridge = sanitize_input($_POST['CagurayBridge']);
    $NicolasBridge = sanitize_input($_POST['NicolasBridge']);
    $InanggihanBridge = sanitize_input($_POST['InanggihanBridge']);
    $StatusOfCoastalWater = sanitize_input($_POST['StatusOfCoastalWater']);
    $VehicularIncident = sanitize_input($_POST['VehicularIncident']);
    $MedicalEmergency = sanitize_input($_POST['MedicalEmergency']);
    $DrowningIncident = sanitize_input($_POST['DrowningIncident']);
    $CapsizedIncident = sanitize_input($_POST['CapsizedIncident']);
    $Fire = sanitize_input($_POST['Fire']);
    $InsurgencyIncident = sanitize_input($_POST['InsurgencyIncident']);
    $OtherIncident = sanitize_input($_POST['OtherIncident']);
    $Casualties = sanitize_input($_POST['Casualties']);
    $TropicalCyclone = sanitize_input($_POST['TropicalCyclone']);
    $FLood = sanitize_input($_POST['Flood']);
    $Landslide = sanitize_input($_POST['Landslide']);
    $Earthquake = sanitize_input($_POST['Earthquake']);
    $Drought = sanitize_input($_POST['Drought']);
    $StormSurge = sanitize_input($_POST['StormSurge']);
    $Tsunami = sanitize_input($_POST['Tsunami']);
    $OtherHazard1 = sanitize_input($_POST['OtherHazard1']);
    $OtherHazard2 = sanitize_input($_POST['OtherHazard2']);
    $OtherHazard3 = sanitize_input($_POST['OtherHazard3']);
    $Blank1 = sanitize_input($_POST['Blank1']);
    $Blank2 = sanitize_input($_POST['Blank2']);
    $Blank3 = sanitize_input($_POST['Blank3']);
    $Evacuation = sanitize_input($_POST['Evacuation']);
    $AffectedPopulation = sanitize_input($_POST['AffectedPopulation']);
    $ClassSuspension = sanitize_input($_POST['ClassSuspension']);
    $WorkSuspension =sanitize_input($_POST['WorkSuspension']);
    $RoadAndBridges = sanitize_input($_POST['RoadAndBridges']);
    $POwer = sanitize_input($_POST['POwer']);
    $Water = sanitize_input($_POST['Water']);
    $Communication = sanitize_input($_POST['Communication']);
    $Houses = sanitize_input($_POST['Houses']);
    $Agriculture = sanitize_input($_POST['Agriculture']);
    $Infrastructures = sanitize_input($_POST['Infrastructures']);
    $Death = sanitize_input($_POST['Death']);
    $Injured = sanitize_input($_POST['Injured']);
    $Missing = sanitize_input($_POST['Missing']);
    $StateOfCalamity = sanitize_input($_POST['StateOfCalamity']);
    $MonitoringAlerts = sanitize_input($_POST['MonitoringAlerts']);
    $TeamLeader = sanitize_input($_POST['TeamLeader']);
    $ReportGeneration= sanitize_input($_POST['ReportGeneration']);

        }

// Upload directory
$targetDir = "uploads/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

// Function to handle image upload and return file path
function uploadImage($fileInputName, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    $fileType = strtolower(pathinfo($_FILES[$fileInputName]["name"], PATHINFO_EXTENSION));
    if (!in_array($fileType, $allowedTypes)) {
        die("Error: Only JPG, JPEG, PNG, & GIF files allowed.");
    }
    $newFileName = uniqid() . "." . $fileType;
    $targetFile = $targetDir . $newFileName;

    if (move_uploaded_file($_FILES[$fileInputName]["tmp_name"], $targetFile)) {
        return $targetFile;
    } else {
        die("Error uploading " . $fileInputName);
    }
}


// Upload both images
/*if(isset($_FILES['CurrentWeather1']) ||isset($_FILES['CurrentWeather2']) ||isset($_FILES['CurrentWeather3']) ||isset($_FILES['CurrentWeather4']) ||
isset($_FILES['IncidentPhoto1']) ||isset($_FILES['IncidentPhoto2']) ||isset($_FILES['IncidentPhoto3']) ||isset($_FILES['IncidentPhoto4'])){


    /*
    $CurrentWeather1 = uploadImage('CurrentWeather1', $targetDir);
    $CurrentWeather2 = uploadImage('CurrentWeather2', $targetDir);
    $CurrentWeather3 = uploadImage('CurrentWeather3', $targetDir);
    $CurrentWeather4 = uploadImage('CurrentWeather4', $targetDir);
    $IncidentPhoto1 = uploadImage('IncidentPhoto1', $targetDir);
    $IncidentPhoto2 = uploadImage('IncidentPhoto2', $targetDir);
    $IncidentPhoto3 = uploadImage('IncidentPhoto3', $targetDir);
    $IncidentPhoto4 = uploadImage('IncidentPhoto4', $targetDir);


    */
   
//$dir = 'uploads/';


    $CurrentWeather1 = basename($_FILES['CurrentWeather1']['name']);
    $CurrentWeather2 = basename($_FILES['CurrentWeather2']['name']);
    $CurrentWeather3 = basename($_FILES['CurrentWeather3']['name']);
    $CurrentWeather4 = basename($_FILES['CurrentWeather4']['name']);
    $IncidentPhoto1 = basename($_FILES['IncidentPhoto1']['name']);
    $IncidentPhoto2 = basename($_FILES['IncidentPhoto2']['name']);
    $IncidentPhoto3 = basename($_FILES['IncidentPhoto3']['name']);
    $IncidentPhoto4 = basename($_FILES['IncidentPhoto4']['name']);


    $targetFile1 = $targetDir . $CurrentWeather1;
    $targetFile2 = $targetDir . $CurrentWeather2;
    $targetFile3 = $targetDir . $CurrentWeather3;
    $targetFile4 = $targetDir . $CurrentWeather4;
    $targetFile5 = $targetDir . $IncidentPhoto1;
    $targetFile6 = $targetDir . $IncidentPhoto2;
    $targetFile7 = $targetDir . $IncidentPhoto3;
    $targetFile8 = $targetDir . $IncidentPhoto4;

    // Move uploaded files to the target directory
    move_uploaded_file($_FILES['CurrentWeather1']['tmp_name'], $targetFile1);
    move_uploaded_file($_FILES['CurrentWeather2']['tmp_name'], $targetFile2);
    move_uploaded_file($_FILES['CurrentWeather3']['tmp_name'], $targetFile3);
    move_uploaded_file($_FILES['CurrentWeather4']['tmp_name'], $targetFile4);
    move_uploaded_file($_FILES['IncidentPhoto1']['tmp_name'], $targetFile5);
    move_uploaded_file($_FILES['IncidentPhoto2']['tmp_name'], $targetFile6);
    move_uploaded_file($_FILES['IncidentPhoto3']['tmp_name'], $targetFile7);
    move_uploaded_file($_FILES['IncidentPhoto4']['tmp_name'], $targetFile8);


// update into database
$sql = " UPDATE 
      incidentdb1 
    SET 
      ForecastDaily = ?,
      RatePerHour = ?,
      Dailyrate = ?,
      WeeklyRate = ?,
      Speed = ?,
      Gusetiness = ?,
      Direction = ?,
      Outdoor = ?,
      Indoor = ?,
      Current = ?,
      TwelveHour = ?,
      TwentyFourHour = ?,
      CagurayBridge = ?,
      NicolasBridge = ?,
      InanggihanBridge = ?,
      StatusOfCoastalWater = ?,
      VehicularIncident = ?,
      MedicalEmergency = ?,
      DrowningIncident = ?,
      CapsizedIncident = ?,
      Fire = ?,
      InsurgencyIncident = ?,
      OtherIncident = ?,
      Casualties = ?,
      TropicalCyclone = ?,
      Flood = ?,
      Landslide = ?,
      Earthquake = ?,
      Drought = ?,
      StormSurge = ?,
      Tsunami = ?,
      OtherHazard1 = ?,
      OtherHazard2 = ?,
      OtherHazard3 = ?,
      Blank1 = ?,
      Blank2 = ?,
      Blank3 = ?,
      Evacuation = ?,
      AffectedPopulation = ?,
      ClassSuspension = ?,
      WorkSuspension = ?,
      RoadAndBridges = ?,
      POwer = ?,
      Water = ?,
      Communication = ?,
      Houses = ?,
      Agriculture = ?,
      Infrastructures = ?,
      Death = ?,
      Injured = ?,
      Missing = ?,
      StateOfCalamity = ?,
      MonitoringAlerts = ?,
      TeamLeader = ?
    WHERE 
      id = ?";

/*
if(($CurrentWeather1== null)||($CurrentWeather2== null)||($CurrentWeather3== null)||($CurrentWeather4== null)){

     header("Location:incidentreport1.php?msg=Please add Photos for Documentation");
die();
}else{

*/

$stmt = $conn->prepare($sql);


if (!$stmt) {
  die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("ssssssssssssssssssssssssssssssssssssssssssssssssssssssi",$ForecastDaily, $RatePerHour, $Dailyrate, $WeeklyRate, $Speed, $Gusetiness,$Direction,$Outdoor, $Indoor, $Current, $TwelveHour, $TwentyFourHour, $CagurayBridge, $NicolasBridge, $InanggihanBridge, $StatusOfCoastalWater, $VehicularIncident, $MedicalEmergency, $DrowningIncident, $CapsizedIncident, $Fire, $InsurgencyIncident, $OtherIncident, $Casualties, $TropicalCyclone, $FLood,$Landslide, $Earthquake, $Drought,$StormSurge, $Tsunami, $OtherHazard1, $OtherHazard2, $OtherHazard3, $Blank1, $Blank2, $Blank3, $Evacuation, $AffectedPopulation, $ClassSuspension, $WorkSuspension,$RoadAndBridges, $POwer, $Water,$Communication,$Houses,$Agriculture,$Infrastructures,$Death,$Injured,$Missing,$StateOfCalamity,$MonitoringAlerts,$TeamLeader,$id);


if ($stmt->execute()) {
 ?>
 <script>
 const orderButton = document.querySelector(".order");

const addRemoveClass = () => {
  if (!orderButton.classList.contains("animate")) {
    orderButton.classList.add("animate");
    setTimeout(() => {
      orderButton.classList.remove("animate");
    }, 10000);
  }
};

orderButton.addEventListener("click", addRemoveClass);
 
</script>


 <?php
    





 header("Location:WTDTTB.php?msg=You Successfully Update Report for Weather Monitoring");
 die();
} else {
    echo "Database Error: " . $stmt->error;
}


$stmt->close();
$conn->close();
//}
?>