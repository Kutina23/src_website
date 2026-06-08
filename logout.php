<?php
session_start();
require_once 'config/database.php';
session_destroy();
header('Location: portal/login.php');
exit;
