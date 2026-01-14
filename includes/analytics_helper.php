<?php
/**
 * Analytics Helper Functions
 * Standardized calculations for consistent revenue reporting across admin pages
 */

/**
 * Get total revenue from all paid orders (users + guests)
 * This ensures consistency across dashboard, orders, and reports pages
 */
function getTotalRevenue($pdo) {
    try {
        // User purchases revenue (actual amount paid, not reduced by discount)
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(
                CASE WHEN p.status = 'paid' 
                THEN GREATEST(0, COALESCE(p.amount, pr.price))
                ELSE 0 
                END
            ), 0) as user_revenue
            FROM purchases p 
            JOIN products pr ON p.product_id = pr.id
        ");
        $user_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['user_revenue'];

        // Guest orders revenue (actual amount paid, not reduced by discount)
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(
                CASE WHEN go.status = 'paid' 
                THEN GREATEST(0, go.total_amount)
                ELSE 0 
                END
            ), 0) as guest_revenue
            FROM guest_orders go
        ");
        $guest_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['guest_revenue'];

        return $user_revenue + $guest_revenue;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get total orders count from all paid orders (users + guests)
 */
function getTotalOrders($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM purchases WHERE status = 'paid') +
                (SELECT COUNT(*) FROM guest_orders WHERE status = 'paid') as total_orders
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get user orders count
 */
function getUserOrdersCount($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM purchases WHERE status = 'paid'");
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get guest orders count
 */
function getGuestOrdersCount($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM guest_orders WHERE status = 'paid'");
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get total products count
 */
function getTotalProducts($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get total discounts applied across all orders
 */
function getTotalDiscounts($pdo) {
    try {
        // User purchases discounts
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(CASE WHEN p.status = 'paid' THEN COALESCE(p.discount_amount, 0) ELSE 0 END), 0) as user_discounts
            FROM purchases p
        ");
        $user_discounts = $stmt->fetch(PDO::FETCH_ASSOC)['user_discounts'];

        // Guest orders discounts
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(CASE WHEN go.status = 'paid' THEN COALESCE(go.discount_amount, 0) ELSE 0 END), 0) as guest_discounts
            FROM guest_orders go
        ");
        $guest_discounts = $stmt->fetch(PDO::FETCH_ASSOC)['guest_discounts'];

        return $user_discounts + $guest_discounts;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get total orders with discounts
 */
function getOrdersWithDiscounts($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM purchases WHERE status = 'paid' AND discount_amount > 0) +
                (SELECT COUNT(*) FROM guest_orders WHERE status = 'paid' AND discount_amount > 0) as orders_with_discounts
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC)['orders_with_discounts'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}
?>
