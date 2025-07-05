<?php
require_once '../config/app.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('index.php');
}

$user = getCurrentUser();
$requestModel = new Request();
$error = '';
$success = '';

// Talep türlerini al
$requestTypes = $requestModel->getRequestTypes();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Güvenlik hatası. Lütfen sayfayı yenileyin.';
    } else {
        $requestData = [
            'user_id' => $user['id'],
            'request_type_id' => sanitize($_POST['request_type_id'] ?? ''),
            'title' => sanitize($_POST['title'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'start_date' => sanitize($_POST['start_date'] ?? ''),
            'end_date' => sanitize($_POST['end_date'] ?? ''),
            'amount' => sanitize($_POST['amount'] ?? ''),
            'urgency' => sanitize($_POST['urgency'] ?? 'medium')
        ];
        
        // Validasyon
        $errors = [];
        
        if (empty($requestData['request_type_id'])) {
            $errors[] = 'Talep türü seçilmelidir.';
        }
        
        if (empty($requestData['title'])) {
            $errors[] = 'Talep başlığı zorunludur.';
        } elseif (strlen($requestData['title']) < 5) {
            $errors[] = 'Talep başlığı en az 5 karakter olmalıdır.';
        }
        
        if (empty($requestData['description'])) {
            $errors[] = 'Talep açıklaması zorunludur.';
        } elseif (strlen($requestData['description']) < 10) {
            $errors[] = 'Talep açıklaması en az 10 karakter olmalıdır.';
        }
        
        // Tarih validasyonu
        if (!empty($requestData['start_date']) && !empty($requestData['end_date'])) {
            if (strtotime($requestData['start_date']) > strtotime($requestData['end_date'])) {
                $errors[] = 'Başlangıç tarihi bitiş tarihinden sonra olamaz.';
            }
        }
        
        // Miktar validasyonu
        if (!empty($requestData['amount']) && (!is_numeric($requestData['amount']) || $requestData['amount'] < 0)) {
            $errors[] = 'Miktar pozitif bir sayı olmalıdır.';
        }
        
        if (empty($errors)) {
            try {
                $requestId = $requestModel->create($requestData);
                
                // Başarılı mesaj
                addFlashMessage('success', 'Talebiniz başarıyla oluşturuldu. Onay sürecine alınmıştır.');
                redirect('my-requests.php');
            } catch (Exception $e) {
                $error = 'Talep oluşturulurken hata: ' . $e->getMessage();
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Yeni Talep</title>
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
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-plus-circle mr-2 text-blue-600"></i>
                        Yeni Talep Oluştur
                    </h1>
                    <p class="text-gray-600">Aşağıdaki formu doldurarak yeni talebinizi oluşturun.</p>
                </div>
                <a href="dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Geri Dön
                </a>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white shadow-lg rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Talep Bilgileri</h2>
                <p class="text-sm text-gray-600">Lütfen tüm alanları eksiksiz doldurun.</p>
            </div>
            
            <form method="POST" class="px-6 py-4">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Talep Türü -->
                    <div class="md:col-span-2">
                        <label for="request_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Talep Türü <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="request_type_id" 
                            name="request_type_id" 
                            required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            onchange="toggleFields()"
                        >
                            <option value="">Talep türü seçin</option>
                            <?php foreach ($requestTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>" <?php echo (isset($_POST['request_type_id']) && $_POST['request_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                                    <?php echo $type['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Oluşturmak istediğiniz talep türünü seçin.</p>
                    </div>

                    <!-- Başlık -->
                    <div class="md:col-span-2">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Talep Başlığı <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            required 
                            maxlength="255"
                            value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Talep başlığını girin"
                        >
                        <p class="mt-1 text-sm text-gray-500">Talebinizi özetleyen kısa ve açıklayıcı bir başlık.</p>
                    </div>

                    <!-- Açıklama -->
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Talep Açıklaması <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            required 
                            rows="4"
                            maxlength="1000"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Talebinizi detaylı olarak açıklayın"
                        ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <p class="mt-1 text-sm text-gray-500">Talebinizin detaylarını ve gerekçesini açıklayın.</p>
                    </div>

                    <!-- Başlangıç Tarihi -->
                    <div id="start_date_field" class="hidden">
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Başlangıç Tarihi
                        </label>
                        <input 
                            type="date" 
                            id="start_date" 
                            name="start_date" 
                            value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            min="<?php echo date('Y-m-d'); ?>"
                        >
                        <p class="mt-1 text-sm text-gray-500">İzin başlangıç tarihi.</p>
                    </div>

                    <!-- Bitiş Tarihi -->
                    <div id="end_date_field" class="hidden">
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Bitiş Tarihi
                        </label>
                        <input 
                            type="date" 
                            id="end_date" 
                            name="end_date" 
                            value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            min="<?php echo date('Y-m-d'); ?>"
                        >
                        <p class="mt-1 text-sm text-gray-500">İzin bitiş tarihi.</p>
                    </div>

                    <!-- Miktar -->
                    <div id="amount_field" class="hidden">
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                            Miktar (₺)
                        </label>
                        <input 
                            type="number" 
                            id="amount" 
                            name="amount" 
                            step="0.01" 
                            min="0"
                            value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="0.00"
                        >
                        <p class="mt-1 text-sm text-gray-500">Talep ettiğiniz miktar (avans, fazla mesai ücreti vb.).</p>
                    </div>

                    <!-- Aciliyet -->
                    <div class="md:col-span-2">
                        <label for="urgency" class="block text-sm font-medium text-gray-700 mb-2">
                            Aciliyet Durumu
                        </label>
                        <select 
                            id="urgency" 
                            name="urgency" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="low" <?php echo (isset($_POST['urgency']) && $_POST['urgency'] === 'low') ? 'selected' : ''; ?>>
                                Düşük
                            </option>
                            <option value="medium" <?php echo (!isset($_POST['urgency']) || $_POST['urgency'] === 'medium') ? 'selected' : ''; ?>>
                                Orta
                            </option>
                            <option value="high" <?php echo (isset($_POST['urgency']) && $_POST['urgency'] === 'high') ? 'selected' : ''; ?>>
                                Yüksek
                            </option>
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Talebinizin aciliyet durumunu belirtin.</p>
                    </div>
                </div>

                <!-- Form Butonları -->
                <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                    <a href="dashboard.php" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        İptal
                    </a>
                    <button 
                        type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <i class="fas fa-paper-plane mr-2"></i>
                        Talep Oluştur
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleFields() {
            const requestTypeId = document.getElementById('request_type_id').value;
            const startDateField = document.getElementById('start_date_field');
            const endDateField = document.getElementById('end_date_field');
            const amountField = document.getElementById('amount_field');
            
            // Tüm alanları gizle
            startDateField.classList.add('hidden');
            endDateField.classList.add('hidden');
            amountField.classList.add('hidden');
            
            // Seçilen türe göre alanları göster
            if (requestTypeId) {
                const requestTypes = <?php echo json_encode(array_column($requestTypes, 'name', 'id')); ?>;
                const selectedType = requestTypes[requestTypeId];
                
                if (selectedType === 'İzin') {
                    startDateField.classList.remove('hidden');
                    endDateField.classList.remove('hidden');
                    document.getElementById('start_date').required = true;
                    document.getElementById('end_date').required = true;
                } else if (selectedType === 'Avans' || selectedType === 'Fazla Mesai') {
                    amountField.classList.remove('hidden');
                    document.getElementById('amount').required = true;
                }
            }
        }
        
        // Sayfa yüklendiğinde alanları kontrol et
        document.addEventListener('DOMContentLoaded', function() {
            toggleFields();
        });
        
        // Tarih doğrulaması
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = this.value;
            const endDateField = document.getElementById('end_date');
            
            if (startDate) {
                endDateField.min = startDate;
                if (endDateField.value && endDateField.value < startDate) {
                    endDateField.value = startDate;
                }
            }
        });
        
        // Form gönderim doğrulaması
        document.querySelector('form').addEventListener('submit', function(e) {
            const requestTypeId = document.getElementById('request_type_id').value;
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            
            if (!requestTypeId) {
                e.preventDefault();
                alert('Lütfen talep türünü seçin.');
                return;
            }
            
            if (!title || title.length < 5) {
                e.preventDefault();
                alert('Talep başlığı en az 5 karakter olmalıdır.');
                return;
            }
            
            if (!description || description.length < 10) {
                e.preventDefault();
                alert('Talep açıklaması en az 10 karakter olmalıdır.');
                return;
            }
            
            // Tarih kontrolü
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            if (startDate && endDate && startDate > endDate) {
                e.preventDefault();
                alert('Başlangıç tarihi bitiş tarihinden sonra olamaz.');
                return;
            }
            
            // Miktar kontrolü
            const amount = document.getElementById('amount').value;
            if (amount && (isNaN(amount) || parseFloat(amount) < 0)) {
                e.preventDefault();
                alert('Miktar pozitif bir sayı olmalıdır.');
                return;
            }
        });
    </script>
</body>
</html>