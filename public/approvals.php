<?php
require_once '../config/app.php';

// Giriş kontrolü ve yetki kontrolü
if (!isLoggedIn()) {
    redirect('index.php');
}

$user = getCurrentUser();
if ($user['role'] !== 'manager' && $user['role'] !== 'admin') {
    addFlashMessage('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('dashboard.php');
}

$requestModel = new Request();

// Onay işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        addFlashMessage('error', 'Güvenlik hatası. Lütfen sayfayı yenileyin.');
    } else {
        $action = sanitize($_POST['action'] ?? '');
        $requestId = sanitize($_POST['request_id'] ?? '');
        $comments = sanitize($_POST['comments'] ?? '');
        
        try {
            if ($action === 'approve') {
                $requestModel->approve($requestId, $user['id'], $comments);
                addFlashMessage('success', 'Talep başarıyla onaylandı.');
            } elseif ($action === 'reject') {
                $requestModel->reject($requestId, $user['id'], $comments);
                addFlashMessage('success', 'Talep reddedildi.');
            }
        } catch (Exception $e) {
            addFlashMessage('error', 'İşlem sırasında hata: ' . $e->getMessage());
        }
    }
    
    redirect('approvals.php');
}

// Onay bekleyen talepleri al
$pendingApprovals = $requestModel->getForApproval($user['id']);

// Flash mesajları
$flashMessages = getFlashMessages();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Onay Bekleyen Talepler</title>
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
                <i class="fas fa-check-double mr-2 text-orange-600"></i>
                Onay Bekleyen Talepler
            </h1>
            <p class="text-gray-600">Onayınızı bekleyen talepleri görüntüleyebilir ve işlem yapabilirsiniz.</p>
        </div>

        <!-- Talepler -->
        <div class="space-y-6">
            <?php if (empty($pendingApprovals)): ?>
                <div class="bg-white shadow rounded-lg">
                    <div class="text-center py-12">
                        <i class="fas fa-check-circle text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Onay bekleyen talep bulunmuyor</h3>
                        <p class="text-gray-600">Şu anda onayınızı bekleyen herhangi bir talep yok.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($pendingApprovals as $request): ?>
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-user-circle text-4xl text-gray-400"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-900"><?php echo $request['title']; ?></h3>
                                            <p class="text-sm text-gray-600">
                                                <strong><?php echo $request['first_name'] . ' ' . $request['last_name']; ?></strong>
                                                - <?php echo $request['department']; ?> - <?php echo $request['position']; ?>
                                            </p>
                                            <p class="text-sm text-blue-600"><?php echo $request['request_type_name']; ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <h4 class="text-sm font-medium text-gray-900 mb-2">Talep Açıklaması:</h4>
                                        <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-md"><?php echo nl2br($request['description']); ?></p>
                                    </div>
                                    
                                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <?php if ($request['start_date'] && $request['end_date']): ?>
                                            <div>
                                                <span class="text-xs font-medium text-gray-500">Tarih Aralığı</span>
                                                <p class="text-sm text-gray-900">
                                                    <i class="fas fa-calendar mr-1"></i>
                                                    <?php echo formatDate($request['start_date']); ?> - <?php echo formatDate($request['end_date']); ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($request['amount']): ?>
                                            <div>
                                                <span class="text-xs font-medium text-gray-500">Miktar</span>
                                                <p class="text-sm text-gray-900">
                                                    <i class="fas fa-lira-sign mr-1"></i>
                                                    <?php echo formatCurrency($request['amount']); ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">Aciliyet</span>
                                            <p class="text-sm">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo getUrgencyColor($request['urgency']); ?>">
                                                    <?php echo getUrgencyText($request['urgency']); ?>
                                                </span>
                                            </p>
                                        </div>
                                        
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">Talep Tarihi</span>
                                            <p class="text-sm text-gray-900">
                                                <i class="fas fa-clock mr-1"></i>
                                                <?php echo formatDateTime($request['created_at']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="ml-6">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-yellow-800 bg-yellow-100">
                                        <i class="fas fa-clock mr-1"></i>
                                        Onay Bekliyor
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Onay Butonları -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <div class="flex items-start space-x-4">
                                    <div class="flex-1">
                                        <label for="comments_<?php echo $request['id']; ?>" class="block text-sm font-medium text-gray-700 mb-2">
                                            Onay Yorumu (İsteğe bağlı)
                                        </label>
                                        <textarea 
                                            id="comments_<?php echo $request['id']; ?>" 
                                            rows="3" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Onay veya red gerekçenizi yazabilirsiniz..."
                                        ></textarea>
                                    </div>
                                    
                                    <div class="flex flex-col space-y-2 pt-7">
                                        <button 
                                            onclick="approveRequest(<?php echo $request['id']; ?>)" 
                                            class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                                        >
                                            <i class="fas fa-check mr-2"></i>
                                            Onayla
                                        </button>
                                        <button 
                                            onclick="rejectRequest(<?php echo $request['id']; ?>)" 
                                            class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                                        >
                                            <i class="fas fa-times mr-2"></i>
                                            Reddet
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hidden form for approval actions -->
    <form id="approvalForm" method="POST" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" id="action">
        <input type="hidden" name="request_id" id="request_id">
        <input type="hidden" name="comments" id="comments">
    </form>

    <script>
        function approveRequest(requestId) {
            if (confirm('Bu talebi onaylamak istediğinizden emin misiniz?')) {
                const comments = document.getElementById('comments_' + requestId).value;
                submitApproval('approve', requestId, comments);
            }
        }
        
        function rejectRequest(requestId) {
            if (confirm('Bu talebi reddetmek istediğinizden emin misiniz?')) {
                const comments = document.getElementById('comments_' + requestId).value;
                if (!comments.trim()) {
                    if (!confirm('Red gerekçesi belirtmediniz. Yine de devam etmek istiyor musunuz?')) {
                        return;
                    }
                }
                submitApproval('reject', requestId, comments);
            }
        }
        
        function submitApproval(action, requestId, comments) {
            document.getElementById('action').value = action;
            document.getElementById('request_id').value = requestId;
            document.getElementById('comments').value = comments;
            document.getElementById('approvalForm').submit();
        }

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