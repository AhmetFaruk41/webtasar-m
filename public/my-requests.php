<?php
require_once '../config/app.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('index.php');
}

$user = getCurrentUser();
$requestModel = new Request();

// Filtreler
$filters = [
    'status' => sanitize($_GET['status'] ?? ''),
    'request_type_id' => sanitize($_GET['request_type_id'] ?? ''),
    'date_from' => sanitize($_GET['date_from'] ?? ''),
    'date_to' => sanitize($_GET['date_to'] ?? '')
];

// Talepleri al
$requests = $requestModel->getByUserId($user['id'], $filters);

// Talep türlerini al
$requestTypes = $requestModel->getRequestTypes();

// Flash mesajları
$flashMessages = getFlashMessages();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Taleplerim</title>
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
                    <a href="request-form.php" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-plus mr-1"></i>
                        Yeni Talep
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
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-list mr-2 text-blue-600"></i>
                        Taleplerim
                    </h1>
                    <p class="text-gray-600">Oluşturduğunuz talepleri görüntüleyebilir ve yönetebilirsiniz.</p>
                </div>
                <a href="request-form.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>
                    Yeni Talep
                </a>
            </div>
        </div>

        <!-- Filtreler -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Filtreler</h2>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            Durum
                        </label>
                        <select 
                            id="status" 
                            name="status" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Tüm Durumlar</option>
                            <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Beklemede</option>
                            <option value="approved" <?php echo $filters['status'] === 'approved' ? 'selected' : ''; ?>>Onaylandı</option>
                            <option value="rejected" <?php echo $filters['status'] === 'rejected' ? 'selected' : ''; ?>>Reddedildi</option>
                            <option value="cancelled" <?php echo $filters['status'] === 'cancelled' ? 'selected' : ''; ?>>İptal Edildi</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="request_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Talep Türü
                        </label>
                        <select 
                            id="request_type_id" 
                            name="request_type_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Tüm Türler</option>
                            <?php foreach ($requestTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>" <?php echo $filters['request_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                    <?php echo $type['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">
                            Başlangıç Tarihi
                        </label>
                        <input 
                            type="date" 
                            id="date_from" 
                            name="date_from" 
                            value="<?php echo $filters['date_from']; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                    
                    <div class="flex items-end">
                        <button 
                            type="submit" 
                            class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
                        >
                            <i class="fas fa-search mr-2"></i>
                            Filtrele
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Talepler -->
        <div class="bg-white shadow rounded-lg">
            <div class="p-6">
                <?php if (empty($requests)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-file-alt text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Henüz talep oluşturmadınız</h3>
                        <p class="text-gray-600 mb-4">İlk talebinizi oluşturmak için aşağıdaki butona tıklayın.</p>
                        <a href="request-form.php" class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i>
                            İlk Talebinizi Oluşturun
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($requests as $request): ?>
                            <div class="border rounded-lg p-6 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-4">
                                            <div class="flex-1">
                                                <h3 class="text-lg font-medium text-gray-900"><?php echo $request['title']; ?></h3>
                                                <p class="text-sm text-gray-500">
                                                    <?php echo $request['request_type_name']; ?>
                                                    <?php if ($request['amount']): ?>
                                                        - <?php echo formatCurrency($request['amount']); ?>
                                                    <?php endif; ?>
                                                </p>
                                                <?php if ($request['description']): ?>
                                                    <p class="text-sm text-gray-600 mt-1"><?php echo mb_substr($request['description'], 0, 100); ?>...</p>
                                                <?php endif; ?>
                                                <div class="flex items-center space-x-4 mt-2">
                                                    <span class="text-xs text-gray-400">
                                                        <i class="fas fa-calendar mr-1"></i>
                                                        <?php echo formatDateTime($request['created_at']); ?>
                                                    </span>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo getUrgencyColor($request['urgency']); ?>">
                                                        <?php echo getUrgencyText($request['urgency']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ml-6">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo getStatusColor($request['status']); ?>">
                                            <?php echo getStatusText($request['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Tarih aralığı -->
                                <?php if ($request['start_date'] && $request['end_date']): ?>
                                    <div class="mt-3 text-sm text-gray-600">
                                        <i class="fas fa-calendar-alt mr-1"></i>
                                        <?php echo formatDate($request['start_date']); ?> - <?php echo formatDate($request['end_date']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
    </script>
</body>
</html>