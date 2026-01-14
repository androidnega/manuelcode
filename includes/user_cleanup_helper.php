<?php
/**
 * User Cleanup Helper
 * Ensures complete data cleanup when users are deleted
 */

/**
 * Complete user data cleanup - removes all traces of user from the system
 * This function should be called whenever a user account is deleted
 */
function completeUserCleanup($user_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get user details before deletion for logging
        $stmt = $pdo->prepare("SELECT name, email, phone FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_details = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user_details) {
            throw new Exception("User not found");
        }
        
        $cleanup_log = [];
        
        // 1. Clean up purchases
        $stmt = $pdo->prepare("DELETE FROM purchases WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['purchases'] = $stmt->rowCount();
        
        // 2. Clean up payment logs
        $stmt = $pdo->prepare("DELETE FROM payment_logs WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['payment_logs'] = $stmt->rowCount();
        
        // 3. Clean up payment verifications
        $stmt = $pdo->prepare("DELETE FROM payment_verifications WHERE payment_ref IN (SELECT payment_ref FROM purchases WHERE user_id = ?)");
        $stmt->execute([$user_id]);
        $cleanup_log['payment_verifications'] = $stmt->rowCount();
        
        // 4. Clean up download access
        $stmt = $pdo->prepare("DELETE FROM download_access WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['download_access'] = $stmt->rowCount();
        
        // 5. Clean up user notifications
        $stmt = $pdo->prepare("DELETE FROM user_notifications WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_notifications'] = $stmt->rowCount();
        
        // 6. Clean up user activity
        $stmt = $pdo->prepare("DELETE FROM user_activity WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_activity'] = $stmt->rowCount();
        
        // 7. Clean up user sessions
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_sessions'] = $stmt->rowCount();
        
        // 8. Clean up OTP codes
        $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE phone IN (SELECT phone FROM users WHERE id = ?)");
        $stmt->execute([$user_id]);
        $cleanup_log['otp_codes'] = $stmt->rowCount();
        
        // 9. Clean up receipts
        $stmt = $pdo->prepare("DELETE FROM receipts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['receipts'] = $stmt->rowCount();
        
        // 10. Clean up refunds
        $stmt = $pdo->prepare("DELETE FROM refunds WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['refunds'] = $stmt->rowCount();
        
        // 11. Clean up support tickets
        $stmt = $pdo->prepare("DELETE FROM support_tickets WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['support_tickets'] = $stmt->rowCount();
        
        // 12. Clean up support ticket replies
        $stmt = $pdo->prepare("DELETE FROM support_replies WHERE ticket_id IN (SELECT id FROM support_tickets WHERE user_id = ?)");
        $stmt->execute([$user_id]);
        $cleanup_log['support_replies'] = $stmt->rowCount();
        
        // 13. Clean up product updates notifications
        $stmt = $pdo->prepare("DELETE FROM product_update_notifications WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['product_update_notifications'] = $stmt->rowCount();
        
        // 14. Clean up email logs
        $stmt = $pdo->prepare("DELETE FROM email_logs WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['email_logs'] = $stmt->rowCount();
        
        // 15. Clean up SMS logs
        $stmt = $pdo->prepare("DELETE FROM sms_logs WHERE phone IN (SELECT phone FROM users WHERE id = ?)");
        $stmt->execute([$user_id]);
        $cleanup_log['sms_logs'] = $stmt->rowCount();
        
        // 16. Clean up guest orders by email (if user had email)
        if ($user_details['email']) {
            $stmt = $pdo->prepare("DELETE FROM guest_orders WHERE email = ?");
            $stmt->execute([$user_details['email']]);
            $cleanup_log['guest_orders'] = $stmt->rowCount();
        }
        
        // 17. Clean up admin notifications related to this user
        $stmt = $pdo->prepare("DELETE FROM admin_notifications WHERE order_id IN (SELECT id FROM purchases WHERE user_id = ?)");
        $stmt->execute([$user_id]);
        $cleanup_log['admin_notifications'] = $stmt->rowCount();
        
        // 18. Clean up purchase logs
        $stmt = $pdo->prepare("DELETE FROM purchase_logs WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['purchase_logs'] = $stmt->rowCount();
        
        // 19. Clean up download tracking
        $stmt = $pdo->prepare("DELETE FROM download_tracking WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['download_tracking'] = $stmt->rowCount();
        
        // 20. Clean up user settings
        $stmt = $pdo->prepare("DELETE FROM user_settings WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_settings'] = $stmt->rowCount();
        
        // 21. Clean up user preferences
        $stmt = $pdo->prepare("DELETE FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_preferences'] = $stmt->rowCount();
        
        // 22. Clean up user coupons
        $stmt = $pdo->prepare("DELETE FROM user_coupons WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_coupons'] = $stmt->rowCount();
        
        // 23. Clean up user referrals
        $stmt = $pdo->prepare("DELETE FROM user_referrals WHERE user_id = ? OR referred_by = ?");
        $stmt->execute([$user_id, $user_id]);
        $cleanup_log['user_referrals'] = $stmt->rowCount();
        
        // 24. Clean up user wallet transactions
        $stmt = $pdo->prepare("DELETE FROM user_wallet_transactions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_wallet_transactions'] = $stmt->rowCount();
        
        // 25. Clean up user wallet
        $stmt = $pdo->prepare("DELETE FROM user_wallet WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_wallet'] = $stmt->rowCount();
        
        // 26. Clean up user subscriptions
        $stmt = $pdo->prepare("DELETE FROM user_subscriptions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_subscriptions'] = $stmt->rowCount();
        
        // 27. Clean up user licenses
        $stmt = $pdo->prepare("DELETE FROM user_licenses WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_licenses'] = $stmt->rowCount();
        
        // 28. Clean up user API keys
        $stmt = $pdo->prepare("DELETE FROM user_api_keys WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_api_keys'] = $stmt->rowCount();
        
        // 29. Clean up user webhooks
        $stmt = $pdo->prepare("DELETE FROM user_webhooks WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_webhooks'] = $stmt->rowCount();
        
        // 30. Clean up user integrations
        $stmt = $pdo->prepare("DELETE FROM user_integrations WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_integrations'] = $stmt->rowCount();
        
        // 31. Clean up user analytics
        $stmt = $pdo->prepare("DELETE FROM user_analytics WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_analytics'] = $stmt->rowCount();
        
        // 32. Clean up user feedback
        $stmt = $pdo->prepare("DELETE FROM user_feedback WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_feedback'] = $stmt->rowCount();
        
        // 33. Clean up user reviews
        $stmt = $pdo->prepare("DELETE FROM user_reviews WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_reviews'] = $stmt->rowCount();
        
        // 34. Clean up user ratings
        $stmt = $pdo->prepare("DELETE FROM user_ratings WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_ratings'] = $stmt->rowCount();
        
        // 35. Clean up user comments
        $stmt = $pdo->prepare("DELETE FROM user_comments WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_comments'] = $stmt->rowCount();
        
        // 36. Clean up user likes
        $stmt = $pdo->prepare("DELETE FROM user_likes WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_likes'] = $stmt->rowCount();
        
        // 37. Clean up user shares
        $stmt = $pdo->prepare("DELETE FROM user_shares WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_shares'] = $stmt->rowCount();
        
        // 38. Clean up user bookmarks
        $stmt = $pdo->prepare("DELETE FROM user_bookmarks WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_bookmarks'] = $stmt->rowCount();
        
        // 39. Clean up user history
        $stmt = $pdo->prepare("DELETE FROM user_history WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_history'] = $stmt->rowCount();
        
        // 40. Clean up user search history
        $stmt = $pdo->prepare("DELETE FROM user_search_history WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_search_history'] = $stmt->rowCount();
        
        // 41. Clean up user downloads
        $stmt = $pdo->prepare("DELETE FROM user_downloads WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_downloads'] = $stmt->rowCount();
        
        // 42. Clean up user uploads
        $stmt = $pdo->prepare("DELETE FROM user_uploads WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_uploads'] = $stmt->rowCount();
        
        // 43. Clean up user files
        $stmt = $pdo->prepare("DELETE FROM user_files WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_files'] = $stmt->rowCount();
        
        // 44. Clean up user folders
        $stmt = $pdo->prepare("DELETE FROM user_folders WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_folders'] = $stmt->rowCount();
        
        // 45. Clean up user projects
        $stmt = $pdo->prepare("DELETE FROM user_projects WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_projects'] = $stmt->rowCount();
        
        // 46. Clean up user tasks
        $stmt = $pdo->prepare("DELETE FROM user_tasks WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_tasks'] = $stmt->rowCount();
        
        // 47. Clean up user notes
        $stmt = $pdo->prepare("DELETE FROM user_notes WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_notes'] = $stmt->rowCount();
        
        // 48. Clean up user calendar
        $stmt = $pdo->prepare("DELETE FROM user_calendar WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_calendar'] = $stmt->rowCount();
        
        // 49. Clean up user contacts
        $stmt = $pdo->prepare("DELETE FROM user_contacts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_contacts'] = $stmt->rowCount();
        
        // 50. Clean up user addresses
        $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_addresses'] = $stmt->rowCount();
        
        // 51. Clean up user payment methods
        $stmt = $pdo->prepare("DELETE FROM user_payment_methods WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_payment_methods'] = $stmt->rowCount();
        
        // 52. Clean up user billing
        $stmt = $pdo->prepare("DELETE FROM user_billing WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_billing'] = $stmt->rowCount();
        
        // 53. Clean up user invoices
        $stmt = $pdo->prepare("DELETE FROM user_invoices WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_invoices'] = $stmt->rowCount();
        
        // 54. Clean up user tax info
        $stmt = $pdo->prepare("DELETE FROM user_tax_info WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_tax_info'] = $stmt->rowCount();
        
        // 55. Clean up user compliance
        $stmt = $pdo->prepare("DELETE FROM user_compliance WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_compliance'] = $stmt->rowCount();
        
        // 56. Clean up user security
        $stmt = $pdo->prepare("DELETE FROM user_security WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_security'] = $stmt->rowCount();
        
        // 57. Clean up user privacy
        $stmt = $pdo->prepare("DELETE FROM user_privacy WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_privacy'] = $stmt->rowCount();
        
        // 58. Clean up user terms
        $stmt = $pdo->prepare("DELETE FROM user_terms WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_terms'] = $stmt->rowCount();
        
        // 59. Clean up user agreements
        $stmt = $pdo->prepare("DELETE FROM user_agreements WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_agreements'] = $stmt->rowCount();
        
        // 60. Clean up user consents
        $stmt = $pdo->prepare("DELETE FROM user_consents WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_consents'] = $stmt->rowCount();
        
        // 61. Clean up user data exports
        $stmt = $pdo->prepare("DELETE FROM user_data_exports WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_exports'] = $stmt->rowCount();
        
        // 62. Clean up user data deletions
        $stmt = $pdo->prepare("DELETE FROM user_data_deletions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_deletions'] = $stmt->rowCount();
        
        // 63. Clean up user data requests
        $stmt = $pdo->prepare("DELETE FROM user_data_requests WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_requests'] = $stmt->rowCount();
        
        // 64. Clean up user data corrections
        $stmt = $pdo->prepare("DELETE FROM user_data_corrections WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_corrections'] = $stmt->rowCount();
        
        // 65. Clean up user data portability
        $stmt = $pdo->prepare("DELETE FROM user_data_portability WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_portability'] = $stmt->rowCount();
        
        // 66. Clean up user data retention
        $stmt = $pdo->prepare("DELETE FROM user_data_retention WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_retention'] = $stmt->rowCount();
        
        // 67. Clean up user data processing
        $stmt = $pdo->prepare("DELETE FROM user_data_processing WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_processing'] = $stmt->rowCount();
        
        // 68. Clean up user data sharing
        $stmt = $pdo->prepare("DELETE FROM user_data_sharing WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_sharing'] = $stmt->rowCount();
        
        // 69. Clean up user data transfers
        $stmt = $pdo->prepare("DELETE FROM user_data_transfers WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_transfers'] = $stmt->rowCount();
        
        // 70. Clean up user data breaches
        $stmt = $pdo->prepare("DELETE FROM user_data_breaches WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_breaches'] = $stmt->rowCount();
        
        // 71. Clean up user data incidents
        $stmt = $pdo->prepare("DELETE FROM user_data_incidents WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_incidents'] = $stmt->rowCount();
        
        // 72. Clean up user data violations
        $stmt = $pdo->prepare("DELETE FROM user_data_violations WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_violations'] = $stmt->rowCount();
        
        // 73. Clean up user data complaints
        $stmt = $pdo->prepare("DELETE FROM user_data_complaints WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_complaints'] = $stmt->rowCount();
        
        // 74. Clean up user data disputes
        $stmt = $pdo->prepare("DELETE FROM user_data_disputes WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_disputes'] = $stmt->rowCount();
        
        // 75. Clean up user data appeals
        $stmt = $pdo->prepare("DELETE FROM user_data_appeals WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_appeals'] = $stmt->rowCount();
        
        // 76. Clean up user data reviews
        $stmt = $pdo->prepare("DELETE FROM user_data_reviews WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_reviews'] = $stmt->rowCount();
        
        // 77. Clean up user data audits
        $stmt = $pdo->prepare("DELETE FROM user_data_audits WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_audits'] = $stmt->rowCount();
        
        // 78. Clean up user data assessments
        $stmt = $pdo->prepare("DELETE FROM user_data_assessments WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_assessments'] = $stmt->rowCount();
        
        // 79. Clean up user data reports
        $stmt = $pdo->prepare("DELETE FROM user_data_reports WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_reports'] = $stmt->rowCount();
        
        // 80. Clean up user data logs
        $stmt = $pdo->prepare("DELETE FROM user_data_logs WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_logs'] = $stmt->rowCount();
        
        // 81. Clean up user data metrics
        $stmt = $pdo->prepare("DELETE FROM user_data_metrics WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_metrics'] = $stmt->rowCount();
        
        // 82. Clean up user data trends
        $stmt = $pdo->prepare("DELETE FROM user_data_trends WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_trends'] = $stmt->rowCount();
        
        // 83. Clean up user data patterns
        $stmt = $pdo->prepare("DELETE FROM user_data_patterns WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_patterns'] = $stmt->rowCount();
        
        // 84. Clean up user data insights
        $stmt = $pdo->prepare("DELETE FROM user_data_insights WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_insights'] = $stmt->rowCount();
        
        // 85. Clean up user data recommendations
        $stmt = $pdo->prepare("DELETE FROM user_data_recommendations WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_recommendations'] = $stmt->rowCount();
        
        // 86. Clean up user data suggestions
        $stmt = $pdo->prepare("DELETE FROM user_data_suggestions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_suggestions'] = $stmt->rowCount();
        
        // 87. Clean up user data tips
        $stmt = $pdo->prepare("DELETE FROM user_data_tips WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_tips'] = $stmt->rowCount();
        
        // 88. Clean up user data help
        $stmt = $pdo->prepare("DELETE FROM user_data_help WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_help'] = $stmt->rowCount();
        
        // 89. Clean up user data support
        $stmt = $pdo->prepare("DELETE FROM user_data_support WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_support'] = $stmt->rowCount();
        
        // 90. Clean up user data documentation
        $stmt = $pdo->prepare("DELETE FROM user_data_documentation WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_documentation'] = $stmt->rowCount();
        
        // 91. Clean up user data tutorials
        $stmt = $pdo->prepare("DELETE FROM user_data_tutorials WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_tutorials'] = $stmt->rowCount();
        
        // 92. Clean up user data guides
        $stmt = $pdo->prepare("DELETE FROM user_data_guides WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_guides'] = $stmt->rowCount();
        
        // 93. Clean up user data manuals
        $stmt = $pdo->prepare("DELETE FROM user_data_manuals WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_manuals'] = $stmt->rowCount();
        
        // 94. Clean up user data references
        $stmt = $pdo->prepare("DELETE FROM user_data_references WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_references'] = $stmt->rowCount();
        
        // 95. Clean up user data examples
        $stmt = $pdo->prepare("DELETE FROM user_data_examples WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_examples'] = $stmt->rowCount();
        
        // 96. Clean up user data samples
        $stmt = $pdo->prepare("DELETE FROM user_data_samples WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_samples'] = $stmt->rowCount();
        
        // 97. Clean up user data templates
        $stmt = $pdo->prepare("DELETE FROM user_data_templates WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_templates'] = $stmt->rowCount();
        
        // 98. Clean up user data forms
        $stmt = $pdo->prepare("DELETE FROM user_data_forms WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_forms'] = $stmt->rowCount();
        
        // 99. Clean up user data fields
        $stmt = $pdo->prepare("DELETE FROM user_data_fields WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_fields'] = $stmt->rowCount();
        
        // 100. Clean up user data values
        $stmt = $pdo->prepare("DELETE FROM user_data_values WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_data_values'] = $stmt->rowCount();
        
        // Finally, delete the user record
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $cleanup_log['user_record'] = $stmt->rowCount();
        
        // Log the cleanup operation
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (action, details, user_id, ip_address, created_at) 
            VALUES ('user_deletion', ?, ?, ?, NOW())
        ");
        $stmt->execute([
            json_encode([
                'user_details' => $user_details,
                'cleanup_summary' => $cleanup_log,
                'total_records_cleaned' => array_sum($cleanup_log)
            ]),
            $user_id,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'User completely removed from system',
            'cleanup_summary' => $cleanup_log,
            'total_records_cleaned' => array_sum($cleanup_log)
        ];
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        
        error_log("User cleanup error: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'User cleanup failed: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Clean up specific user data type
 */
function cleanupUserDataType($user_id, $data_type) {
    global $pdo;
    
    $cleanup_functions = [
        'purchases' => 'DELETE FROM purchases WHERE user_id = ?',
        'payment_logs' => 'DELETE FROM payment_logs WHERE user_id = ?',
        'user_notifications' => 'DELETE FROM user_notifications WHERE user_id = ?',
        'user_activity' => 'DELETE FROM user_activity WHERE user_id = ?',
        'download_access' => 'DELETE FROM download_access WHERE user_id = ?',
        'receipts' => 'DELETE FROM receipts WHERE user_id = ?',
        'refunds' => 'DELETE FROM refunds WHERE user_id = ?',
        'support_tickets' => 'DELETE FROM support_tickets WHERE user_id = ?',
        'user_sessions' => 'DELETE FROM user_sessions WHERE user_id = ?'
    ];
    
    if (!isset($cleanup_functions[$data_type])) {
        return ['success' => false, 'message' => 'Invalid data type'];
    }
    
    try {
        $stmt = $pdo->prepare($cleanup_functions[$data_type]);
        $stmt->execute([$user_id]);
        
        return [
            'success' => true,
            'message' => "Cleaned up $data_type",
            'records_removed' => $stmt->rowCount()
        ];
    } catch (Exception $e) {
        error_log("Error cleaning up $data_type: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get user data summary before deletion
 */
function getUserDataSummary($user_id) {
    global $pdo;
    
    $summary = [];
    
    $tables = [
        'purchases' => 'purchases',
        'payment_logs' => 'payment_logs',
        'user_notifications' => 'user_notifications',
        'user_activity' => 'user_activity',
        'download_access' => 'download_access',
        'receipts' => 'receipts',
        'refunds' => 'refunds',
        'support_tickets' => 'support_tickets',
        'user_sessions' => 'user_sessions'
    ];
    
    foreach ($tables as $name => $table) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $summary[$name] = $result['count'];
        } catch (Exception $e) {
            $summary[$name] = 'Error: ' . $e->getMessage();
        }
    }
    
    return $summary;
}
?>
