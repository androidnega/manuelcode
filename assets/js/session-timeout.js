// Session Timeout Management
// Handles automatic logout after 10 minutes of inactivity

class SessionTimeoutManager {
    constructor() {
        this.timeoutMinutes = 10;
        this.timeoutSeconds = this.timeoutMinutes * 60;
        this.warningMinutes = 2; // Show warning 2 minutes before timeout
        this.warningSeconds = this.warningMinutes * 60;
        this.inactivityTimer = null;
        this.warningTimer = null;
        this.isWarningShown = false;
        
        this.init();
    }
    
    init() {
        // Reset timers on user activity
        this.resetTimers();
        
        // Set up activity listeners
        this.setupActivityListeners();
        
        // Start the inactivity timer
        this.startInactivityTimer();
        
        // Check session status every 30 seconds
        setInterval(() => this.checkSessionStatus(), 30000);
    }
    
    setupActivityListeners() {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                this.resetTimers();
            }, true);
        });
        
        // Also reset on form interactions
        document.addEventListener('submit', () => this.resetTimers(), true);
        document.addEventListener('input', () => this.resetTimers(), true);
    }
    
    resetTimers() {
        // Clear existing timers
        if (this.inactivityTimer) {
            clearTimeout(this.inactivityTimer);
        }
        if (this.warningTimer) {
            clearTimeout(this.warningTimer);
        }
        
        // Hide warning if shown
        if (this.isWarningShown) {
            this.hideWarning();
        }
        
        // Start new timers
        this.startInactivityTimer();
    }
    
    startInactivityTimer() {
        // Set warning timer
        this.warningTimer = setTimeout(() => {
            this.showWarning();
        }, (this.timeoutSeconds - this.warningSeconds) * 1000);
        
        // Set logout timer
        this.inactivityTimer = setTimeout(() => {
            this.logout();
        }, this.timeoutSeconds * 1000);
    }
    
    showWarning() {
        this.isWarningShown = true;
        
        // Create warning modal
        const warningModal = document.createElement('div');
        warningModal.id = 'session-warning-modal';
        warningModal.innerHTML = `
            <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
                <div class="bg-white rounded-lg p-6 max-w-md mx-4">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-500 text-2xl"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-medium text-gray-900">Session Timeout Warning</h3>
                        </div>
                    </div>
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">
                            Your session will expire in <span id="countdown-timer">${this.warningMinutes}:00</span> due to inactivity.
                        </p>
                        <p class="text-sm text-gray-600 mt-2">
                            Click "Stay Logged In" to continue your session.
                        </p>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button id="stay-logged-in" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Stay Logged In
                        </button>
                        <button id="logout-now" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                            Logout Now
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(warningModal);
        
        // Add event listeners
        document.getElementById('stay-logged-in').addEventListener('click', () => {
            this.resetTimers();
            this.hideWarning();
        });
        
        document.getElementById('logout-now').addEventListener('click', () => {
            this.logout();
        });
        
        // Start countdown
        this.startCountdown();
    }
    
    hideWarning() {
        this.isWarningShown = false;
        const modal = document.getElementById('session-warning-modal');
        if (modal) {
            modal.remove();
        }
    }
    
    startCountdown() {
        let timeLeft = this.warningSeconds;
        
        const countdownInterval = setInterval(() => {
            timeLeft--;
            
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            const timeString = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            const countdownElement = document.getElementById('countdown-timer');
            if (countdownElement) {
                countdownElement.textContent = timeString;
            }
            
            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                this.logout();
            }
        }, 1000);
    }
    
    async checkSessionStatus() {
        try {
            // Determine the correct path for session check
            let checkUrl = 'check_session.php';
            
            if (window.location.pathname.includes('/admin/')) {
                checkUrl = 'check_session.php';
            } else if (window.location.pathname.includes('/dashboard/')) {
                checkUrl = 'check_session.php';
            }
            
            const response = await fetch(checkUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'check_session' })
            });
            
            const data = await response.json();
            
            if (!data.valid) {
                this.logout();
            }
        } catch (error) {
            console.error('Error checking session status:', error);
        }
    }
    
    logout() {
        // Clear all timers
        if (this.inactivityTimer) {
            clearTimeout(this.inactivityTimer);
        }
        if (this.warningTimer) {
            clearTimeout(this.warningTimer);
        }
        
        // Hide warning if shown
        if (this.isWarningShown) {
            this.hideWarning();
        }
        
        // Determine logout URL based on current page - use absolute paths
        let logoutUrl = '/auth/logout.php';
        
        if (window.location.pathname.includes('/admin/')) {
            logoutUrl = '/admin/auth/logout.php';
        }
        
        // Redirect to logout
        window.location.href = logoutUrl + '?timeout=1';
    }
}

// Initialize session timeout manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if user is logged in
    if (document.body.classList.contains('logged-in') || 
        document.body.classList.contains('admin-logged-in') || 
        document.body.classList.contains('superadmin-logged-in') ||
        document.body.classList.contains('support-logged-in')) {
        new SessionTimeoutManager();
    }
});

// Also initialize immediately if DOM is already loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        if (document.body.classList.contains('logged-in') || 
            document.body.classList.contains('admin-logged-in') || 
            document.body.classList.contains('superadmin-logged-in') ||
            document.body.classList.contains('support-logged-in')) {
            new SessionTimeoutManager();
        }
    });
} else {
    if (document.body.classList.contains('logged-in') || 
        document.body.classList.contains('admin-logged-in') || 
        document.body.classList.contains('superadmin-logged-in') ||
        document.body.classList.contains('support-logged-in')) {
        new SessionTimeoutManager();
    }
}
