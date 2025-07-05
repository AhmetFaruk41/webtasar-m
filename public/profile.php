<?php
require_once '../config/app.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('index.php');
}

$user = getCurrentUser();
$userModel = new User();
$error = '';
$success = '';

// Profil güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Güvenlik hatası. Lütfen sayfayı yenileyin.';
    } else {
        $action = sanitize($_POST['action'] ?? '');
        
        if ($action === 'update_profile') {
            $profileData = [
                'first_name' => sanitize($_POST['first_name'] ?? ''),
                'last_name' => sanitize($_POST['last_name'] ?? ''),
                'phone' => sanitize($_POST['phone'] ?? '')
            ];
            
            // Validasyon
            $errors = [];
            
            if (empty($profileData['first_name'])) {
                $errors[] = 'Ad alanı zorunludur.';
            }
            
            if (empty($profileData['last_name'])) {
                $errors[] = 'Soyad alanı zorunludur.';
            }
            
            if (!empty($profileData['phone']) && !preg_match('/^[0-9+\-\s\(\)]+$/', $profileData['phone'])) {
                $errors[] = 'Geçerli bir telefon numarası girin.';
            }
            
            if (empty($errors)) {
                try {
                    $userModel->updateProfile($user['id'], $profileData);
                    $success = 'Profil bilgileriniz başarıyla güncellendi.';
                    
                    // Session'daki kullanıcı bilgilerini güncelle
                    $user = getCurrentUser();
                } catch (Exception $e) {
                    $error = 'Profil güncellenirken hata: ' . $e->getMessage();
                }
            } else {
                $error = implode('<br>', $errors);
            }
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Şifre validasyonu
            $errors = [];
            
            if (empty($currentPassword)) {
                $errors[] = 'Mevcut şifre zorunludur.';
            }
            
            if (empty($newPassword)) {
                $errors[] = 'Yeni şifre zorunludur.';
            } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                $errors[] = 'Yeni şifre en az ' . PASSWORD_MIN_LENGTH . ' karakter olmalıdır.';
            }
            
            if ($newPassword !== $confirmPassword) {
                $errors[] = 'Yeni şifre ve onay şifresi eşleşmiyor.';
            }
            
            if (empty($errors)) {
                try {
                    $userModel->changePassword($user['id'], $currentPassword, $newPassword);
                    $success = 'Şifreniz başarıyla değiştirildi.';
                } catch (Exception $e) {
                    $error = 'Şifre değiştirilirken hata: ' . $e->getMessage();
                }
            } else {
                $error = implode('<br>', $errors);
            }
        }
    }
}

// Kullanıcı profilini al
$userProfile = $userModel->getProfile($user['id']);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Profil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="dashboard.php" class="text-xl font-bold text-gray-900">
                            <i class="fas fa-building mr-2 text-indigo-600"></i>
                            <?php echo APP_NAME; ?>
                        </a>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-gray-900">
                        <i class="fas fa-home mr-1"></i>
                        Dashboard
                    </a>
                    <a href="my-requests.php" class="text-gray-700 hover:text-gray-900">
                        <i class="fas fa-list mr-1"></i>
                        Taleplerim
                    </a>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt mr-1"></i>
                        Çıkış
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-user mr-2 text-blue-600"></i>
                Profil Yönetimi
            </h1>
            <p class="text-gray-600">Profil bilgilerinizi görüntüleyin ve güncelleyin.</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Profil Bilgileri -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Genel Bilgiler</h2>
            </div>
            
            <div class="p-6">
                <div class="flex items-center space-x-6 mb-6">
                    <div class="flex-shrink-0">
                        <i class="fas fa-user-circle text-6xl text-gray-400"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900">
                            <?php echo $userProfile['first_name'] . ' ' . $userProfile['last_name']; ?>
                        </h3>
                        <p class="text-gray-600"><?php echo $userProfile['position']; ?></p>
                        <p class="text-gray-500"><?php echo $userProfile['department']; ?></p>
                        <p class="text-sm text-gray-400">
                            Çalışan ID: <?php echo $userProfile['employee_id']; ?>
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">E-posta</label>
                        <div class="mt-1 p-3 bg-gray-50 border border-gray-300 rounded-md">
                            <?php echo $userProfile['email']; ?>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">E-posta adresi değiştirilemez.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Rol</label>
                        <div class="mt-1 p-3 bg-gray-50 border border-gray-300 rounded-md">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <?php echo ucfirst($userProfile['role']); ?>
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">İşe Başlama Tarihi</label>
                        <div class="mt-1 p-3 bg-gray-50 border border-gray-300 rounded-md">
                            <?php echo $userProfile['hire_date'] ? formatDate($userProfile['hire_date']) : 'Belirtilmemiş'; ?>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Yönetici</label>
                        <div class="mt-1 p-3 bg-gray-50 border border-gray-300 rounded-md">
                            <?php if ($userProfile['manager_first_name']): ?>
                                <?php echo $userProfile['manager_first_name'] . ' ' . $userProfile['manager_last_name']; ?>
                            <?php else: ?>
                                Belirtilmemiş
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Düzenlenebilir Bilgiler -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Düzenlenebilir Bilgiler</h2>
            </div>
            
            <form method="POST" class="p-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Ad <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="first_name" 
                            name="first_name" 
                            required 
                            value="<?php echo htmlspecialchars($userProfile['first_name']); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Soyad <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="last_name" 
                            name="last_name" 
                            required 
                            value="<?php echo htmlspecialchars($userProfile['last_name']); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Telefon
                        </label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            value="<?php echo htmlspecialchars($userProfile['phone'] ?? ''); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="+90 5XX XXX XX XX"
                        >
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button 
                        type="submit" 
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <i class="fas fa-save mr-2"></i>
                        Profili Güncelle
                    </button>
                </div>
            </form>
        </div>

        <!-- Şifre Değiştirme -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Şifre Değiştir</h2>
                <p class="text-sm text-gray-600">Güvenliğiniz için düzenli olarak şifrenizi değiştirin.</p>
            </div>
            
            <form method="POST" class="p-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="change_password">
                
                <div class="space-y-6">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Mevcut Şifre <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="current_password" 
                            name="current_password" 
                            required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>

                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Yeni Şifre <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            required 
                            minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                        <p class="mt-1 text-sm text-gray-500">En az <?php echo PASSWORD_MIN_LENGTH; ?> karakter olmalıdır.</p>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Yeni Şifre (Tekrar) <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button 
                        type="submit" 
                        class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                    >
                        <i class="fas fa-key mr-2"></i>
                        Şifreyi Değiştir
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Şifre onay kontrolü
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Şifreler eşleşmiyor');
            } else {
                this.setCustomValidity('');
            }
        });

        // Form gönderim doğrulaması
        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (form.querySelector('[name="action"]').value === 'change_password') {
                    const currentPassword = document.getElementById('current_password').value;
                    const newPassword = document.getElementById('new_password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    
                    if (!currentPassword || !newPassword || !confirmPassword) {
                        e.preventDefault();
                        alert('Lütfen tüm şifre alanlarını doldurun.');
                        return;
                    }
                    
                    if (newPassword !== confirmPassword) {
                        e.preventDefault();
                        alert('Yeni şifre ve onay şifresi eşleşmiyor.');
                        return;
                    }
                    
                    if (newPassword.length < <?php echo PASSWORD_MIN_LENGTH; ?>) {
                        e.preventDefault();
                        alert('Yeni şifre en az <?php echo PASSWORD_MIN_LENGTH; ?> karakter olmalıdır.');
                        return;
                    }
                }
            });
        });

        // Auto-hide flash messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.mb-6.p-4.rounded');
            messages.forEach(function(message) {
                if (message.classList.contains('bg-green-100') || message.classList.contains('bg-red-100')) {
                    message.style.transition = 'opacity 0.5s ease-in-out';
                    message.style.opacity = '0';
                    setTimeout(function() {
                        message.remove();
                    }, 500);
                }
            });
        }, 5000);
    </script>
</body>
</html>