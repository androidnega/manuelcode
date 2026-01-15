// Payment functionality for Paystack integration
function initializePayment(productId, isGuest = false, guestData = null) {
    const button = document.getElementById('paystack-button');
    if (!button) return;
    
    // Store original button HTML to restore on error
    const originalButtonHTML = button.innerHTML;
    
    button.addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
        
        const paymentStatus = document.getElementById('payment-status');
        const paymentLoading = document.getElementById('payment-loading');
        const paymentError = document.getElementById('payment-error');
        
        if (paymentStatus) paymentStatus.classList.remove('hidden');
        if (paymentLoading) paymentLoading.classList.remove('hidden');
        if (paymentError) paymentError.classList.add('hidden');
        
        // Check if payment is properly configured
        console.log('Initiating payment for product ID:', productId);
        console.log('User type:', isGuest ? 'Guest' : 'Registered');
        
        // Prepare payment data
        const paymentData = {
            product_id: productId,
            is_guest: isGuest
        };
        
        if (isGuest && guestData) {
            paymentData.guest_data = guestData;
        }
        
        // Add coupon data if available
        const couponData = button.getAttribute('data-coupon');
        if (couponData) {
            try {
                paymentData.coupon_data = JSON.parse(couponData);
                console.log('Coupon data parsed:', paymentData.coupon_data);
            } catch (e) {
                console.error('Error parsing coupon data:', e);
            }
        } else {
            // Also check sessionStorage as fallback
            const sessionCoupon = sessionStorage.getItem('applied_coupon');
            if (sessionCoupon) {
                try {
                    paymentData.coupon_data = JSON.parse(sessionCoupon);
                    console.log('Coupon data from sessionStorage:', paymentData.coupon_data);
                } catch (e) {
                    console.error('Error parsing sessionStorage coupon data:', e);
                }
            }
        }
        
        // Initialize payment - use absolute path to avoid path issues
        let apiPath = 'payment/process_payment_api.php';
        if (window.location.pathname.includes('/payment/')) {
            apiPath = 'process_payment_api.php';
        } else if (window.location.pathname.includes('/admin/') || window.location.pathname.includes('/dashboard/')) {
            apiPath = '../payment/process_payment_api.php';
        }
        
        console.log('API Path:', apiPath);
        console.log('Payment Data:', paymentData);
        
        fetch(apiPath, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(paymentData)
        })
        .then(async response => {
            // Get response text first to handle both JSON and non-JSON responses
            const responseText = await response.text();
            console.log('Response Status:', response.status);
            console.log('Response Text:', responseText);
            
            if (!response.ok) {
                // Try to parse as JSON for error message
                let errorMessage = 'Network response was not ok';
                try {
                    const errorData = JSON.parse(responseText);
                    errorMessage = errorData.message || errorData.error || errorMessage;
                } catch (e) {
                    errorMessage = `Server error (${response.status}): ${response.statusText}`;
                }
                throw new Error(errorMessage);
            }
            
            // Parse JSON response
            try {
                return JSON.parse(responseText);
            } catch (e) {
                throw new Error('Invalid response from server');
            }
        })
        .then(data => {
            console.log('Payment API Response:', data);
            if (data.success) {
                // Redirect to payment gateway
                window.location.href = data.authorization_url;
            } else {
                throw new Error(data.message || 'Payment initialization failed');
            }
        })
        .catch(error => {
            console.error('Payment error:', error);
            
            if (paymentLoading) paymentLoading.classList.add('hidden');
            if (paymentError) {
                paymentError.classList.remove('hidden');
                const errorMessage = document.getElementById('error-message');
                if (errorMessage) {
                    errorMessage.textContent = error.message || 'An error occurred. Please try again.';
                }
            }
            
            // Re-enable button with original text
            if (button) {
                button.disabled = false;
                button.innerHTML = originalButtonHTML;
            }
        });
    });
}

// Initialize payment when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on a payment page
    const paystackButton = document.getElementById('paystack-button');
    if (paystackButton) {
        // Get payment data from data attributes
        const productId = paystackButton.getAttribute('data-product-id');
        const isGuest = paystackButton.getAttribute('data-is-guest') === 'true';
        const guestData = paystackButton.getAttribute('data-guest-data');
        
        if (productId) {
            const guestDataObj = guestData ? JSON.parse(guestData) : null;
            initializePayment(parseInt(productId), isGuest, guestDataObj);
        }
    }
});
