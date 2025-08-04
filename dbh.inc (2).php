<?php
// Create connection
$conn = mysqli_connect("", "", "");

// Check connection
if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
};

if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error(). "<br>";
  echo "Contact admin on email vithrbacek (at) email.cz";
  exit();
}
                    
?>