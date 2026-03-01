    
<?php
require_once __DIR__ . '/../../app/config.php';
include __DIR__ . '/../../app/dal/db.php'   ;

if (!isset($_POST['password'], $_POST['email'])) {
    header('Location: ' . url('public/login/login.php?error=missing_fields'));
    exit();
}

$password = trim($_POST['password']);
$email = trim($_POST['email']);

$sql = "SELECT id,nome,email,password,ruolo 
        FROM utenti
        WHERE email = ?
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);
$row_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row_user) {
    header('Location: ' . url('public/login/login.php?error=invalid_credentials'));
    exit();
}


if (!password_verify($password, $row_user['password'])) {

    header('Location: ' . url('public/login/login.php?error=invalid_credentials'));
    exit();
}

// Login effettuato
$_SESSION['id'] = $row_user['id'];
$_SESSION['nome'] = $row_user['nome'];
$_SESSION['email'] = $row_user['email'];
$_SESSION['ruolo'] = $row_user['ruolo'];

//Redirect 
if ($row_user['ruolo'] === 'admin') {
    header('Location: ' . url('public/admin/index.php'));
    exit();
} elseif ($row_user['ruolo'] === 'user') {
    header('Location: ' . url('public/customers/index.php'));
    exit();
} else {
    session_destroy();
    header('Location: ' . url('public/login/login.php?error=invalid_role'));
    exit();
}
