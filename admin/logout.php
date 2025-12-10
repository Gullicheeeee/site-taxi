<?php
require_once 'config.php';

// Détruire la session
session_destroy();

// Rediriger vers la page de login
header('Location: login.php');
exit;
