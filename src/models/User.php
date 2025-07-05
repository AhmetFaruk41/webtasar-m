<?php
/**
 * Kullanıcı Model Sınıfı
 */

class User {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function authenticate($email, $password) {
        $query = "SELECT * FROM users WHERE email = ? AND is_active = 1";
        $user = $this->db->fetchOne($query, [$email]);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Oturum verilerini ayarla
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_data'] = [
                'id' => $user['id'],
                'employee_id' => $user['employee_id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'department' => $user['department'],
                'position' => $user['position'],
                'role' => $user['role'],
                'manager_id' => $user['manager_id']
            ];
            
            // Son giriş zamanını güncelle
            $this->updateLastLogin($user['id']);
            
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function create($data) {
        $query = "INSERT INTO users (employee_id, email, password_hash, first_name, last_name, 
                  department, position, manager_id, role, phone, hire_date, salary) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['employee_id'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['first_name'],
            $data['last_name'],
            $data['department'],
            $data['position'],
            $data['manager_id'] ?? null,
            $data['role'] ?? 'employee',
            $data['phone'] ?? null,
            $data['hire_date'] ?? null,
            $data['salary'] ?? null
        ];
        
        try {
            $this->db->execute($query, $params);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Kullanıcı oluşturulurken hata: " . $e->getMessage());
        }
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['employee_id', 'email', 'first_name', 'last_name', 
                         'department', 'position', 'manager_id', 'role', 'phone', 
                         'hire_date', 'salary', 'is_active'];
        
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
        $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        
        try {
            $this->db->execute($query, $params);
            return true;
        } catch (Exception $e) {
            throw new Exception("Kullanıcı güncellenirken hata: " . $e->getMessage());
        }
    }
    
    public function changePassword($id, $currentPassword, $newPassword) {
        // Mevcut şifreyi kontrol et
        $user = $this->findById($id);
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            throw new Exception("Mevcut şifre yanlış.");
        }
        
        // Yeni şifreyi güncelle
        $query = "UPDATE users SET password_hash = ? WHERE id = ?";
        $params = [password_hash($newPassword, PASSWORD_DEFAULT), $id];
        
        try {
            $this->db->execute($query, $params);
            return true;
        } catch (Exception $e) {
            throw new Exception("Şifre güncellenirken hata: " . $e->getMessage());
        }
    }
    
    public function findById($id) {
        $query = "SELECT * FROM users WHERE id = ?";
        return $this->db->fetchOne($query, [$id]);
    }
    
    public function findByEmail($email) {
        $query = "SELECT * FROM users WHERE email = ?";
        return $this->db->fetchOne($query, [$email]);
    }
    
    public function findByEmployeeId($employeeId) {
        $query = "SELECT * FROM users WHERE employee_id = ?";
        return $this->db->fetchOne($query, [$employeeId]);
    }
    
    public function getAll($filters = []) {
        $query = "SELECT u.*, m.first_name as manager_first_name, m.last_name as manager_last_name 
                  FROM users u 
                  LEFT JOIN users m ON u.manager_id = m.id";
        
        $whereClause = [];
        $params = [];
        
        if (!empty($filters['department'])) {
            $whereClause[] = "u.department = ?";
            $params[] = $filters['department'];
        }
        
        if (!empty($filters['role'])) {
            $whereClause[] = "u.role = ?";
            $params[] = $filters['role'];
        }
        
        if (!empty($filters['is_active'])) {
            $whereClause[] = "u.is_active = ?";
            $params[] = $filters['is_active'];
        }
        
        if (!empty($whereClause)) {
            $query .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $query .= " ORDER BY u.first_name, u.last_name";
        
        return $this->db->fetchAll($query, $params);
    }
    
    public function getManagers() {
        $query = "SELECT * FROM users WHERE role IN ('manager', 'admin') AND is_active = 1 
                  ORDER BY first_name, last_name";
        return $this->db->fetchAll($query);
    }
    
    public function getEmployeesByManager($managerId) {
        $query = "SELECT * FROM users WHERE manager_id = ? AND is_active = 1 
                  ORDER BY first_name, last_name";
        return $this->db->fetchAll($query, [$managerId]);
    }
    
    public function getDepartments() {
        $query = "SELECT DISTINCT department FROM users WHERE is_active = 1 ORDER BY department";
        return $this->db->fetchAll($query);
    }
    
    public function delete($id) {
        // Kullanıcıyı pasif yap (veri bütünlüğü için)
        $query = "UPDATE users SET is_active = 0 WHERE id = ?";
        
        try {
            $this->db->execute($query, [$id]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Kullanıcı silinirken hata: " . $e->getMessage());
        }
    }
    
    public function checkEmailExists($email, $excludeId = null) {
        $query = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $params = [$email];
        
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetchOne($query, $params);
        return $result['count'] > 0;
    }
    
    public function checkEmployeeIdExists($employeeId, $excludeId = null) {
        $query = "SELECT COUNT(*) as count FROM users WHERE employee_id = ?";
        $params = [$employeeId];
        
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetchOne($query, $params);
        return $result['count'] > 0;
    }
    
    private function updateLastLogin($userId) {
        $query = "UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $this->db->execute($query, [$userId]);
    }
    
    public function getProfile($userId) {
        $query = "SELECT u.*, m.first_name as manager_first_name, m.last_name as manager_last_name,
                  m.email as manager_email
                  FROM users u 
                  LEFT JOIN users m ON u.manager_id = m.id 
                  WHERE u.id = ?";
        return $this->db->fetchOne($query, [$userId]);
    }
    
    public function updateProfile($userId, $data) {
        $allowedFields = ['first_name', 'last_name', 'phone'];
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            throw new Exception("Güncellenecek alan bulunamadı.");
        }
        
        $params[] = $userId;
        $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        
        try {
            $this->db->execute($query, $params);
            
            // Oturum verilerini güncelle
            $user = $this->findById($userId);
            $_SESSION['user_data']['first_name'] = $user['first_name'];
            $_SESSION['user_data']['last_name'] = $user['last_name'];
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Profil güncellenirken hata: " . $e->getMessage());
        }
    }
}