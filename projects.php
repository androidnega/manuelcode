<?php 
include 'includes/db.php';
include 'includes/user_activity_tracker.php';
include 'includes/meta_helper.php';

// Set page-specific meta data
setQuickMeta(
    'Our Projects | Portfolio of Web & Mobile Development Work - ManuelCode',
    'Explore our portfolio of successful projects including web applications, mobile apps, e-commerce solutions, and custom software development. See how we turn ideas into reality.',
    'assets/favi/favicon.png',
    'portfolio, projects, web applications, mobile apps, e-commerce, custom software, development projects, case studies'
);

include 'includes/header.php';

// Fetch projects from database
try {
    $stmt = $pdo->query("SELECT * FROM projects WHERE status = 'active' ORDER BY featured DESC, created_at DESC");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $projects = [];
    error_log("Error fetching projects: " . $e->getMessage());
}

// Get unique categories for filtering
$categories = [];
foreach ($projects as $project) {
    if (!in_array($project['category'], $categories)) {
        $categories[] = $project['category'];
    }
}
?>

<!-- Hero Section -->
<section class="relative py-16 md:py-24 bg-gradient-to-br from-[#536895] to-[#2D3E50] overflow-hidden page-hero-section">
  <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <h1 class="text-3xl md:text-5xl font-bold text-white mb-4 leading-tight" style="font-family: 'Inter', sans-serif;">
      Our Projects
    </h1>
    <p class="text-lg md:text-xl text-white/90 max-w-2xl mx-auto leading-relaxed">
      Showcasing our best work and innovative solutions
    </p>
  </div>
</section>

<!-- Project Categories -->
<section class="py-16 bg-white">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
        Project <span class="text-[#536895]">Categories</span>
      </h2>
      <p class="text-lg text-gray-600 max-w-2xl mx-auto">
        Browse our projects by category to find solutions similar to your needs
      </p>
    </div>
    
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-16">
      <button class="category-filter active bg-[#536895] text-white px-6 py-3 rounded-lg font-medium transition-all duration-300 hover:bg-[#4a5a7a]" data-category="all">
        <i class="fas fa-th mr-2"></i>
        All Projects
      </button>
      <?php foreach ($categories as $category): ?>
        <button class="category-filter bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-medium transition-all duration-300 hover:bg-gray-200" data-category="<?php echo strtolower(str_replace(' ', '-', $category)); ?>">
          <i class="fas fa-folder mr-2"></i>
          <?php echo htmlspecialchars($category); ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Projects Grid -->
<section class="py-16 bg-gray-50">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <?php if (empty($projects)): ?>
      <!-- No Projects Message -->
      <div class="text-center py-16">
        <div class="mb-6">
          <i class="fas fa-folder-open text-6xl text-gray-300"></i>
        </div>
        <h3 class="text-2xl font-bold text-gray-700 mb-4">No Projects Available</h3>
        <p class="text-gray-600 max-w-md mx-auto">
          We're currently working on amazing projects. Check back soon to see our latest work!
        </p>
      </div>
    <?php else: ?>
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8" id="projects-grid">
        <?php foreach ($projects as $project): ?>
          <?php 
          // Parse technologies into array
          $technologies = !empty($project['technologies']) ? json_decode($project['technologies'], true) : [];
          if (!is_array($technologies)) {
              $technologies = !empty($project['technologies']) ? explode(',', $project['technologies']) : [];
          }
          
          // Generate category class for filtering
          $category_class = strtolower(str_replace(' ', '-', $project['category']));
          
          // Get project image or use default
          $image_url = !empty($project['image_url']) ? $project['image_url'] : '';
          $image_caption = !empty($project['image_caption']) ? $project['image_caption'] : $project['title'];
          
          // Generate icon based on category
          $icon_class = 'fas fa-code';
          switch (strtolower($project['category'])) {
              case 'web development':
                  $icon_class = 'fas fa-globe';
                  break;
              case 'mobile apps':
                  $icon_class = 'fas fa-mobile-alt';
                  break;
              case 'e-commerce':
                  $icon_class = 'fas fa-shopping-cart';
                  break;
              case 'software solution':
                  $icon_class = 'fas fa-cogs';
                  break;
              case 'integration':
                  $icon_class = 'fas fa-plug';
                  break;
              default:
                  $icon_class = 'fas fa-code';
          }
          ?>
          
          <div class="project-card group bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden" 
               data-category="<?php echo $category_class; ?>">
            <div class="relative overflow-hidden">
              <?php if ($image_url): ?>
                <img src="<?php echo htmlspecialchars($image_url); ?>" 
                     alt="<?php echo htmlspecialchars($image_caption); ?>" 
                     class="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300">
              <?php else: ?>
                <div class="h-40 bg-gradient-to-br from-[#536895] to-[#4a5a7a] flex items-center justify-center">
                  <i class="<?php echo $icon_class; ?> text-white text-4xl group-hover:scale-110 transition-transform duration-300"></i>
                </div>
              <?php endif; ?>
              
              <?php if ($project['featured']): ?>
                <div class="absolute top-4 right-4">
                  <span class="bg-[#F5A623] text-white px-3 py-1 rounded-full text-xs font-medium">Featured</span>
                </div>
              <?php endif; ?>
            </div>
            
            <div class="p-4">
              <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($project['title']); ?></h3>
                <div class="flex space-x-2">
                  <?php foreach (array_slice($technologies, 0, 2) as $tech): ?>
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs"><?php echo htmlspecialchars(trim($tech)); ?></span>
                  <?php endforeach; ?>
                  <?php if (count($technologies) > 2): ?>
                    <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs">+<?php echo count($technologies) - 2; ?> more</span>
                  <?php endif; ?>
                </div>
              </div>
              
              <p class="text-gray-600 mb-3 text-sm">
                <?php echo htmlspecialchars(substr($project['description'], 0, 120)) . (strlen($project['description']) > 120 ? '...' : ''); ?>
              </p>
              
              <div class="flex items-center justify-between">
                <div class="flex items-center text-sm text-gray-500">
                  <i class="fas fa-calendar mr-1"></i>
                  <span><?php echo date('Y', strtotime($project['created_at'])); ?></span>
                </div>
                <a href="project-detail.php?id=<?php echo $project['id']; ?>" 
                   class="text-[#536895] hover:text-[#4a5a7a] font-medium text-sm group-hover:underline">
                  View Details <i class="fas fa-arrow-right ml-1"></i>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>



<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryFilters = document.querySelectorAll('.category-filter');
    const projectCards = document.querySelectorAll('.project-card');
    
    categoryFilters.forEach(filter => {
        filter.addEventListener('click', function() {
            const selectedCategory = this.getAttribute('data-category');
            
            // Update active filter button
            categoryFilters.forEach(btn => {
                btn.classList.remove('active', 'bg-[#536895]', 'text-white');
                btn.classList.add('bg-gray-100', 'text-gray-700');
            });
            this.classList.add('active', 'bg-[#536895]', 'text-white');
            this.classList.remove('bg-gray-100', 'text-gray-700');
            
            
            // Filter projects
            projectCards.forEach(card => {
                if (selectedCategory === 'all' || card.getAttribute('data-category') === selectedCategory) {
                    card.style.display = 'block';
                    card.style.animation = 'fadeIn 0.5s ease-in-out';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});

// Add fade-in animation
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
`;
document.head.appendChild(style);
</script>

<?php include 'includes/footer.php'; ?>
