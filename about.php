<?php 
include 'includes/meta_helper.php';

// Set page-specific meta data
setQuickMeta(
    'About ManuelCode | Professional Software Engineer & Full-Stack Developer',
    'Learn about ManuelCode, a professional software engineer with expertise in full-stack development, mobile applications, and custom software solutions. Experienced in modern technologies and innovative problem-solving.',
    'assets/favi/favicon.png',
    'about ManuelCode, software engineer, full-stack developer, professional developer, software development experience, technical expertise'
);

include 'includes/header.php'; 
?>

<!-- Hero Section -->
<section class="relative py-16 md:py-24 bg-gradient-to-br from-[#536895] to-[#2D3E50] overflow-hidden page-hero-section">
  <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <h1 class="text-3xl md:text-5xl font-bold text-white mb-4 leading-tight" style="font-family: 'Inter', sans-serif;">
      About Us
    </h1>
    <p class="text-lg md:text-xl text-white/90 max-w-2xl mx-auto leading-relaxed">
      Building innovative solutions for tomorrow's challenges
    </p>
  </div>
</section>

<!-- Mission & Vision Section -->
<section class="py-20 bg-white">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-2 gap-16 items-center">
      <!-- Mission -->
      <div class="space-y-6">
        <div class="inline-flex items-center px-4 py-2 bg-blue-50 text-blue-700 rounded-full text-sm font-medium">
          <i class="fas fa-bullseye mr-2"></i>
          Our Mission
        </div>
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 leading-tight">
          Empowering Businesses Through
          <span class="text-[#536895]">Innovative Technology</span>
        </h2>
        <p class="text-lg text-gray-600 leading-relaxed">
          We strive to deliver cutting-edge software solutions that not only meet our clients' immediate needs but also provide long-term value and scalability. Our mission is to bridge the gap between complex business requirements and elegant, user-friendly applications.
        </p>
        <div class="grid grid-cols-2 gap-4">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
              <i class="fas fa-rocket text-blue-600"></i>
            </div>
            <span class="text-gray-700 font-medium">Fast Delivery</span>
          </div>
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
              <i class="fas fa-shield-alt text-green-600"></i>
            </div>
            <span class="text-gray-700 font-medium">Secure Solutions</span>
          </div>
        </div>
      </div>
      
      <!-- Vision -->
      <div class="space-y-6">
        <div class="inline-flex items-center px-4 py-2 bg-purple-50 text-purple-700 rounded-full text-sm font-medium">
          <i class="fas fa-eye mr-2"></i>
          Our Vision
        </div>
        <h3 class="text-3xl md:text-4xl font-bold text-gray-900 leading-tight">
          Leading the Future of
          <span class="text-[#536895]">Digital Innovation</span>
        </h3>
        <p class="text-lg text-gray-600 leading-relaxed">
          We envision a world where technology seamlessly integrates with everyday business operations, creating opportunities for growth, efficiency, and success. Our goal is to be at the forefront of digital transformation.
        </p>
        <div class="grid grid-cols-2 gap-4">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
              <i class="fas fa-lightbulb text-purple-600"></i>
            </div>
            <span class="text-gray-700 font-medium">Innovation</span>
          </div>
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
              <i class="fas fa-globe text-orange-600"></i>
            </div>
            <span class="text-gray-700 font-medium">Global Reach</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Team Photo Section -->
<section class="py-20 bg-gray-50">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
        Our <span class="text-[#536895]">Team</span> Together
      </h2>
      <p class="text-lg text-gray-600 max-w-2xl mx-auto">
        A collaborative team of young software developers from Takoradi Technical University working together to deliver exceptional software solutions
      </p>
    </div>
    
    <div class="relative">
      <div class="bg-gradient-to-br from-[#536895] to-[#4a5a7a] rounded-2xl p-4 md:p-8 shadow-2xl">
        <div class="max-w-4xl mx-auto">
          <img src="assets/images/teammanuel.jpg" alt="ManuelCode Team" class="w-full h-auto rounded-xl shadow-lg object-cover">
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent rounded-2xl pointer-events-none"></div>
      </div>
      
      <!-- Floating stats -->
      <div class="absolute -bottom-6 left-1/2 transform -translate-x-1/2 bg-white rounded-xl shadow-lg px-4 md:px-8 py-3 md:py-4">
        <div class="flex space-x-4 md:space-x-8">
          <div class="text-center">
            <div class="text-lg md:text-2xl font-bold text-[#536895]">4</div>
            <div class="text-xs md:text-sm text-gray-600">Team Members</div>
          </div>
          <div class="text-center">
            <div class="text-lg md:text-2xl font-bold text-[#536895]">50+</div>
            <div class="text-xs md:text-sm text-gray-600">Projects Completed</div>
          </div>
          <div class="text-center">
            <div class="text-lg md:text-2xl font-bold text-[#536895]">100%</div>
            <div class="text-xs md:text-sm text-gray-600">Client Satisfaction</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Core Values Section -->
<section class="py-20 bg-white">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16">
      <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
        Our <span class="text-[#536895]">Core Values</span>
      </h2>
      <p class="text-lg text-gray-600 max-w-2xl mx-auto">
        The principles that guide our work and relationships with clients
      </p>
    </div>
    
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
      <!-- Excellence -->
      <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-award text-white text-xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Excellence</h3>
        <p class="text-gray-600">We strive for excellence in every project, delivering quality that exceeds expectations.</p>
      </div>
      
      <!-- Innovation -->
      <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-lightbulb text-white text-xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Innovation</h3>
        <p class="text-gray-600">Constantly exploring new technologies and approaches to solve complex problems.</p>
      </div>
      
      <!-- Integrity -->
      <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-handshake text-white text-xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Integrity</h3>
        <p class="text-gray-600">Building trust through honest communication and transparent business practices.</p>
      </div>
      
      <!-- Collaboration -->
      <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
        <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-users text-white text-xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Collaboration</h3>
        <p class="text-gray-600">Working closely with clients to understand their needs and deliver tailored solutions.</p>
      </div>
    </div>
  </div>
</section>

<!-- Services Overview Section -->
<section class="py-20 bg-white">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16">
      <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
        What We <span class="text-[#536895]">Specialize In</span>
      </h2>
      <p class="text-lg text-gray-600 max-w-2xl mx-auto">
        Comprehensive software solutions tailored to your business needs
      </p>
    </div>
    
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
      <!-- Web Development -->
      <div class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 p-8 text-white">
        <div class="absolute inset-0 bg-black bg-opacity-20 group-hover:bg-opacity-30 transition-all duration-300"></div>
        <div class="relative z-10">
          <div class="w-16 h-16 bg-white/20 rounded-lg flex items-center justify-center mb-6">
            <i class="fas fa-code text-2xl"></i>
          </div>
          <h3 class="text-2xl font-bold mb-4">Web Development</h3>
          <p class="text-blue-100 mb-6">Modern, responsive websites and web applications built with cutting-edge technologies.</p>
          <ul class="space-y-2 text-sm text-blue-100">
            <li class="flex items-center"><i class="fas fa-check mr-2"></i>React & Vue.js</li>
            <li class="flex items-center"><i class="fas fa-check mr-2"></i>Node.js & PHP</li>
            <li class="flex items-center"><i class="fas fa-check mr-2"></i>Responsive Design</li>
          </ul>
        </div>
      </div>
      
      <!-- Mobile Development -->
      <div class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 p-8 text-white">
        <div class="absolute inset-0 bg-black bg-opacity-20 group-hover:bg-opacity-30 transition-all duration-300"></div>
        <div class="relative z-10">
          <div class="w-16 h-16 bg-white/20 rounded-lg flex items-center justify-center mb-6">
            <i class="fas fa-mobile-alt text-2xl"></i>
          </div>
          <h3 class="text-2xl font-bold mb-4">Mobile Apps</h3>
          <p class="text-purple-100 mb-6">Native and cross-platform mobile applications for iOS and Android platforms.</p>
          <ul class="space-y-2 text-sm text-purple-100">
            <li class="flex items-center"><i class="fas fa-check mr-2"></i>React Native</li>
            <li class="flex items-center"><i class="fas fa-check mr-2"></i>Flutter</li>
            <li class="flex items-center"><i class="fas fa-check mr-2"></i>Native iOS/Android</li>
          </ul>
        </div>
      </div>
      
      <!-- API Development -->
      <div class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-green-500 to-green-600 p-8 text-white">
        <div class="absolute inset-0 bg-black bg-opacity-20 group-hover:bg-opacity-30 transition-all duration-300"></div>
        <div class="relative z-10">
          <div class="w-16 h-16 bg-white/20 rounded-lg flex items-center justify-center mb-6">
            <i class="fas fa-cogs text-2xl"></i>
          </div>
          <h3 class="text-2xl font-bold mb-4">API Development</h3>
          <p class="text-green-100 mb-6">Robust RESTful APIs and microservices for seamless integration.</p>
          <ul class="space-y-2 text-sm text-green-100">
            <li class="flex items-center"><i class="fas fa-check mr-2"></i>RESTful APIs</li>
            <li class="flex items-center"><i class="fas fa-check mr-2"></i>GraphQL</li>
            <li class="flex items-center"><i class="fas fa-check mr-2"></i>Microservices</li>
      </ul>
        </div>
      </div>
    </div>
    </div>
  </section>

<!-- Team Section -->
<section class="py-20 bg-white">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16">
      <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
        Meet Our <span class="text-[#536895]">Team</span>
      </h2>
      <p class="text-lg text-gray-600 max-w-2xl mx-auto">
        Passionate professionals dedicated to delivering exceptional results
      </p>
    </div>
    
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
      <!-- Emmanuel Kwofie - Lead Developer -->
      <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 text-center">
        <div class="w-28 h-32 rounded-lg overflow-hidden mx-auto mb-4 shadow-lg">
          <img src="assets/images/Manuelleaddeveloper.jpg" alt="Emmanuel Kwofie" class="w-full h-full object-cover object-top">
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Emmanuel Kwofie</h3>
        <p class="text-[#536895] font-medium mb-3">Lead Developer & Fullstack Developer</p>
        <p class="text-gray-600 text-sm">Experienced full-stack developer with expertise in modern web technologies and mobile development.</p>
        <div class="flex justify-center space-x-3 mt-4">
          <a href="https://www.linkedin.com/in/emmanuel-kofi-k-94869a108/" target="_blank" class="text-gray-400 hover:text-[#536895] transition-colors">
            <i class="fab fa-linkedin"></i>
          </a>
          <a href="https://github.com/androidnega" target="_blank" class="text-gray-400 hover:text-[#536895] transition-colors">
            <i class="fab fa-github"></i>
          </a>
        </div>
      </div>
      
      <!-- Benjamin Eshun - Frontend Developer -->
      <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 text-center">
        <div class="w-28 h-32 rounded-lg overflow-hidden mx-auto mb-4 shadow-lg">
          <img src="assets/images/benjamineshun.jpg" alt="Benjamin Eshun" class="w-full h-full object-cover object-top">
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Benjamin Eshun</h3>
        <p class="text-[#536895] font-medium mb-3">Frontend Developer</p>
        <p class="text-gray-600 text-sm">Creative frontend developer specializing in responsive design and modern UI/UX frameworks.</p>
        <div class="flex justify-center space-x-3 mt-4">
          <a href="#" class="text-gray-400 hover:text-[#536895] transition-colors">
            <i class="fab fa-linkedin"></i>
          </a>
          <a href="#" class="text-gray-400 hover:text-[#536895] transition-colors">
            <i class="fab fa-github"></i>
          </a>
        </div>
      </div>
      
      <!-- Elisha Wana - Data Analyst -->
      <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 text-center">
        <div class="w-28 h-32 rounded-lg overflow-hidden mx-auto mb-4 shadow-lg">
          <img src="assets/images/ElishaWana.jpg" alt="Elisha Wana" class="w-full h-full object-cover object-top">
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Elisha Wana</h3>
        <p class="text-[#536895] font-medium mb-3">Data Analyst</p>
        <p class="text-gray-600 text-sm">Expert data analyst with strong skills in data visualization and business intelligence solutions.</p>
        <div class="flex justify-center space-x-3 mt-4">
          <a href="#" class="text-gray-400 hover:text-[#536895] transition-colors">
            <i class="fab fa-linkedin"></i>
          </a>
          <a href="#" class="text-gray-400 hover:text-[#536895] transition-colors">
            <i class="fab fa-github"></i>
          </a>
        </div>
</div>
      
      <!-- Rose Awuah - Frontend & Documentalist -->
      <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 text-center">
        <div class="w-28 h-32 rounded-lg overflow-hidden mx-auto mb-4 shadow-lg">
          <img src="assets/images/RoseAwuah.jpg" alt="Rose Awuah" class="w-full h-full object-cover object-top">
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Rose Awuah</h3>
        <p class="text-[#536895] font-medium mb-3">Frontend Developer & Documentalist</p>
        <p class="text-gray-600 text-sm">Skilled frontend developer and technical writer ensuring clear documentation and user-friendly interfaces.</p>
        <div class="flex justify-center space-x-3 mt-4">
          <a href="#" class="text-gray-400 hover:text-[#536895] transition-colors">
            <i class="fab fa-linkedin"></i>
          </a>
          <a href="#" class="text-gray-400 hover:text-[#536895] transition-colors">
            <i class="fab fa-github"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>



<?php include 'includes/footer.php'; ?>
