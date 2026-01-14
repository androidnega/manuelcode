// Analytics Time Tracking
// This script tracks time spent on pages and sends data to the server

(function() {
    'use strict';
    
    let startTime = Date.now();
    let pageUrl = window.location.pathname;
    let sessionId = getSessionId();
    
    // Get session ID from cookies or generate one
    function getSessionId() {
        let sessionId = getCookie('PHPSESSID');
        if (!sessionId) {
            sessionId = 'guest_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            setCookie('PHPSESSID', sessionId, 1); // 1 day expiry
        }
        return sessionId;
    }
    
    // Cookie helper functions
    function setCookie(name, value, days) {
        let expires = '';
        if (days) {
            let date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + value + expires + '; path=/';
    }
    
    function getCookie(name) {
        let nameEQ = name + '=';
        let ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
    
    // Send time spent data to server
    function sendTimeSpent() {
        let timeSpent = Math.floor((Date.now() - startTime) / 1000); // Convert to seconds
        
        // Only send if user spent more than 5 seconds on the page
        if (timeSpent < 5) return;
        
        // Send data via AJAX
        fetch('includes/update_time_spent.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                session_id: sessionId,
                page_url: pageUrl,
                time_spent: timeSpent
            })
        })
        .catch(error => {
            console.log('Analytics tracking error:', error);
        });
    }
    
    // Track when user leaves the page
    function handlePageUnload() {
        sendTimeSpent();
    }
    
    // Track when user navigates away
    function handlePageHide() {
        sendTimeSpent();
    }
    
    // Track when user comes back to the page
    function handlePageShow() {
        startTime = Date.now();
    }
    
    // Track when user navigates to a new page (SPA navigation)
    function handleNavigation() {
        sendTimeSpent();
        startTime = Date.now();
        pageUrl = window.location.pathname;
    }
    
    // Set up event listeners
    window.addEventListener('beforeunload', handlePageUnload);
    window.addEventListener('pagehide', handlePageHide);
    window.addEventListener('pageshow', handlePageShow);
    
    // Track navigation in SPAs (if using history API)
    let originalPushState = history.pushState;
    let originalReplaceState = history.replaceState;
    
    history.pushState = function() {
        handleNavigation();
        return originalPushState.apply(this, arguments);
    };
    
    history.replaceState = function() {
        handleNavigation();
        return originalReplaceState.apply(this, arguments);
    };
    
    // Track clicks on internal links
    document.addEventListener('click', function(e) {
        let target = e.target.closest('a');
        if (target && target.href && target.href.startsWith(window.location.origin)) {
            // Small delay to ensure the data is sent before navigation
            setTimeout(sendTimeSpent, 100);
        }
    });
    
    // Send periodic updates (every 30 seconds) for long sessions
    setInterval(function() {
        let timeSpent = Math.floor((Date.now() - startTime) / 1000);
        if (timeSpent >= 30) {
            sendTimeSpent();
            startTime = Date.now(); // Reset timer
        }
    }, 30000);
    
    // Send data when page becomes visible again (user returns to tab)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            sendTimeSpent();
        } else {
            startTime = Date.now();
        }
    });
    
})();
