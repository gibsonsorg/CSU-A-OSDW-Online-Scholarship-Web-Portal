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
        <img src="{{ asset('images/maroon osdw.png') }}" alt="osdw Logo">
        <h3>OSDW SCHOLARSHIP</h3>
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
            <a href="#faq">FAQs</a>
            
        </div>

        <div class="nav-auth">
            <a href="{{ route('login') }}"><b>Login</b></a>
            <a href="{{ route('register') }}" class="regBtn"><b>Register</b></a>
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
                <a href="#faq" class="menu-item" data-icon="question">
                    <i class="fas fa-question-circle"></i>
                    <span>FAQs</span>
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
            <h2>Web-Based Scholarship Management and Requirements Monitoring System For Cagayan State University Aparri Campus</h2>
            <p>Providing easy access to scholarships, financial aid, and essential community services through a streamlined online platform.</p>
            <div class="cta-buttons">
                <a href="{{ route('register') }}" class="btn btn-primary">Get Started</a>
                
            </div>
        </div>
    </div>
  </section>

 

  <!-- How It Works Section -->
  <section class="how-it-works" id="how-it-works">
    <div class="container">
        <h2>How to Apply</h2>
        <p class="section-subtitle">Simple steps to access our services</p>
        <div class="application-steps">
            <article class="application-step">
                <div class="application-step-number">1</div>
                <div class="application-step-content">
                    <h3>Create an Account</h3>
                    <p>Register using your Gmail address and a secure password.</p>
                </div>
            </article>
            <article class="application-step">
                <div class="application-step-number">2</div>
                <div class="application-step-content">
                    <h3>Verify Your Email</h3>
                    <p>Enter the 6-digit code sent to your Gmail.</p>
                </div>
            </article>
            <article class="application-step">
                <div class="application-step-number">3</div>
                <div class="application-step-content">
                    <h3>Explore &amp; Apply</h3>
                    <p>Review scholarship eligibility, complete the form, and upload required documents.</p>
                </div>
            </article>
            <article class="application-step">
                <div class="application-step-number">4</div>
                <div class="application-step-content">
                    <h3>Track Your Application</h3>
                    <p>Check your application status and notifications for updates.</p>
                </div>
            </article>
        </div>
    </div>
  </section>

  <!-- About Section -->
  <section class="about-section" id="about">
    <div class="container">
        <div class="about-grantee-layout">
            <div class="about-grantee-copy">
                <p class="about-grantee-eyebrow">CSU APARRI CAMPUS</p>
                <h2>Every Opportunity Begins With Support</h2>
                <p>The CSU Aparri Campus Scholarship and Student Financial Assistance Program is an online platform designed to connect students with educational assistance and scholarship opportunities.</p>
                <p>Through a streamlined application process, students can explore available grants, submit requirements, and monitor their application progress in one convenient platform.</p>
            </div>

            <div class="about-grantee-carousel" data-about-carousel role="region" aria-roledescription="carousel" aria-label="CSU Aparri student grantee photographs">
                    <div class="about-grantee-slides">
                        <figure class="about-grantee-slide is-active" data-about-slide role="group" aria-roledescription="slide" aria-label="1 of 3" aria-hidden="false">
                            <img src="{{ asset('images/cover1.jfif') }}" alt="CSU Aparri student grantee, photograph 1 of 3" fetchpriority="high">
                        </figure>
                        <figure class="about-grantee-slide" data-about-slide role="group" aria-roledescription="slide" aria-label="2 of 3" aria-hidden="true" inert>
                            <img src="{{ asset('images/cover2.jfif') }}" alt="CSU Aparri student grantee, photograph 2 of 3" loading="lazy">
                        </figure>
                        <figure class="about-grantee-slide" data-about-slide role="group" aria-roledescription="slide" aria-label="3 of 3" aria-hidden="true" inert>
                            <img src="{{ asset('images/cover3.jfif') }}" alt="CSU Aparri student grantee, photograph 3 of 3" loading="lazy">
                        </figure>
                    </div>
                    <div class="about-grantee-carousel-controls">
                        <button class="about-grantee-arrow" type="button" data-about-previous aria-label="Previous grantee photograph"><span aria-hidden="true">&#8249;</span></button>
                        <div class="about-grantee-indicators" role="group" aria-label="Choose a grantee photograph">
                            <button class="about-grantee-dot is-active" type="button" data-about-indicator="0" aria-label="Show grantee photograph 1 of 3" aria-current="true"></button>
                            <button class="about-grantee-dot" type="button" data-about-indicator="1" aria-label="Show grantee photograph 2 of 3" aria-current="false"></button>
                            <button class="about-grantee-dot" type="button" data-about-indicator="2" aria-label="Show grantee photograph 3 of 3" aria-current="false"></button>
                        </div>
                        <button class="about-grantee-arrow" type="button" data-about-next aria-label="Next grantee photograph"><span aria-hidden="true">&#8250;</span></button>
                        <span class="about-grantee-counter" aria-live="polite">01 / 03</span>
                    </div>
            </div>
            <div class="about-grantee-gallery">
                    <h3>Student Grantee Gallery</h3>
                    <div class="about-grantee-gallery-grid">
                        <figure><img src="{{ asset('images/semicover1.jfif') }}" alt="CSU Aparri student grantee, supporting photograph 1" loading="lazy"></figure>
                        <figure><img src="{{ asset('images/semicover2jfif.jfif') }}" alt="CSU Aparri student grantee, supporting photograph 2" loading="lazy"></figure>
                        <figure><img src="{{ asset('images/semicover3.jfif') }}" alt="CSU Aparri student grantee, supporting photograph 3" loading="lazy"></figure>
                        <figure><img src="{{ asset('images/semicover4.jfif') }}" alt="CSU Aparri student grantee, supporting photograph 4" loading="lazy"></figure>
                    </div>
                    <div class="about-grantee-stats" aria-label="Scholarship program highlights">
                        <div class="about-grantee-stat">
                            <strong>1500</strong>
                            <span>Scholars Supported</span>
                        </div>
                        <div class="about-grantee-stat">
                            <strong>3,500</strong>
                            <span>Student Assisted</span>
                        </div>
                        <div class="about-grantee-stat">
                            <strong>85%</strong>
                            <span>Satisfaction Rate</span>
                        </div>
                    </div>
            </div>

        <div class="about-grantee-support">
            <div class="about-grantee-support-heading">
               
                <h3>Students, Supported in Their Journey</h3>
            </div>
            <div class="about-grantee-benefits-grid">
                <article class="about-grantee-benefit">
                   
                    <h4>Access to Opportunities</h4>
                    <p>Discover available academic and non-academic scholarships that support students with different qualifications and educational needs.</p>
                </article>
                <article class="about-grantee-benefit">
             
                    <h4>A Convenient Application Process</h4>
                    <p>Complete application forms, submit required documents, and monitor your application status through one centralized platform.</p>
                </article>
                <article class="about-grantee-benefit">
                  
                    <h4>Support for Student Success</h4>
                    <p>The portal helps make scholarship information and application updates more accessible to CSU Aparri students.</p>
                </article>
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

  <!-- Frequently Asked Questions Section -->
  <section class="welcome-faq-section" id="faq" aria-labelledby="welcome-faq-title">
    <div class="container">
        <header class="welcome-faq-header">
            <h2 id="welcome-faq-title">Frequently Asked Questions</h2>
            <p>Find answers to common questions about registration, scholarship applications, application status, and account support.</p>
        </header>

        <div class="welcome-faq-categories">
            <section class="welcome-faq-category" aria-labelledby="welcome-faq-applications">
                <h3 id="welcome-faq-applications">Scholarship Applications</h3>
                <div class="welcome-faq-list">
                    <article class="welcome-faq-item">
                        <h4><button class="welcome-faq-question" id="welcome-faq-q6" type="button" aria-expanded="false" aria-controls="welcome-faq-a6"><span>What is the difference between Academic and Non-Academic Grants?</span><span class="welcome-faq-indicator" aria-hidden="true"></span></button></h4>
                        <div class="welcome-faq-answer" id="welcome-faq-a6" role="region" aria-labelledby="welcome-faq-q6" aria-hidden="true" inert><div class="welcome-faq-answer-inner"><p>Academic Grants generally recognize academic qualifications, performance, or educational assistance eligibility, while Non-Academic Grants support students based on other qualifications, activities, or circumstances specified by the scholarship program.</p></div></div>
                    </article>
                    <article class="welcome-faq-item">
                        <h4><button class="welcome-faq-question" id="welcome-faq-q7" type="button" aria-expanded="false" aria-controls="welcome-faq-a7"><span>How many scholarships can I apply for?</span><span class="welcome-faq-indicator" aria-hidden="true"></span></button></h4>
                        <div class="welcome-faq-answer" id="welcome-faq-a7" role="region" aria-labelledby="welcome-faq-q7" aria-hidden="true" inert><div class="welcome-faq-answer-inner"><p>Students may have a maximum of two active scholarship applications across both categories:</p><ul><li>Two Academic Grants</li><li>Two Non-Academic Grants</li><li>One Academic and one Non-Academic Grant</li></ul><p>The maximum is two active applications in total, not two per category.</p></div></div>
                    </article>
                    <article class="welcome-faq-item">
                        <h4><button class="welcome-faq-question" id="welcome-faq-q8" type="button" aria-expanded="false" aria-controls="welcome-faq-a8"><span>Can I apply for the same scholarship more than once?</span><span class="welcome-faq-indicator" aria-hidden="true"></span></button></h4>
                        <div class="welcome-faq-answer" id="welcome-faq-a8" role="region" aria-labelledby="welcome-faq-q8" aria-hidden="true" inert><div class="welcome-faq-answer-inner"><p>A student cannot submit duplicate active applications for the same scholarship. If an application is rejected for a reason that allows correction and reapplication, the student may reapply according to the system's reapplication rules.</p></div></div>
                    </article>
                    <article class="welcome-faq-item">
                        <h4><button class="welcome-faq-question" id="welcome-faq-q9" type="button" aria-expanded="false" aria-controls="welcome-faq-a9"><span>What documents should I upload?</span><span class="welcome-faq-indicator" aria-hidden="true"></span></button></h4>
                        <div class="welcome-faq-answer" id="welcome-faq-a9" role="region" aria-labelledby="welcome-faq-q9" aria-hidden="true" inert><div class="welcome-faq-answer-inner"><p>Required documents depend on the scholarship. Review the scholarship's Required Documents section and the application form before submitting. Upload files in the accepted format and within the specified size limit.</p></div></div>
                    </article>
                    <article class="welcome-faq-item">
                        <h4><button class="welcome-faq-question" id="welcome-faq-q10" type="button" aria-expanded="false" aria-controls="welcome-faq-a10"><span>Can I edit my application after submitting it?</span><span class="welcome-faq-indicator" aria-hidden="true"></span></button></h4>
                        <div class="welcome-faq-answer" id="welcome-faq-a10" role="region" aria-labelledby="welcome-faq-q10" aria-hidden="true" inert><div class="welcome-faq-answer-inner"><p>Submitted applications are subject to administrative review. If the administrator rejects an application for document correction or an incomplete form, the student may be allowed to correct and resubmit it within the permitted reapplication period.</p></div></div>
                    </article>
                </div>
            </section>

            <section class="welcome-faq-category" aria-labelledby="welcome-faq-status">
                <h3 id="welcome-faq-status">Application Status and Notifications</h3>
                <div class="welcome-faq-list">
                    <article class="welcome-faq-item">
                        <h4><button class="welcome-faq-question" id="welcome-faq-q11" type="button" aria-expanded="false" aria-controls="welcome-faq-a11"><span>How can I check my scholarship application status?</span><span class="welcome-faq-indicator" aria-hidden="true"></span></button></h4>
                        <div class="welcome-faq-answer" id="welcome-faq-a11" role="region" aria-labelledby="welcome-faq-q11" aria-hidden="true" inert><div class="welcome-faq-answer-inner"><p>Log in to your account and navigate to the Application Status section to view the current status of your submitted applications.</p></div></div>
                    </article>
                    <article class="welcome-faq-item">
                        <h4><button class="welcome-faq-question" id="welcome-faq-q12" type="button" aria-expanded="false" aria-controls="welcome-faq-a12"><span>What happens if my application is approved?</span><span class="welcome-faq-indicator" aria-hidden="true"></span></button></h4>
                        <div class="welcome-faq-answer" id="welcome-faq-a12" role="region" aria-labelledby="welcome-faq-q12" aria-hidden="true" inert><div class="welcome-faq-answer-inner"><p>Your application will be marked Approved. Approved applications are locked and cannot be resubmitted through the application form.</p></div></div>
                    </article>
                    <article class="welcome-faq-item">
                        <h4><button class="welcome-faq-question" id="welcome-faq-q13" type="button" aria-expanded="false" aria-controls="welcome-faq-a13"><span>What happens if my application is rejected?</span><span class="welcome-faq-indicator" aria-hidden="true"></span></button></h4>
                        <div class="welcome-faq-answer" id="welcome-faq-a13" role="region" aria-labelledby="welcome-faq-q13" aria-hidden="true" inert><div class="welcome-faq-answer-inner"><p>Review the rejection reason and any remarks provided by the administrator. Depending on the rejection reason, your application may either become unavailable for reapplication or allow a permitted correction and resubmission.</p></div></div>
                    </article>
                    <article class="welcome-faq-item">
                        <h4><button class="welcome-faq-question" id="welcome-faq-q14" type="button" aria-expanded="false" aria-controls="welcome-faq-a14"><span>How will I know if there is an update to my application?</span><span class="welcome-faq-indicator" aria-hidden="true"></span></button></h4>
                        <div class="welcome-faq-answer" id="welcome-faq-a14" role="region" aria-labelledby="welcome-faq-q14" aria-hidden="true" inert><div class="welcome-faq-answer-inner"><p>You can check the notification bell in your student dashboard and the Application Status page. Email notifications may also be sent when the administrator makes a decision, depending on the system's current email notification settings.</p></div></div>
                    </article>
                </div>
            </section>

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
                    <li><a href="#faq">FAQs</a></li>
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