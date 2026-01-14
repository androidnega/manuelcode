<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="relative py-20 bg-gradient-to-br from-[#536895] via-[#4a5a7a] to-[#2D3E50] overflow-hidden">
  <div class="absolute inset-0 bg-black bg-opacity-20"></div>
  <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-2 gap-12 items-center">
      <div>
        <div class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-full text-sm font-medium mb-6">
          <i class="fas fa-shopping-bag mr-2"></i>
          Featured Project
        </div>
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-6 leading-tight">
          E-commerce
          <span class="block text-[#F5A623]">Platform</span>
        </h1>
        <p class="text-xl text-white/90 mb-8 leading-relaxed">
          A modern, full-featured e-commerce platform with advanced payment integration, inventory management, and comprehensive analytics dashboard.
        </p>
        <div class="flex flex-wrap gap-4 mb-8">
          <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">React</span>
          <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">Node.js</span>
          <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-medium">MongoDB</span>
          <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-sm font-medium">Stripe</span>
        </div>
        <div class="flex flex-col sm:flex-row gap-4">
          <a href="#" class="bg-[#F5A623] hover:bg-[#d88c1b] text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg">
            <i class="fas fa-external-link-alt mr-2"></i>
            Live Demo
          </a>
          <a href="#" class="bg-transparent border-2 border-white text-white hover:bg-white hover:text-[#536895] px-8 py-3 rounded-lg font-semibold transition-all duration-300">
            <i class="fab fa-github mr-2"></i>
            View Code
          </a>
        </div>
      </div>
      <div class="relative">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-8 shadow-2xl">
          <i class="fas fa-shopping-bag text-white text-8xl mx-auto block text-center"></i>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Project Overview -->
<section class="py-20 bg-white">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-3 gap-12">
      <!-- Main Content -->
      <div class="lg:col-span-2">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Project Overview</h2>
        <div class="prose prose-lg max-w-none">
          <p class="text-gray-600 mb-6">
            This comprehensive e-commerce platform was built to provide a complete online shopping experience with modern features and robust functionality. The project showcases advanced web development techniques and best practices in e-commerce solutions.
          </p>
          
          <h3 class="text-2xl font-bold text-gray-900 mb-4">Key Features</h3>
          <ul class="space-y-3 mb-8">
            <li class="flex items-start">
              <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
              <span class="text-gray-600">Advanced product catalog with categories, filters, and search functionality</span>
            </li>
            <li class="flex items-start">
              <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
              <span class="text-gray-600">Secure payment processing with Stripe integration</span>
            </li>
            <li class="flex items-start">
              <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
              <span class="text-gray-600">Real-time inventory management and stock tracking</span>
            </li>
            <li class="flex items-start">
              <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
              <span class="text-gray-600">Comprehensive admin dashboard with analytics</span>
            </li>
            <li class="flex items-start">
              <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
              <span class="text-gray-600">User authentication and role-based access control</span>
            </li>
            <li class="flex items-start">
              <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
              <span class="text-gray-600">Responsive design optimized for all devices</span>
            </li>
            <li class="flex items-start">
              <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
              <span class="text-gray-600">Order management and tracking system</span>
            </li>
            <li class="flex items-start">
              <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
              <span class="text-gray-600">Email notifications and customer communication</span>
            </li>
          </ul>
          
          <h3 class="text-2xl font-bold text-gray-900 mb-4">Technical Implementation</h3>
          <p class="text-gray-600 mb-6">
            The platform was built using modern web technologies with a focus on performance, scalability, and user experience. The frontend utilizes React with TypeScript for type safety, while the backend is powered by Node.js with Express framework.
          </p>
          
          <div class="grid md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gray-50 p-6 rounded-lg">
              <h4 class="font-bold text-gray-900 mb-3">Frontend Technologies</h4>
              <ul class="space-y-2 text-sm text-gray-600">
                <li>• React 18 with TypeScript</li>
                <li>• Tailwind CSS for styling</li>
                <li>• React Router for navigation</li>
                <li>• Redux Toolkit for state management</li>
                <li>• React Query for data fetching</li>
              </ul>
            </div>
            <div class="bg-gray-50 p-6 rounded-lg">
              <h4 class="font-bold text-gray-900 mb-3">Backend Technologies</h4>
              <ul class="space-y-2 text-sm text-gray-600">
                <li>• Node.js with Express</li>
                <li>• MongoDB with Mongoose ODM</li>
                <li>• JWT for authentication</li>
                <li>• Stripe API for payments</li>
                <li>• Nodemailer for emails</li>
              </ul>
            </div>
          </div>
          
          <h3 class="text-2xl font-bold text-gray-900 mb-4">Development Process</h3>
          <p class="text-gray-600 mb-6">
            The project followed an agile development methodology with regular client feedback and iterative improvements. The development process included comprehensive planning, design, development, testing, and deployment phases.
          </p>
          
          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-lg mb-8">
            <h4 class="font-bold text-gray-900 mb-3">Project Timeline</h4>
            <div class="space-y-3">
              <div class="flex items-center">
                <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                <span class="text-sm text-gray-600">Week 1-2: Planning and Design</span>
              </div>
              <div class="flex items-center">
                <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                <span class="text-sm text-gray-600">Week 3-6: Frontend Development</span>
              </div>
              <div class="flex items-center">
                <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                <span class="text-sm text-gray-600">Week 7-10: Backend Development</span>
              </div>
              <div class="flex items-center">
                <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                <span class="text-sm text-gray-600">Week 11-12: Testing and Deployment</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Sidebar -->
      <div class="lg:col-span-1">
        <div class="bg-gray-50 rounded-xl p-6 sticky top-8">
          <h3 class="text-xl font-bold text-gray-900 mb-6">Project Details</h3>
          
          <div class="space-y-6">
            <div>
              <h4 class="font-semibold text-gray-900 mb-2">Client</h4>
              <p class="text-gray-600">TechStart Solutions</p>
            </div>
            
            <div>
              <h4 class="font-semibold text-gray-900 mb-2">Duration</h4>
              <p class="text-gray-600">12 weeks</p>
            </div>
            
            <div>
              <h4 class="font-semibold text-gray-900 mb-2">Team Size</h4>
              <p class="text-gray-600">4 developers</p>
            </div>
            
            <div>
              <h4 class="font-semibold text-gray-900 mb-2">Category</h4>
              <p class="text-gray-600">E-commerce / Web Development</p>
            </div>
            
            <div>
              <h4 class="font-semibold text-gray-900 mb-2">Status</h4>
              <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">Completed</span>
            </div>
            
            <div>
              <h4 class="font-semibold text-gray-900 mb-2">Launch Date</h4>
              <p class="text-gray-600">March 2024</p>
            </div>
          </div>
          
          <div class="mt-8 pt-6 border-t border-gray-200">
            <h4 class="font-semibold text-gray-900 mb-4">Key Metrics</h4>
            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-gray-600">Performance Score</span>
                <span class="font-semibold text-gray-900">98/100</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Accessibility</span>
                <span class="font-semibold text-gray-900">95/100</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">SEO Score</span>
                <span class="font-semibold text-gray-900">92/100</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Related Projects -->
<section class="py-20 bg-gray-50">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <h2 class="text-3xl font-bold text-gray-900 mb-12 text-center">Related Projects</h2>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
      <a href="#" class="group bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden">
        <div class="h-48 bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
          <i class="fas fa-university text-white text-4xl group-hover:scale-110 transition-transform duration-300"></i>
        </div>
        <div class="p-6">
          <h3 class="text-xl font-bold text-gray-900 mb-2">Mobile Banking App</h3>
          <p class="text-gray-600 text-sm">Secure mobile banking application with biometric authentication.</p>
        </div>
      </a>
      
      <a href="#" class="group bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden">
        <div class="h-48 bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center">
          <i class="fas fa-utensils text-white text-4xl group-hover:scale-110 transition-transform duration-300"></i>
        </div>
        <div class="p-6">
          <h3 class="text-xl font-bold text-gray-900 mb-2">Restaurant Management</h3>
          <p class="text-gray-600 text-sm">Complete restaurant management system with order processing.</p>
        </div>
      </a>
      
      <a href="#" class="group bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden">
        <div class="h-48 bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
          <i class="fas fa-graduation-cap text-white text-4xl group-hover:scale-110 transition-transform duration-300"></i>
        </div>
        <div class="p-6">
          <h3 class="text-xl font-bold text-gray-900 mb-2">Learning Management System</h3>
          <p class="text-gray-600 text-sm">Comprehensive LMS platform with course creation and tracking.</p>
        </div>
      </a>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
