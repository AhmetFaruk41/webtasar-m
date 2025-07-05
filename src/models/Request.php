<?php
/**
 * Talep Model Sınıfı
 */

class Request {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function create($data) {
        $query = "INSERT INTO requests (user_id, request_type_id, title, description, 
                  start_date, end_date, amount, urgency, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['user_id'],
            $data['request_type_id'],
            $data['title'],
            $data['description'] ?? null,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['amount'] ?? null,
            $data['urgency'] ?? 'medium',
            'pending'
        ];
        
        try {
            $this->db->beginTransaction();
            
            $this->db->execute($query, $params);
            $requestId = $this->db->lastInsertId();
            
            // Onaylayanı belirle ve onay kaydı oluştur
            $this->createApprovalRecord($requestId, $data['user_id']);
            
            $this->db->commit();
            return $requestId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Talep oluşturulurken hata: " . $e->getMessage());
        }
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['title', 'description', 'start_date', 'end_date', 'amount', 'urgency'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            throw new Exception("Güncellenecek alan bulunamadı.");
        }
        
        $params[] = $id;
        $query = "UPDATE requests SET " . implode(', ', $fields) . " WHERE id = ?";
        
        try {
            $this->db->execute($query, $params);
            return true;
        } catch (Exception $e) {
            throw new Exception("Talep güncellenirken hata: " . $e->getMessage());
        }
    }
    
    public function updateStatus($id, $status) {
        $query = "UPDATE requests SET status = ? WHERE id = ?";
        
        try {
            $this->db->execute($query, [$status, $id]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Talep durumu güncellenirken hata: " . $e->getMessage());
        }
    }
    
    public function findById($id) {
        $query = "SELECT r.*, rt.name as request_type_name, rt.description as request_type_description,
                  u.first_name, u.last_name, u.employee_id, u.department, u.position
                  FROM requests r
                  LEFT JOIN request_types rt ON r.request_type_id = rt.id
                  LEFT JOIN users u ON r.user_id = u.id
                  WHERE r.id = ?";
        return $this->db->fetchOne($query, [$id]);
    }
    
    public function getByUserId($userId, $filters = []) {
        $query = "SELECT r.*, rt.name as request_type_name, rt.description as request_type_description
                  FROM requests r
                  LEFT JOIN request_types rt ON r.request_type_id = rt.id
                  WHERE r.user_id = ?";
        
        $params = [$userId];
        
        if (!empty($filters['status'])) {
            $query .= " AND r.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['request_type_id'])) {
            $query .= " AND r.request_type_id = ?";
            $params[] = $filters['request_type_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND r.created_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND r.created_at <= ?";
            $params[] = $filters['date_to'];
        }
        
        $query .= " ORDER BY r.created_at DESC";
        
        return $this->db->fetchAll($query, $params);
    }
    
    public function getForApproval($approverId) {
        $query = "SELECT r.*, rt.name as request_type_name, rt.description as request_type_description,
                  u.first_name, u.last_name, u.employee_id, u.department, u.position,
                  a.status as approval_status, a.comments as approval_comments
                  FROM requests r
                  LEFT JOIN request_types rt ON r.request_type_id = rt.id
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN approvals a ON r.id = a.request_id
                  WHERE a.approver_id = ? AND a.status = 'pending'
                  ORDER BY r.created_at DESC";
        
        return $this->db->fetchAll($query, [$approverId]);
    }
    
    public function getAll($filters = []) {
        $query = "SELECT r.*, rt.name as request_type_name, rt.description as request_type_description,
                  u.first_name, u.last_name, u.employee_id, u.department, u.position
                  FROM requests r
                  LEFT JOIN request_types rt ON r.request_type_id = rt.id
                  LEFT JOIN users u ON r.user_id = u.id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $query .= " AND r.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['request_type_id'])) {
            $query .= " AND r.request_type_id = ?";
            $params[] = $filters['request_type_id'];
        }
        
        if (!empty($filters['department'])) {
            $query .= " AND u.department = ?";
            $params[] = $filters['department'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND r.created_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND r.created_at <= ?";
            $params[] = $filters['date_to'];
        }
        
        $query .= " ORDER BY r.created_at DESC";
        
        return $this->db->fetchAll($query, $params);
    }
    
    public function approve($requestId, $approverId, $comments = null) {
        try {
            $this->db->beginTransaction();
            
            // Onay kaydını güncelle
            $approvalQuery = "UPDATE approvals SET status = 'approved', comments = ?, 
                             approved_at = CURRENT_TIMESTAMP WHERE request_id = ? AND approver_id = ?";
            $this->db->execute($approvalQuery, [$comments, $requestId, $approverId]);
            
            // Talep durumunu güncelle
            $this->updateStatus($requestId, 'approved');
            
            // Bildirim oluştur
            $request = $this->findById($requestId);
            $this->createNotification($request['user_id'], 'Talep Onaylandı', 
                                    'Talebiniz onaylandı: ' . $request['title'], 'success');
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Talep onaylanırken hata: " . $e->getMessage());
        }
    }
    
    public function reject($requestId, $approverId, $comments = null) {
        try {
            $this->db->beginTransaction();
            
            // Onay kaydını güncelle
            $approvalQuery = "UPDATE approvals SET status = 'rejected', comments = ?, 
                             approved_at = CURRENT_TIMESTAMP WHERE request_id = ? AND approver_id = ?";
            $this->db->execute($approvalQuery, [$comments, $requestId, $approverId]);
            
            // Talep durumunu güncelle
            $this->updateStatus($requestId, 'rejected');
            
            // Bildirim oluştur
            $request = $this->findById($requestId);
            $this->createNotification($request['user_id'], 'Talep Reddedildi', 
                                    'Talebiniz reddedildi: ' . $request['title'], 'error');
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Talep reddedilirken hata: " . $e->getMessage());
        }
    }
    
    public function cancel($requestId, $userId) {
        // Sadece kendi talebi iptal edebilir ve beklemede olmalı
        $request = $this->findById($requestId);
        
        if (!$request || $request['user_id'] != $userId) {
            throw new Exception("Bu talebi iptal etme yetkiniz yok.");
        }
        
        if ($request['status'] !== 'pending') {
            throw new Exception("Sadece beklemede olan talepler iptal edilebilir.");
        }
        
        try {
            $this->updateStatus($requestId, 'cancelled');
            return true;
        } catch (Exception $e) {
            throw new Exception("Talep iptal edilirken hata: " . $e->getMessage());
        }
    }
    
    public function delete($id) {
        $query = "DELETE FROM requests WHERE id = ?";
        
        try {
            $this->db->execute($query, [$id]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Talep silinirken hata: " . $e->getMessage());
        }
    }
    
    public function getRequestTypes() {
        $query = "SELECT * FROM request_types WHERE is_active = 1 ORDER BY name";
        return $this->db->fetchAll($query);
    }
    
    public function getStats($userId = null, $role = null) {
        if ($userId && $role !== 'admin') {
            // Kullanıcının kendi istatistikleri
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                      FROM requests WHERE user_id = ?";
            return $this->db->fetchOne($query, [$userId]);
        } else {
            // Tüm sistem istatistikleri
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                      FROM requests";
            return $this->db->fetchOne($query);
        }
    }
    
    public function getMonthlyStats($userId = null) {
        $query = "SELECT 
                    MONTH(created_at) as month,
                    YEAR(created_at) as year,
                    COUNT(*) as count
                  FROM requests";
        
        $params = [];
        
        if ($userId) {
            $query .= " WHERE user_id = ?";
            $params[] = $userId;
        }
        
        $query .= " GROUP BY YEAR(created_at), MONTH(created_at)
                   ORDER BY YEAR(created_at) DESC, MONTH(created_at) DESC
                   LIMIT 12";
        
        return $this->db->fetchAll($query, $params);
    }
    
    private function createApprovalRecord($requestId, $userId) {
        // Kullanıcının yöneticisini bul
        $userQuery = "SELECT manager_id FROM users WHERE id = ?";
        $user = $this->db->fetchOne($userQuery, [$userId]);
        
        $approverId = $user['manager_id'];
        
        // Eğer yönetici yoksa, admin'i onaylayan yap
        if (!$approverId) {
            $adminQuery = "SELECT id FROM users WHERE role = 'admin' AND is_active = 1 LIMIT 1";
            $admin = $this->db->fetchOne($adminQuery);
            $approverId = $admin['id'];
        }
        
        $approvalQuery = "INSERT INTO approvals (request_id, approver_id, status) VALUES (?, ?, 'pending')";
        $this->db->execute($approvalQuery, [$requestId, $approverId]);
        
        // Onaylayıcıya bildirim gönder
        $this->createNotification($approverId, 'Yeni Talep', 
                                'Onayınızı bekleyen yeni bir talep var.', 'info');
    }
    
    private function createNotification($userId, $title, $message, $type = 'info') {
        $query = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
        $this->db->execute($query, [$userId, $title, $message, $type]);
    }
    
    public function getApprovalHistory($requestId) {
        $query = "SELECT a.*, u.first_name, u.last_name, u.position
                  FROM approvals a
                  LEFT JOIN users u ON a.approver_id = u.id
                  WHERE a.request_id = ?
                  ORDER BY a.created_at DESC";
        
        return $this->db->fetchAll($query, [$requestId]);
    }
    
    public function canEdit($requestId, $userId) {
        $request = $this->findById($requestId);
        
        if (!$request) {
            return false;
        }
        
        // Sadece kendi talebi ve beklemede olan talepler düzenlenebilir
        return $request['user_id'] == $userId && $request['status'] === 'pending';
    }
    
    public function canCancel($requestId, $userId) {
        $request = $this->findById($requestId);
        
        if (!$request) {
            return false;
        }
        
        // Sadece kendi talebi ve beklemede olan talepler iptal edilebilir
        return $request['user_id'] == $userId && $request['status'] === 'pending';
    }
}