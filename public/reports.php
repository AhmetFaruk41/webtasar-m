<?php
require_once '../config/app.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('index.php');
}

$user = getCurrentUser();
$requestModel = new Request();
$userModel = new User();

// Tarih filtresi
$dateFrom = sanitize($_GET['date_from'] ?? date('Y-m-01')); // Bu ayın başı
$dateTo = sanitize($_GET['date_to'] ?? date('Y-m-t')); // Bu ayın sonu
$department = sanitize($_GET['department'] ?? '');

// Filtreleri hazırla
$filters = [
    'date_from' => $dateFrom,
    'date_to' => $dateTo
];

if (!empty($department)) {
    $filters['department'] = $department;
}

// İstatistikleri al
$totalStats = $requestModel->getStats();
$departmentStats = [];
$monthlyStats = $requestModel->getMonthlyStats();

// Departman bazlı istatistikler
$departments = $userModel->getDepartments();
foreach ($departments as $dept) {
    $deptFilters = ['department' => $dept['department']];
    $departmentStats[$dept['department']] = $requestModel->getStats(null, null, $deptFilters);
}

// Talep türü bazlı istatistikler
$requestTypes = $requestModel->getRequestTypes();
$typeStats = [];
foreach ($requestTypes as $type) {
    $typeFilters = ['request_type_id' => $type['id']];
    $typeStats[$type['name']] = count($requestModel->getAll($typeFilters));
}

// Son talepler
$recentRequests = array_slice($requestModel->getAll($filters), 0, 10);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Raporlar</title>
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
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="admin.php" class="text-gray-700 hover:text-gray-900">
                            <i class="fas fa-cogs mr-1"></i>
                            Admin
                        </a>
                    <?php endif; ?>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt mr-1"></i>
                        Çıkış
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-chart-bar mr-2 text-green-600"></i>
                Raporlar ve İstatistikler
            </h1>
            <p class="text-gray-600">Talep yönetimi ve sistem performans raporları</p>
        </div>

        <!-- Filtreler -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Rapor Filtreleri</h2>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">
                            Başlangıç Tarihi
                        </label>
                        <input 
                            type="date" 
                            id="date_from" 
                            name="date_from" 
                            value="<?php echo $dateFrom; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                    
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">
                            Bitiş Tarihi
                        </label>
                        <input 
                            type="date" 
                            id="date_to" 
                            name="date_to" 
                            value="<?php echo $dateTo; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                    
                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700 mb-2">
                            Departman
                        </label>
                        <select 
                            id="department" 
                            name="department" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Tüm Departmanlar</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['department']; ?>" <?php echo $department === $dept['department'] ? 'selected' : ''; ?>>
                                    <?php echo $dept['department']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button 
                            type="submit" 
                            class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
                        >
                            <i class="fas fa-filter mr-2"></i>
                            Filtrele
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Genel İstatistikler -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-alt text-2xl text-blue-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Toplam Talep</dt>
                                <dd class="text-3xl font-semibold text-gray-900"><?php echo $totalStats['total']; ?></dd>
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
                                <dd class="text-3xl font-semibold text-gray-900"><?php echo $totalStats['pending']; ?></dd>
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
                                <dd class="text-3xl font-semibold text-gray-900"><?php echo $totalStats['approved']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-percent text-2xl text-purple-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Onay Oranı</dt>
                                <dd class="text-3xl font-semibold text-gray-900">
                                    <?php 
                                    $approvalRate = $totalStats['total'] > 0 ? round(($totalStats['approved'] / $totalStats['total']) * 100, 1) : 0;
                                    echo $approvalRate . '%';
                                    ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafikler -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Talep Durumu Grafiği -->
            <div class="bg-white shadow rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Talep Durumları</h3>
                    <div class="relative h-64">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Talep Türü Grafiği -->
            <div class="bg-white shadow rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Talep Türleri</h3>
                    <div class="relative h-64">
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Departman İstatistikleri -->
        <?php if (!empty($departmentStats)): ?>
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Departman Bazlı İstatistikler</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Departman
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Toplam
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Beklemede
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Onaylandı
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Reddedildi
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Onay Oranı
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($departmentStats as $deptName => $stats): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo $deptName; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $stats['total']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600">
                                            <?php echo $stats['pending']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                            <?php echo $stats['approved']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                            <?php echo $stats['rejected']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php 
                                            $rate = $stats['total'] > 0 ? round(($stats['approved'] / $stats['total']) * 100, 1) : 0;
                                            echo $rate . '%';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Son Talepler -->
        <div class="bg-white shadow rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Son Talepler</h3>
                <?php if (empty($recentRequests)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-file-alt text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Seçilen kriterlere uygun talep bulunamadı.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recentRequests as $request): ?>
                            <div class="border rounded-lg p-4 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900"><?php echo $request['title']; ?></h4>
                                        <p class="text-sm text-gray-500">
                                            <?php echo $request['first_name'] . ' ' . $request['last_name']; ?> - 
                                            <?php echo $request['department']; ?> - 
                                            <?php echo $request['request_type_name']; ?>
                                        </p>
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
    </div>

    <script>
        // Talep Durumu Grafiği
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Beklemede', 'Onaylandı', 'Reddedildi', 'İptal Edildi'],
                datasets: [{
                    data: [
                        <?php echo $totalStats['pending']; ?>,
                        <?php echo $totalStats['approved']; ?>,
                        <?php echo $totalStats['rejected']; ?>,
                        <?php echo $totalStats['cancelled']; ?>
                    ],
                    backgroundColor: [
                        '#FCD34D',
                        '#10B981',
                        '#EF4444',
                        '#6B7280'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Talep Türü Grafiği
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        new Chart(typeCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo '"' . implode('", "', array_keys($typeStats)) . '"'; ?>],
                datasets: [{
                    label: 'Talep Sayısı',
                    data: [<?php echo implode(', ', array_values($typeStats)); ?>],
                    backgroundColor: [
                        '#3B82F6',
                        '#10B981',
                        '#F59E0B',
                        '#EF4444',
                        '#8B5CF6'
                    ],
                    borderColor: [
                        '#2563EB',
                        '#059669',
                        '#D97706',
                        '#DC2626',
                        '#7C3AED'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Tarih validasyonu
        document.getElementById('date_from').addEventListener('change', function() {
            const fromDate = this.value;
            const toDateField = document.getElementById('date_to');
            
            if (fromDate) {
                toDateField.min = fromDate;
                if (toDateField.value && toDateField.value < fromDate) {
                    toDateField.value = fromDate;
                }
            }
        });
    </script>
</body>
</html>