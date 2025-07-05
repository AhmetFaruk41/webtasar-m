<?php
require_once '../config/app.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('index.php');
}

$user = getCurrentUser();
$requestModel = new Request();
$notificationModel = new Notification();

// Kullanıcı istatistiklerini al
$stats = $requestModel->getStats($user['id'], $user['role']);
$monthlyStats = $requestModel->getMonthlyStats($user['id']);
$notifications = $notificationModel->getByUserId($user['id'], 5);
$unreadNotifications = $notificationModel->getUnreadCount($user['id']);

// Son talepler
$allUserRequests = $requestModel->getByUserId($user['id'], []);
$recentRequests = array_slice($allUserRequests, 0, 5);

// Onay bekleyen talepler (sadece manager ve admin için)
$pendingApprovals = [];
if ($user['role'] === 'manager' || $user['role'] === 'admin') {
    $pendingApprovals = $requestModel->getForApproval($user['id']);
}

// Flash mesajları
$flashMessages = getFlashMessages();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Dashboard</title>
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
                        <h1 class="text-xl font-bold text-gray-900">
                            <i class="fas fa-building mr-2 text-indigo-600"></i>
                            <?php echo APP_NAME; ?>
                        </h1>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Admin Panel Link (sadece admin için) -->
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="admin.php" class="text-gray-700 hover:text-gray-900">
                            <i class="fas fa-cogs mr-1"></i>
                            Admin
                        </a>
                    <?php endif; ?>
                    
                    <!-- Bildirimler -->
                    <div class="relative">
                        <button id="notifications-btn" class="relative p-2 text-gray-500 hover:text-gray-700 focus:outline-none">
                            <i class="fas fa-bell text-xl"></i>
                            <?php if ($unreadNotifications > 0): ?>
                                <span class="absolute -top-1 -right-1 h-5 w-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center">
                                    <?php echo $unreadNotifications; ?>
                                </span>
                            <?php endif; ?>
                        </button>
                        
                        <!-- Bildirim dropdown -->
                        <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg overflow-hidden z-50">
                            <div class="py-2">
                                <div class="px-4 py-2 text-sm text-gray-700 border-b">
                                    <strong>Bildirimler</strong>
                                </div>
                                <?php if (empty($notifications)): ?>
                                    <div class="px-4 py-3 text-sm text-gray-500">
                                        Henüz bildirim yok
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <div class="px-4 py-3 hover:bg-gray-50 border-b border-gray-100 <?php echo $notification['is_read'] ? 'opacity-60' : ''; ?>">
                                            <div class="flex items-start">
                                                <div class="<?php echo $notificationModel->getNotificationTypeColor($notification['type']); ?> rounded-full p-1 mr-3">
                                                    <span class="text-xs"><?php echo $notificationModel->getNotificationTypeIcon($notification['type']); ?></span>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="text-sm font-medium text-gray-900"><?php echo $notification['title']; ?></p>
                                                    <p class="text-xs text-gray-500"><?php echo $notification['message']; ?></p>
                                                    <p class="text-xs text-gray-400 mt-1"><?php echo formatDateTime($notification['created_at']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Kullanıcı menüsü -->
                    <div class="relative">
                        <button id="user-menu-btn" class="flex items-center text-sm text-gray-700 hover:text-gray-900 focus:outline-none">
                            <i class="fas fa-user-circle text-2xl mr-2"></i>
                            <span><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></span>
                            <i class="fas fa-chevron-down ml-1"></i>
                        </button>
                        
                        <!-- Kullanıcı dropdown -->
                        <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg overflow-hidden z-50">
                            <div class="py-1">
                                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i>Profil
                                </a>
                                <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-cog mr-2"></i>Ayarlar
                                </a>
                                <div class="border-t border-gray-100"></div>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Çıkış
                                </a>
                            </div>
                        </div>
                    </div>
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

        <!-- Hoşgeldin mesajı -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">
                Hoşgeldin, <?php echo $user['first_name']; ?>!
            </h1>
            <p class="text-gray-600">
                <?php echo $user['position']; ?> - <?php echo $user['department']; ?>
            </p>
        </div>

        <!-- İstatistik kartları -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-alt text-2xl text-blue-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Toplam Talep</dt>
                                <dd class="text-3xl font-semibold text-gray-900"><?php echo $stats['total']; ?></dd>
                            </dl>
                        </div>
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
                                <dt class="text-sm font-medium text-gray-500 truncate">Beklemede</dt>
                                <dd class="text-3xl font-semibold text-gray-900"><?php echo $stats['pending']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-2xl text-green-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Onaylandı</dt>
                                <dd class="text-3xl font-semibold text-gray-900"><?php echo $stats['approved']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-times-circle text-2xl text-red-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Reddedildi</dt>
                                <dd class="text-3xl font-semibold text-gray-900"><?php echo $stats['rejected']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hızlı erişim -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Hızlı Erişim</h2>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <a href="request-form.php" class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                        <i class="fas fa-plus text-2xl text-blue-600 mb-2"></i>
                        <span class="text-sm font-medium text-blue-900">Yeni Talep</span>
                    </a>
                    <a href="my-requests.php" class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                        <i class="fas fa-list text-2xl text-green-600 mb-2"></i>
                        <span class="text-sm font-medium text-green-900">Taleplerim</span>
                    </a>
                    <?php if ($user['role'] === 'manager' || $user['role'] === 'admin'): ?>
                        <a href="approvals.php" class="flex flex-col items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">
                            <i class="fas fa-check-double text-2xl text-orange-600 mb-2"></i>
                            <span class="text-sm font-medium text-orange-900">Onaylar</span>
                            <?php if (count($pendingApprovals) > 0): ?>
                                <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full mt-1">
                                    <?php echo count($pendingApprovals); ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    <a href="reports.php" class="flex flex-col items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                        <i class="fas fa-chart-bar text-2xl text-purple-600 mb-2"></i>
                        <span class="text-sm font-medium text-purple-900">Raporlar</span>
                    </a>
                    
                    <!-- Ek özellikler grid'e ekle -->
                    <a href="profile.php" class="flex flex-col items-center p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                        <i class="fas fa-user text-2xl text-indigo-600 mb-2"></i>
                        <span class="text-sm font-medium text-indigo-900">Profil</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Son talepler ve onay bekleyenler -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Son talepler -->
            <div class="bg-white shadow rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900">Son Taleplerim</h2>
                        <a href="my-requests.php" class="text-sm text-blue-600 hover:text-blue-500">
                            Tümünü Gör
                        </a>
                    </div>
                    
                    <?php if (empty($recentRequests)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-file-alt text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">Henüz talep oluşturmadınız</p>
                            <a href="request-form.php" class="mt-2 inline-block bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                İlk Talebinizi Oluşturun
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recentRequests as $request): ?>
                                <div class="border rounded-lg p-4 hover:bg-gray-50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <h3 class="font-medium text-gray-900"><?php echo $request['title']; ?></h3>
                                            <p class="text-sm text-gray-500"><?php echo $request['request_type_name']; ?></p>
                                            <p class="text-xs text-gray-400"><?php echo formatDateTime($request['created_at']); ?></p>
                                        </div>
                                        <div class="ml-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getStatusColor($request['status']); ?>">
                                                <?php echo getStatusText($request['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Onay bekleyen talepler (sadece manager ve admin) -->
            <?php if ($user['role'] === 'manager' || $user['role'] === 'admin'): ?>
                <div class="bg-white shadow rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-medium text-gray-900">Onay Bekleyen Talepler</h2>
                            <a href="approvals.php" class="text-sm text-blue-600 hover:text-blue-500">
                                Tümünü Gör
                            </a>
                        </div>
                        
                        <?php if (empty($pendingApprovals)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-check-circle text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500">Onay bekleyen talep yok</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach (array_slice($pendingApprovals, 0, 3) as $request): ?>
                                    <div class="border rounded-lg p-4 hover:bg-gray-50">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <h3 class="font-medium text-gray-900"><?php echo $request['title']; ?></h3>
                                                <p class="text-sm text-gray-500">
                                                    <?php echo $request['first_name'] . ' ' . $request['last_name']; ?> - 
                                                    <?php echo $request['request_type_name']; ?>
                                                </p>
                                                <p class="text-xs text-gray-400"><?php echo formatDateTime($request['created_at']); ?></p>
                                            </div>
                                            <div class="ml-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getUrgencyColor($request['urgency']); ?>">
                                                    <?php echo getUrgencyText($request['urgency']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Dropdown toggle functionality
        function toggleDropdown(buttonId, dropdownId) {
            const button = document.getElementById(buttonId);
            const dropdown = document.getElementById(dropdownId);
            
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        }
        
        // Initialize dropdowns
        toggleDropdown('notifications-btn', 'notifications-dropdown');
        toggleDropdown('user-menu-btn', 'user-dropdown');
        
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
    </script>
</body>
</html>