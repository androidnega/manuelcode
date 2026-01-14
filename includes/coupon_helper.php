<?php
// Coupon Helper Functions
// Handles coupon validation, discount calculation, and usage tracking

class CouponManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Validate a coupon code
     */
    public function validateCoupon($code, $user_id = null, $product_id = null, $amount = 0) {
        try {
            // Get coupon details
            $stmt = $this->pdo->prepare("
                SELECT * FROM coupons 
                WHERE code = ? AND is_active = TRUE
            ");
            $stmt->execute([strtoupper(trim($code))]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$coupon) {
                return ['valid' => false, 'message' => 'Invalid coupon code'];
            }
            
            // Check if coupon is expired
            if ($coupon['valid_until'] && strtotime($coupon['valid_until']) < time()) {
                return ['valid' => false, 'message' => 'Coupon has expired'];
            }
            
            // Check if coupon is not yet valid
            if ($coupon['valid_from'] && strtotime($coupon['valid_from']) > time()) {
                return ['valid' => false, 'message' => 'Coupon is not yet valid'];
            }
            
            // Check usage limit
            if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
                return ['valid' => false, 'message' => 'Coupon usage limit reached'];
            }
            
            // Check minimum amount
            if ($amount < $coupon['minimum_amount']) {
                return ['valid' => false, 'message' => 'Minimum order amount of ₵' . number_format($coupon['minimum_amount'], 2) . ' required'];
            }
            
            // Check if user has already used this coupon
            if ($user_id) {
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) as usage_count 
                    FROM coupon_usage 
                    WHERE coupon_id = ? AND user_id = ?
                ");
                $stmt->execute([$coupon['id'], $user_id]);
                $usage = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($usage['usage_count'] >= $coupon['user_limit']) {
                    return ['valid' => false, 'message' => 'You have already used this coupon'];
                }
            }
            
            // Check if coupon applies to specific products
            if ($coupon['applies_to'] === 'specific_products' && $product_id) {
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM coupon_products 
                    WHERE coupon_id = ? AND product_id = ?
                ");
                $stmt->execute([$coupon['id'], $product_id]);
                $product_check = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product_check['count'] == 0) {
                    return ['valid' => false, 'message' => 'Coupon does not apply to this product'];
                }
            }
            
            return [
                'valid' => true, 
                'coupon' => $coupon,
                'message' => 'Coupon applied successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Coupon validation error: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Error validating coupon'];
        }
    }
    
    /**
     * Calculate discount amount
     */
    public function calculateDiscount($coupon, $amount) {
        $discount = 0;
        
        if ($coupon['discount_type'] === 'percentage') {
            $discount = ($amount * $coupon['discount_value']) / 100;
            
            // Apply maximum discount limit if set
            if (isset($coupon['maximum_discount']) && $coupon['maximum_discount'] && $discount > $coupon['maximum_discount']) {
                $discount = $coupon['maximum_discount'];
            }
        } else {
            $discount = $coupon['discount_value'];
        }
        
        // Ensure discount doesn't exceed the amount
        return min($discount, $amount);
    }
    
    /**
     * Apply coupon to an order
     */
    public function applyCoupon($coupon_id, $user_id, $order_id, $original_amount, $discount_amount) {
        try {
            $this->pdo->beginTransaction();
            
            // Record coupon usage
            $stmt = $this->pdo->prepare("
                INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount, original_amount, final_amount) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $final_amount = $original_amount - $discount_amount;
            $stmt->execute([$coupon_id, $user_id, $order_id, $discount_amount, $original_amount, $final_amount]);
            
            // Update coupon usage count
            $stmt = $this->pdo->prepare("
                UPDATE coupons 
                SET used_count = used_count + 1 
                WHERE id = ?
            ");
            $stmt->execute([$coupon_id]);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("Error applying coupon: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all active coupons
     */
    public function getActiveCoupons() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM coupons 
                WHERE is_active = TRUE 
                AND (valid_until IS NULL OR valid_until > NOW())
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting active coupons: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all coupons (for admin management)
     */
    public function getAllCoupons() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM coupons 
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting all coupons: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get coupon usage statistics
     */
    public function getCouponStats($coupon_id = null) {
        try {
            if ($coupon_id) {
                $stmt = $this->pdo->prepare("
                    SELECT 
                        c.*,
                        COUNT(cu.id) as total_usage,
                        SUM(cu.discount_amount) as total_discount_given
                    FROM coupons c
                    LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id
                    WHERE c.id = ?
                    GROUP BY c.id
                ");
                $stmt->execute([$coupon_id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->pdo->prepare("
                    SELECT 
                        c.*,
                        COUNT(cu.id) as total_usage,
                        SUM(cu.discount_amount) as total_discount_given
                    FROM coupons c
                    LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id
                    GROUP BY c.id
                    ORDER BY c.created_at DESC
                ");
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            error_log("Error getting coupon stats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create a new coupon
     */
    public function createCoupon($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO coupons (
                    code, name, description, discount_type, discount_value, 
                    minimum_amount, maximum_discount, usage_limit, user_limit,
                    valid_from, valid_until, applies_to, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                strtoupper(trim($data['code'])),
                $data['name'],
                $data['description'],
                $data['discount_type'],
                $data['discount_value'],
                $data['minimum_amount'] ?? 0,
                $data['maximum_discount'] ?? null,
                $data['usage_limit'] ?? null,
                $data['user_limit'] ?? 1,
                $data['valid_from'] ?? date('Y-m-d H:i:s'),
                $data['valid_until'] ?? null,
                $data['applies_to'] ?? 'all',
                $data['created_by'] ?? null
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creating coupon: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update coupon
     */
    public function updateCoupon($coupon_id, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE coupons SET
                    code = ?, name = ?, description = ?, discount_type = ?, discount_value = ?,
                    minimum_amount = ?, maximum_discount = ?, usage_limit = ?, user_limit = ?,
                    valid_from = ?, valid_until = ?, applies_to = ?, is_active = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                strtoupper(trim($data['code'])),
                $data['name'],
                $data['description'],
                $data['discount_type'],
                $data['discount_value'],
                $data['minimum_amount'] ?? 0,
                $data['maximum_discount'] ?? null,
                $data['usage_limit'] ?? null,
                $data['user_limit'] ?? 1,
                $data['valid_from'] ?? date('Y-m-d H:i:s'),
                $data['valid_until'] ?? null,
                $data['applies_to'] ?? 'all',
                $data['is_active'] ?? true,
                $coupon_id
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error updating coupon: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete coupon
     */
    public function deleteCoupon($coupon_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM coupons WHERE id = ?");
            $stmt->execute([$coupon_id]);
            return true;
        } catch (Exception $e) {
            error_log("Error deleting coupon: " . $e->getMessage());
            return false;
        }
    }
}

// Global coupon manager instance
$couponManager = new CouponManager($pdo);

/**
 * Standalone function for coupon validation (for backward compatibility)
 */
function validateCoupon($code, $user_id = null, $product_id = null, $amount = 0) {
    global $couponManager;
    return $couponManager->validateCoupon($code, $user_id, $product_id, $amount);
}

/**
 * Standalone function for discount calculation
 */
function calculateCouponDiscount($coupon, $amount) {
    global $couponManager;
    return $couponManager->calculateDiscount($coupon, $amount);
}
?>