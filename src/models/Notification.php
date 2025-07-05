<?php
/**
 * Bildirim Model Sınıfı
 */

class Notification {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function create($userId, $title, $message, $type = 'info') {
        $query = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
        
        try {
            $this->db->execute($query, [$userId, $title, $message, $type]);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Bildirim oluşturulurken hata: " . $e->getMessage());
        }
    }
    
    public function getByUserId($userId, $limit = 10) {
        $query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
        return $this->db->fetchAll($query, [$userId, $limit]);
    }
    
    public function getUnreadCount($userId) {
        $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $result = $this->db->fetchOne($query, [$userId]);
        return $result['count'];
    }
    
    public function markAsRead($id, $userId) {
        $query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        
        try {
            $this->db->execute($query, [$id, $userId]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Bildirim okundu olarak işaretlenirken hata: " . $e->getMessage());
        }
    }
    
    public function markAllAsRead($userId) {
        $query = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
        
        try {
            $this->db->execute($query, [$userId]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Bildiriler okundu olarak işaretlenirken hata: " . $e->getMessage());
        }
    }
    
    public function delete($id, $userId) {
        $query = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
        
        try {
            $this->db->execute($query, [$id, $userId]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Bildirim silinirken hata: " . $e->getMessage());
        }
    }
    
    public function deleteAll($userId) {
        $query = "DELETE FROM notifications WHERE user_id = ?";
        
        try {
            $this->db->execute($query, [$userId]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Bildiriler silinirken hata: " . $e->getMessage());
        }
    }
    
    public function getNotificationTypeIcon($type) {
        $icons = [
            'info' => '🔔',
            'success' => '✅',
            'warning' => '⚠️',
            'error' => '❌'
        ];
        return $icons[$type] ?? '🔔';
    }
    
    public function getNotificationTypeColor($type) {
        $colors = [
            'info' => 'text-blue-600 bg-blue-100',
            'success' => 'text-green-600 bg-green-100',
            'warning' => 'text-yellow-600 bg-yellow-100',
            'error' => 'text-red-600 bg-red-100'
        ];
        return $colors[$type] ?? 'text-blue-600 bg-blue-100';
    }
}