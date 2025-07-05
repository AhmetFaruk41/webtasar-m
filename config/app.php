<?php
/**
 * Uygulama Konfigürasyon Ayarları
 */

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Oturum başlat
session_start();

// Zaman dilimi ayarla
date_default_timezone_set('Europe/Istanbul');

// Sabitler
define('APP_NAME', 'Çalışan Talep Yönetim Sistemi');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

// Güvenlik ayarları
define('PASSWORD_MIN_LENGTH', 6);
define('SESSION_TIMEOUT', 3600); // 1 saat

// E-posta ayarları
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'noreply@company.com');
define('FROM_NAME', 'Talep Yönetim Sistemi');

// Dosya yüklemeleri için klasör oluştur
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Otomatik yükleme
spl_autoload_register(function($className) {
    $paths = [
        __DIR__ . '/../src/models/' . $className . '.php',
        __DIR__ . '/../src/controllers/' . $className . '.php',
        __DIR__ . '/../src/helpers/' . $className . '.php',
        __DIR__ . '/../config/' . $className . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
});

// Yardımcı fonksiyonlar
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d.m.Y H:i', strtotime($datetime));
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return $_SESSION['user_data'] ?? null;
    }
    return null;
}

function hasRole($role) {
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

function canApprove($userId) {
    $user = getCurrentUser();
    return $user && ($user['role'] === 'manager' || $user['role'] === 'admin');
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function addFlashMessage($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

function formatCurrency($amount) {
    return number_format($amount, 2, ',', '.') . ' ₺';
}

function getStatusText($status) {
    $statusTexts = [
        'pending' => 'Beklemede',
        'approved' => 'Onaylandı',
        'rejected' => 'Reddedildi',
        'cancelled' => 'İptal Edildi'
    ];
    return $statusTexts[$status] ?? $status;
}

function getUrgencyText($urgency) {
    $urgencyTexts = [
        'low' => 'Düşük',
        'medium' => 'Orta',
        'high' => 'Yüksek'
    ];
    return $urgencyTexts[$urgency] ?? $urgency;
}

function getUrgencyColor($urgency) {
    $colors = [
        'low' => 'text-green-600',
        'medium' => 'text-yellow-600',
        'high' => 'text-red-600'
    ];
    return $colors[$urgency] ?? 'text-gray-600';
}

function getStatusColor($status) {
    $colors = [
        'pending' => 'text-yellow-600 bg-yellow-100',
        'approved' => 'text-green-600 bg-green-100',
        'rejected' => 'text-red-600 bg-red-100',
        'cancelled' => 'text-gray-600 bg-gray-100'
    ];
    return $colors[$status] ?? 'text-gray-600 bg-gray-100';
}