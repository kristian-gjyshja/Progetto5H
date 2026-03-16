<?php

$host = "127.0.0.1";
$dbname = "abbonamenti_local";
$username = "abbonamenti_local";
$password = "UdFMFdogG3KkgnHG";

$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);