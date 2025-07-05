<?php
require_once '../config/app.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    if (isset($_GET['action']) && $_GET['action'] === 'download') {
        header('HTTP/1.0 403 Forbidden');
        exit('Giriş yapmanız gerekiyor.');
    }
    redirect('index.php');
}

$user = getCurrentUser();
$attachmentModel = new Attachment();

// Dosya yükleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    header('Content-Type: application/json');
    
    try {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Güvenlik hatası.');
        }
        
        $requestId = sanitize($_POST['request_id'] ?? '');
        
        if (empty($requestId)) {
            throw new Exception('Talep ID gerekli.');
        }
        
        // Talep kontrolü
        $requestModel = new Request();
        $request = $requestModel->findById($requestId);
        
        if (!$request) {
            throw new Exception('Talep bulunamadı.');
        }
        
        // Yetki kontrolü - sadece kendi talebi
        if ($request['user_id'] != $user['id']) {
            throw new Exception('Bu talebe dosya ekleme yetkiniz yok.');
        }
        
        // Dosya kontrolü
        if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
            throw new Exception('Dosya seçilmedi.');
        }
        
        // Dosyayı yükle
        $attachmentId = $attachmentModel->upload($requestId, $_FILES['file']);
        
        // Yüklenen dosya bilgilerini getir
        $attachment = $attachmentModel->findById($attachmentId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Dosya başarıyla yüklendi.',
            'attachment' => [
                'id' => $attachment['id'],
                'name' => $attachment['original_name'],
                'size' => $attachmentModel->formatBytes($attachment['file_size']),
                'uploaded_at' => formatDateTime($attachment['uploaded_at']),
                'icon' => $attachmentModel->getFileIcon($attachment['original_name'])
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Dosya indirme
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    $attachmentId = sanitize($_GET['id'] ?? '');
    
    if (empty($attachmentId)) {
        header('HTTP/1.0 404 Not Found');
        exit('Dosya ID gerekli.');
    }
    
    try {
        // Yetki kontrolü
        if (!$attachmentModel->canUserAccessFile($attachmentId, $user['id'])) {
            header('HTTP/1.0 403 Forbidden');
            exit('Bu dosyaya erişim yetkiniz yok.');
        }
        
        $fileInfo = $attachmentModel->download($attachmentId, $user['id']);
        
        // Dosya headers
        header('Content-Type: ' . $fileInfo['type']);
        header('Content-Disposition: attachment; filename="' . $fileInfo['name'] . '"');
        header('Content-Length: ' . $fileInfo['size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        // Dosyayı çıktıla
        readfile($fileInfo['path']);
        
    } catch (Exception $e) {
        header('HTTP/1.0 404 Not Found');
        exit($e->getMessage());
    }
    exit;
}

// Dosya silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    header('Content-Type: application/json');
    
    try {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Güvenlik hatası.');
        }
        
        $attachmentId = sanitize($_POST['attachment_id'] ?? '');
        
        if (empty($attachmentId)) {
            throw new Exception('Dosya ID gerekli.');
        }
        
        // Yetki kontrolü
        if (!$attachmentModel->canUserAccessFile($attachmentId, $user['id'])) {
            throw new Exception('Bu dosyayı silme yetkiniz yok.');
        }
        
        $attachmentModel->delete($attachmentId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Dosya başarıyla silindi.'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Talep dosyalarını listele
if (isset($_GET['action']) && $_GET['action'] === 'list') {
    header('Content-Type: application/json');
    
    $requestId = sanitize($_GET['request_id'] ?? '');
    
    if (empty($requestId)) {
        echo json_encode([
            'success' => false,
            'message' => 'Talep ID gerekli.'
        ]);
        exit;
    }
    
    try {
        // Talep kontrolü
        $requestModel = new Request();
        $request = $requestModel->findById($requestId);
        
        if (!$request) {
            throw new Exception('Talep bulunamadı.');
        }
        
        // Yetki kontrolü
        if ($request['user_id'] != $user['id'] && !canApprove($user['id'])) {
            throw new Exception('Bu talebin dosyalarını görme yetkiniz yok.');
        }
        
        $attachments = $attachmentModel->getByRequestId($requestId);
        
        $result = [];
        foreach ($attachments as $attachment) {
            $result[] = [
                'id' => $attachment['id'],
                'name' => $attachment['original_name'],
                'size' => $attachmentModel->formatBytes($attachment['file_size']),
                'uploaded_at' => formatDateTime($attachment['uploaded_at']),
                'icon' => $attachmentModel->getFileIcon($attachment['original_name']),
                'download_url' => 'file-upload.php?action=download&id=' . $attachment['id']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'attachments' => $result,
            'count' => count($result),
            'total_size' => $attachmentModel->formatBytes($attachmentModel->getTotalSizeByRequestId($requestId))
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Geçersiz istek
header('HTTP/1.0 400 Bad Request');
exit('Geçersiz istek.');
?>