<?php
/**
 * Dosya Ekleri Model Sınıfı
 */

class Attachment {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function upload($requestId, $file) {
        // Dosya doğrulaması
        $this->validateFile($file);
        
        // Dosya uzantısını al
        $originalName = $file['name'];
        $pathInfo = pathinfo($originalName);
        $extension = strtolower($pathInfo['extension']);
        
        // Benzersiz dosya adı oluştur
        $fileName = uniqid() . '_' . time() . '.' . $extension;
        $filePath = UPLOAD_DIR . $fileName;
        
        // Dosyayı yükle
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception("Dosya yüklenirken hata oluştu.");
        }
        
        // Veritabanına kaydet
        $query = "INSERT INTO attachments (request_id, original_name, file_path, file_size, mime_type) 
                  VALUES (?, ?, ?, ?, ?)";
        
        $params = [
            $requestId,
            $originalName,
            $fileName,
            $file['size'],
            $file['type']
        ];
        
        try {
            $this->db->execute($query, $params);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            // Dosyayı sil
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            throw new Exception("Dosya kaydedilirken hata: " . $e->getMessage());
        }
    }
    
    public function getByRequestId($requestId) {
        $query = "SELECT * FROM attachments WHERE request_id = ? ORDER BY uploaded_at DESC";
        return $this->db->fetchAll($query, [$requestId]);
    }
    
    public function findById($id) {
        $query = "SELECT * FROM attachments WHERE id = ?";
        return $this->db->fetchOne($query, [$id]);
    }
    
    public function delete($id) {
        $attachment = $this->findById($id);
        
        if (!$attachment) {
            throw new Exception("Dosya bulunamadı.");
        }
        
        // Dosyayı fiziksel olarak sil
        $filePath = UPLOAD_DIR . $attachment['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Veritabanından sil
        $query = "DELETE FROM attachments WHERE id = ?";
        
        try {
            $this->db->execute($query, [$id]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Dosya silinirken hata: " . $e->getMessage());
        }
    }
    
    public function download($id, $userId = null) {
        $attachment = $this->findById($id);
        
        if (!$attachment) {
            throw new Exception("Dosya bulunamadı.");
        }
        
        // Yetki kontrolü (opsiyonel)
        if ($userId) {
            $requestModel = new Request();
            $request = $requestModel->findById($attachment['request_id']);
            
            if (!$request || ($request['user_id'] != $userId && !canApprove($userId))) {
                throw new Exception("Bu dosyaya erişim yetkiniz yok.");
            }
        }
        
        $filePath = UPLOAD_DIR . $attachment['file_path'];
        
        if (!file_exists($filePath)) {
            throw new Exception("Dosya fiziksel olarak bulunamadı.");
        }
        
        return [
            'path' => $filePath,
            'name' => $attachment['original_name'],
            'type' => $attachment['mime_type'],
            'size' => $attachment['file_size']
        ];
    }
    
    private function validateFile($file) {
        // Dosya yükleme hatası kontrolü
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception("Dosya boyutu çok büyük.");
                case UPLOAD_ERR_PARTIAL:
                    throw new Exception("Dosya kısmen yüklendi.");
                case UPLOAD_ERR_NO_FILE:
                    throw new Exception("Dosya seçilmedi.");
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new Exception("Geçici klasör bulunamadı.");
                case UPLOAD_ERR_CANT_WRITE:
                    throw new Exception("Dosya yazılamadı.");
                default:
                    throw new Exception("Bilinmeyen dosya yükleme hatası.");
            }
        }
        
        // Dosya boyutu kontrolü
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception("Dosya boyutu " . $this->formatBytes(MAX_FILE_SIZE) . " sınırını aşıyor.");
        }
        
        // Dosya türü kontrolü
        $pathInfo = pathinfo($file['name']);
        $extension = strtolower($pathInfo['extension']);
        
        if (!in_array($extension, ALLOWED_FILE_TYPES)) {
            throw new Exception("İzin verilen dosya türleri: " . implode(', ', ALLOWED_FILE_TYPES));
        }
        
        // MIME type kontrolü
        $allowedMimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        ];
        
        if (isset($allowedMimeTypes[$extension])) {
            $expectedMimeType = $allowedMimeTypes[$extension];
            if ($file['type'] !== $expectedMimeType) {
                // MIME type kontrolü esnek tut, sadece uyarı ver
                error_log("MIME type uyumsuzluğu: {$file['type']} beklenen: $expectedMimeType");
            }
        }
        
        return true;
    }
    
    public function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    public function getFileIcon($fileName) {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $icons = [
            'pdf' => 'fas fa-file-pdf text-red-500',
            'doc' => 'fas fa-file-word text-blue-500',
            'docx' => 'fas fa-file-word text-blue-500',
            'jpg' => 'fas fa-file-image text-green-500',
            'jpeg' => 'fas fa-file-image text-green-500',
            'png' => 'fas fa-file-image text-green-500'
        ];
        
        return $icons[$extension] ?? 'fas fa-file text-gray-500';
    }
    
    public function getTotalSizeByRequestId($requestId) {
        $query = "SELECT SUM(file_size) as total_size FROM attachments WHERE request_id = ?";
        $result = $this->db->fetchOne($query, [$requestId]);
        return $result['total_size'] ?? 0;
    }
    
    public function getCountByRequestId($requestId) {
        $query = "SELECT COUNT(*) as count FROM attachments WHERE request_id = ?";
        $result = $this->db->fetchOne($query, [$requestId]);
        return $result['count'] ?? 0;
    }
    
    public function canUserAccessFile($attachmentId, $userId) {
        $attachment = $this->findById($attachmentId);
        
        if (!$attachment) {
            return false;
        }
        
        $requestModel = new Request();
        $request = $requestModel->findById($attachment['request_id']);
        
        if (!$request) {
            return false;
        }
        
        // Kendi dosyası veya onaylayabilir mi kontrol et
        return $request['user_id'] == $userId || canApprove($userId);
    }
}