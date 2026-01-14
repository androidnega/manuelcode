<?php

class UserIDGenerator {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Generate a unique 6-character user ID based on name and numbers
     */
    public function generateUserID($name) {
        // Clean the name - remove special characters and spaces
        $cleanName = preg_replace('/[^A-Za-z]/', '', $name);
        
        // Get first 3 characters of the name (uppercase)
        $namePart = strtoupper(substr($cleanName, 0, 3));
        
        // If name is less than 3 characters, pad with 'X'
        while (strlen($namePart) < 3) {
            $namePart .= 'X';
        }
        
        // Generate a random 3-digit number
        $numberPart = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        
        // Combine name and number
        $userID = $namePart . $numberPart;
        
        // Check if this user ID already exists
        $attempts = 0;
        while ($this->userIDExists($userID) && $attempts < 10) {
            $numberPart = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
            $userID = $namePart . $numberPart;
            $attempts++;
        }
        
        // If still not unique after 10 attempts, add a letter
        if ($this->userIDExists($userID)) {
            $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $userID = substr($userID, 0, 5) . $letters[rand(0, 25)];
        }
        
        return $userID;
    }
    
    /**
     * Check if a user ID already exists
     */
    private function userIDExists($userID) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM users WHERE user_id = ?");
        $stmt->execute([$userID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    
    /**
     * Update existing users with user IDs if they don't have one
     */
    public function updateExistingUsers() {
        $stmt = $this->pdo->query("SELECT id, name FROM users WHERE user_id IS NULL OR user_id = ''");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $updated = 0;
        foreach ($users as $user) {
            $userID = $this->generateUserID($user['name']);
            $updateStmt = $this->pdo->prepare("UPDATE users SET user_id = ? WHERE id = ?");
            $updateStmt->execute([$userID, $user['id']]);
            $updated++;
        }
        
        return $updated;
    }
    
    /**
     * Get user ID by user ID string
     */
    public function getUserByUserID($userID) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$userID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Validate user ID format (6 characters: 3 letters + 3 numbers)
     */
    public function validateUserID($userID) {
        return preg_match('/^[A-Z]{3}[0-9]{3}$/', $userID);
    }
}

?>
