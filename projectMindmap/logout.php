<?php
// logout.php
require_once 'config.php';

// Destroy session and redirect
session_destroy();
redirect('index.php');
?>