<?php
require_once '../config/app.php';

// Kullanıcı oturumunu sonlandır
if (isLoggedIn()) {
    $user = new User();
    $user->logout();
}

// Giriş sayfasına yönlendir
redirect('index.php');
?>