<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Vider et détruire la session
$_SESSION = [];
session_destroy();

header('Location: login.php');
exit;
