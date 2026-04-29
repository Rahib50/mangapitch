<?php
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
session_destroy();
header('Location: ' . BASE . '/auth/login.php');
exit;