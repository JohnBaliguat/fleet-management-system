<?php
session_start();

session_destroy();


header("Location: Pantrucks/index.php");
exit;
?>
