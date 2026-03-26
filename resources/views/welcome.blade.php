<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSU-A Scholarship Web Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">  
</head>
<body>
  <nav>
    <div class="logo">
        <img src="{{ asset('images/osdw logo.jpg') }}" alt="Municipality Logo">
        <h2>OSDW web portal</h2>
    </div>

    <button class="hamburger-menu" id="hamburgerBtn">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <div class="nav-center" id="navCenter">
        <!-- Desktop Navigation (hidden on mobile) -->
        <div class="nav-links">
            <a href="#home">Home</a>
            <a href="#about">About</a>
            <a href="#how-it-works">How it works</a>
            <a href="#contact">Contact us</a>
            <a href="#services">Services</a>
        </div>

        <div class="nav-auth">
            <a href="{{ route('login') }}"><b>Login</b></a>
            <a href="{{ route('register') }}"><b>Register</b></a>
        </div>

        <!-- Mobile Navigation -->
        <div class="mobile-menu">
            <div class="menu-header">
                <button class="menu-close" id="menuClose">
                    <span>×</span>
                </button>
            </div>
            <div class="menu-items">
                <a href="#home" class="menu-item" data-icon="home">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="#about" class="menu-item" data-icon="info">
                    <i class="fas fa-info-circle"></i>
                    <span>About</span>
                </a>
                <a href="#how-it-works" class="menu-item" data-icon="book">
                    <i class="fas fa-book"></i>
                    <span>How It Works</span>
                </a>
                <a href="#contact" class="menu-item" data-icon="envelope">
                    <i class="fas fa-envelope"></i>
                    <span>Contact Us</span>
                </a>
                <a href="#services" class="menu-item" data-icon="cogs">
                    <i class="fas fa-cogs"></i>
                    <span>Services</span>
                </a>
                <div class="menu-divider"></div>
                <a href="{{ route('login') }}" class="menu-item auth-item login-item">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
                <a href="{{ route('register') }}" class="menu-item auth-item register-item">
                    <i class="fas fa-user-plus"></i>
                    <span>Register</span>
                </a>
            </div>
        </div>
    </div>
    <div class="menu-backdrop" id="menuBackdrop"></div>
  </nav>
  
    

  <!-- Welcome Section -->
  <section class="welcome-section" >
    <div class="container">
        <div class="welcome-content">
            <h2>CSU Aparri Campus Scholarship and Student Financial Assistance Program</h2>
            <p>Your gateway to educational opportunities and community support programs. We are committed to helping residents access scholarships, financial assistance, and other essential services through our streamlined online platform.</p>
            <div class="cta-buttons">
                <a href="{{ route('register') }}" class="btn btn-primary">Get Started</a>
                <a href="#services" class="btn btn-secondary">Learn More</a>
            </div>
        </div>
    </div>
  </section>

  <!-- Services Section -->
  <section class="services-section" id="services">
    <div class="container">
        <h2>Our Services</h2>
        <p class="section-subtitle">Comprehensive programs designed to support our community members</p>
        <div class="services-grid">
            <div class="service-card">
                <div class="icon"></div>
                <h3>Scholarship Programs</h3>
                <p>Access various scholarship opportunities for students from elementary to college level. Supporting academic excellence in our community.</p>
            </div>
            <div class="service-card">
                <div class="icon"></div>
                <h3>Financial Assistance</h3>
                <p>Get support for medical expenses, burial assistance, and emergency financial needs through our assistance programs.</p>
            </div>
            <div class="service-card">
                <div class="icon"></div>
                <h3>Automated Profiling</h3>
                <p>Our AI-powered system ensures fair and efficient beneficiary assessment, making the process faster and more transparent.</p>
            </div>
            <div class="service-card">
                <div class="icon"></div>
                <h3>24/7 Online Access</h3>
                <p>Apply and track your application status anytime, anywhere. Convenient and hassle-free service at your fingertips.</p>
            </div>
        </div>
    </div>
  </section>

  <!-- How It Works Section -->
  <section class="how-it-works" id="how-it-works">
    <div class="container">
        <h2>How to Apply</h2>
        <p class="section-subtitle">Simple steps to access our services</p>
        <div class="steps-container">
            <div class="step">
                <div class="step-number">1</div>
                <h3>Register</h3>
                <p>Create your account with your basic information and valid email address</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <h3>Submit Application</h3>
                <p>Fill out the application form and upload required documents</p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <h3>Automated Review</h3>
                <p>Our system evaluates your eligibility based on set criteria</p>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <h3>Get Approved</h3>
                <p>Receive notification and access your benefits once approved</p>
            </div>
        </div>
    </div>
  </section>

  <!-- About Section -->
  <section class="about-section" id="about">
    <div class="container">
        <div class="about-content">
            <div class="about-text">
                <h2>About Our Portal</h2>
                <p>The CSU Aparri OSDW Assistance and Scholarship Recommendation Portal is designed to streamline the process of applying for and receiving government assistance and educational support.</p>
                <p>Our automated beneficiary profiling system uses advanced technology to ensure that assistance reaches those who need it most, while maintaining transparency and efficiency in the selection process.</p>
                <div class="stats-row">
                    <div class="stat-item">
                        <h3>500+</h3>
                        <p>Scholars Supported</p>
                    </div>
                    <div class="stat-item">
                        <h3>1,200+</h3>
                        <p>Families Assisted</p>
                    </div>
                    <div class="stat-item">
                        <h3>95%</h3>
                        <p>Satisfaction Rate</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </section>

  <!-- Contact Section -->
  <section class="contact-section" id="contact">
    <div class="container">
        <h2>Contact Us</h2>
        <p class="section-subtitle">Have questions? We're here to help</p>
        <div class="contact-grid">
            <div class="contact-card">
                <div class="contact-icon"></div>
                <h3>Visit Us</h3>
                <p>CSU Compound,Maura,Aparri,Philippines<br>OSDW Office</p>
            </div>
            <div class="contact-card">
                <div class="contact-icon"></div>
                <h3>Call Us</h3>
                <p>Cellphone number<br>0935 560 0948</p>
            </div>
            <div class="contact-card">
                <div class="contact-icon"></div>
                <h3>Email Us</h3>
                <p>csu.edu.ph<br>osdwaparri@csu.edu.ph</p>
            </div>
        </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Ready to Get Started?</h2>
            <p>Join hundreds of beneficiaries who have improved their lives through our programs</p>
            <a href="{{ route('register') }}" class="btn btn-large">Apply Now</a>
        </div>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>CSU Aparri OSDW Portal</h3>
                <p>Empowering our community through accessible and efficient services.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Office Hours</h3>
                <p>Monday - Friday<br>8:00 AM - 5:00 PM</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; CSU Aparri OSDW. All rights reserved.</p>
        </div>
    </div>
  </footer>

  <script src="{{ asset('js/welcome.js') }}"></script>
</body>
</html>