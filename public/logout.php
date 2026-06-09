<?php
/**
 * LOGOUT — Hancurkan session admin dan redirect ke login.
 */
session_start();
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
