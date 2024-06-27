<?php
  require_once 'Admin.php'; // Adjust the path based on your project structure

  $admin = new Admin(/* pass dependencies here */);
  $response = [
      'progress' => $admin->getConversionProgress(),
      'finished' => $admin->isConversionFinished()
  ];

  echo json_encode($response);
?>