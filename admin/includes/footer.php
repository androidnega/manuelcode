    </main>
  </div>
</div>

<script>
  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-overlay');
    
    if (sidebar.classList.contains('-translate-x-full')) {
      sidebar.classList.remove('-translate-x-full');
      overlay.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    } else {
      sidebar.classList.add('-translate-x-full');
      overlay.classList.add('hidden');
      document.body.style.overflow = '';
    }
  }

  function toggleDarkMode() {
    fetch('toggle_dark_mode.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        action: 'toggle'
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        location.reload();
      }
    })
    .catch(error => console.error('Error:', error));
  }

  document.addEventListener('DOMContentLoaded', function() {
    const sidebarLinks = document.querySelectorAll('#sidebar a');
    sidebarLinks.forEach(link => {
      link.addEventListener('click', function() {
        if (window.innerWidth < 1024) {
          toggleSidebar();
        }
      });
    });

    window.addEventListener('resize', function() {
      if (window.innerWidth >= 1024) {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-overlay');
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
      }
    });
  });
</script>
</body>
</html>
