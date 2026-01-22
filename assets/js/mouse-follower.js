/**
 * Mouse Follower - Black Circle that follows the cursor
 */

(function() {
    'use strict';
    
    // Wait for DOM to be ready
    function initMouseFollower() {
        // Check if body exists
        if (!document.body) {
            requestAnimationFrame(initMouseFollower);
            return;
        }
        
        // Create the cursor element
        const cursor = document.createElement('div');
        cursor.id = 'mouse-follower';
        document.body.appendChild(cursor);
        
        let mouseX = window.innerWidth / 2;
        let mouseY = window.innerHeight / 2;
        let cursorX = mouseX;
        let cursorY = mouseY;
        
        // Initialize cursor position
        cursor.style.left = cursorX + 'px';
        cursor.style.top = cursorY + 'px';
        cursor.style.opacity = '1';
        
        // Update mouse position
        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
        });
        
        // Smooth animation loop
        function animateCursor() {
            // Calculate the difference between current position and target
            const dx = mouseX - cursorX;
            const dy = mouseY - cursorY;
            
            // Smooth interpolation (adjust speed by changing 0.1 - lower = slower, higher = faster)
            cursorX += dx * 0.1;
            cursorY += dy * 0.1;
            
            // Update cursor position
            cursor.style.left = cursorX + 'px';
            cursor.style.top = cursorY + 'px';
            
            // Continue animation
            requestAnimationFrame(animateCursor);
        }
        
        // Hide cursor when mouse leaves the window
        document.addEventListener('mouseleave', () => {
            cursor.style.opacity = '0';
        });
        
        // Show cursor when mouse enters the window
        document.addEventListener('mouseenter', () => {
            cursor.style.opacity = '1';
        });
        
        // Start animation
        animateCursor();
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMouseFollower);
    } else {
        initMouseFollower();
    }
})();

