<?php
session_start();
session_destroy();
header("Location: login.php"); // or login page
exit;

