<?php
header('Content-Type: text/plain');
// Database connection setup
$servername = "localhost";
$username = "mdrrjvhm_xanxus47";
$password = "oneLASTsong32";
$dbname = "mdrrjvhm_test";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if( isset($_GET['id'])){
$id=$_GET['id'];
  $stmt = $conn->prepare("DELETE FROM incidentdb1 WHERE id = ?");
  $stmt->bind_param('i', $id);

}


  if ($stmt->execute()) {
 header("Location:WTDTTB.php?msg=Delete Successfully!!");
 die();
} else {
    echo "Database Error: " . $stmt->error;
}


$conn->close();
?>
