<?php
require_once '../config/app.php';

// Giriş kontrolü ve admin yetki kontrolü
if (!isLoggedIn()) {
    redirect('index.php');
}

$user = getCurrentUser();
if ($user['role'] !== 'admin') {
    addFlashMessage('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('dashboard.php');
}

$userModel = new User();
$requestModel = new Request();
$notificationModel = new Notification();

// İstatistikleri al
$totalUsers = count($userModel->getAll());
$totalRequests = $requestModel->getStats()['total'];
$pendingRequests = $requestModel->getStats()['pending'];
$departments = $userModel->getDepartments();

// Son kullanıcılar
$recentUsers = array_slice($userModel->getAll(), -5);

// Son talepler
$recentRequests = array_slice($requestModel->getAll(), -10);

// Flash mesajları
$flashMessages = getFlashMessages();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Admin Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a href="user-management.php" class="text-gray-700 hover:text-gray-900">
                        <i class="fas fa-users mr-1"></i>
                        Kullanıcılar
                    </a>
                    <a href="reports.php" class="text-gray-700 hover:text-gray-900">
                        <i class="fas fa-chart-bar mr-1"></i>
                        Raporlar
                    </a>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt mr-1"></i>
                        Çıkış
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Flash Messages -->
        <?php if (!empty($flashMessages)): ?>
            <div class="mb-6">
                <?php foreach ($flashMessages as $message): ?>
                    <div class="mb-4 p-4 rounded-md <?php echo $message['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                        <?php echo $message['message']; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-cogs mr-2 text-purple-600"></i>
                Admin Paneli
            </h1>
            <p class="text-gray-600">Sistem yönetimi ve genel bakış</p>
        </div>

        <!-- İstatistik Kartları -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-users text-2xl text-blue-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Toplam Kullanıcı</dt>
                                <dd class="text-3xl font-semibold text-gray-900"><?php echo $totalUsers; ?></dd>
                            </dl>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="user-management.php" class="text-sm text-blue-600 hover:text-blue-500">
                            Yönet →
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-alt text-2xl text-green-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Toplam Talep</dt>
                                <dd class="text-3xl font-semibold text-gray-900"><?php echo $totalRequests; ?></dd>
                            </dl>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="reports.php" class="text-sm text-green-600 hover:text-green-500">
                            Rapor →
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-2xl text-yellow-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Bekleyen Talepler</dt>
                                <dd class="text-3xl font-semibold text-gray-900"><?php echo $pendingRequests; ?></dd>
                            </dl>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-sm text-yellow-600">
                            Acil İnceleme
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-building text-2xl text-purple-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Departman</dt>
                                <dd class="text-3xl font-semibold text-gray-900"><?php echo count($departments); ?></dd>
                            </dl>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-sm text-purple-600">
                            Aktif Birim
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hızlı İşlemler -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Hızlı İşlemler</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="user-management.php?action=add" class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                        <i class="fas fa-user-plus text-2xl text-blue-600 mb-2"></i>
                        <span class="text-sm font-medium text-blue-900">Kullanıcı Ekle</span>
                    </a>
                    <a href="reports.php" class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                        <i class="fas fa-chart-line text-2xl text-green-600 mb-2"></i>
                        <span class="text-sm font-medium text-green-900">Rapor Oluştur</span>
                    </a>
                    <a href="system-settings.php" class="flex flex-col items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                        <i class="fas fa-cog text-2xl text-purple-600 mb-2"></i>
                        <span class="text-sm font-medium text-purple-900">Sistem Ayarları</span>
                    </a>
                    <a href="backup.php" class="flex flex-col items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">
                        <i class="fas fa-download text-2xl text-orange-600 mb-2"></i>
                        <span class="text-sm font-medium text-orange-900">Yedek Al</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Son Aktiviteler -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Son Kullanıcılar -->
            <div class="bg-white shadow rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900">Son Eklenen Kullanıcılar</h2>
                        <a href="user-management.php" class="text-sm text-blue-600 hover:text-blue-500">
                            Tümünü Gör
                        </a>
                    </div>
                    
                    <?php if (empty($recentUsers)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">Henüz kullanıcı eklenmemiş</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recentUsers as $recentUser): ?>
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-user-circle text-2xl text-gray-400"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo $recentUser['first_name'] . ' ' . $recentUser['last_name']; ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?php echo $recentUser['department']; ?> - <?php echo ucfirst($recentUser['role']); ?>
                                        </p>
                                    </div>
                                    <div class="text-sm text-gray-400">
                                        <?php echo formatDate($recentUser['created_at']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Son Talepler -->
            <div class="bg-white shadow rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900">Son Talepler</h2>
                        <a href="reports.php" class="text-sm text-blue-600 hover:text-blue-500">
                            Tümünü Gör
                        </a>
                    </div>
                    
                    <?php if (empty($recentRequests)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-file-alt text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">Henüz talep oluşturulmamış</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach (array_slice($recentRequests, 0, 5) as $request): ?>
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo getStatusColor($request['status']); ?>">
                                            <?php echo getStatusText($request['status']); ?>
                                        </span>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo $request['title']; ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?php echo $request['first_name'] . ' ' . $request['last_name']; ?> - 
                                            <?php echo $request['request_type_name']; ?>
                                        </p>
                                    </div>
                                    <div class="text-sm text-gray-400">
                                        <?php echo formatDate($request['created_at']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sistem Durumu -->
        <div class="mt-6 bg-white shadow rounded-lg">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Sistem Durumu</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">
                            <?php echo round(memory_get_usage(true) / 1024 / 1024, 2); ?> MB
                        </div>
                        <div class="text-sm text-gray-500">Bellek Kullanımı</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">
                            <?php echo phpversion(); ?>
                        </div>
                        <div class="text-sm text-gray-500">PHP Sürümü</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">
                            <?php echo APP_VERSION; ?>
                        </div>
                        <div class="text-sm text-gray-500">Uygulama Sürümü</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide flash messages after 5 seconds
        setTimeout(function() {
            const flashMessages = document.querySelectorAll('.mb-4.p-4.rounded-md');
            flashMessages.forEach(function(message) {
                message.style.transition = 'opacity 0.5s ease-in-out';
                message.style.opacity = '0';
                setTimeout(function() {
                    message.remove();
                }, 500);
            });
        }, 5000);

        // Real-time clock
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('tr-TR');
            document.getElementById('current-time').textContent = timeString;
        }
        
        // Update time every second
        setInterval(updateTime, 1000);
        updateTime();
    </script>
</body>
</html>