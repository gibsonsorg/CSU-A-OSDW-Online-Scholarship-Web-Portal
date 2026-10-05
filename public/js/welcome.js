document.addEventListener('DOMContentLoaded', function() {
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const navCenter = document.getElementById('navCenter');
    const menuClose = document.getElementById('menuClose');
    const menuBackdrop = document.getElementById('menuBackdrop');
    const menuItems = document.querySelectorAll('.menu-item');
    const faqSection = document.querySelector('.welcome-faq-section');

    if (faqSection) {
        faqSection.addEventListener('click', function(event) {
            const question = event.target.closest('.welcome-faq-question');
            if (!question || !faqSection.contains(question)) return;

            const isExpanded = question.getAttribute('aria-expanded') === 'true';
            const answer = document.getElementById(question.getAttribute('aria-controls'));
            const item = question.closest('.welcome-faq-item');
            if (!answer || !item) return;

            question.setAttribute('aria-expanded', String(!isExpanded));
            answer.setAttribute('aria-hidden', String(isExpanded));
            answer.inert = isExpanded;
            item.classList.toggle('is-open', !isExpanded);
        });
    }

    const aboutCarousel = document.querySelector('[data-about-carousel]');
    if (aboutCarousel) {
        const slides = Array.from(aboutCarousel.querySelectorAll('[data-about-slide]'));
        const indicators = Array.from(aboutCarousel.querySelectorAll('[data-about-indicator]'));
        const counter = aboutCarousel.querySelector('.about-grantee-counter');
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        let activeSlide = 0;
        let rotationTimer = null;

        function showSlide(index) {
            activeSlide = (index + slides.length) % slides.length;
            slides.forEach((slide, slideIndex) => {
                const isActive = slideIndex === activeSlide;
                slide.classList.toggle('is-active', isActive);
                slide.setAttribute('aria-hidden', String(!isActive));
                slide.inert = !isActive;
            });
            indicators.forEach((indicator, slideIndex) => {
                const isActive = slideIndex === activeSlide;
                indicator.classList.toggle('is-active', isActive);
                indicator.setAttribute('aria-current', String(isActive));
            });
            if (counter) counter.textContent = `0${activeSlide + 1} / 0${slides.length}`;
        }

        function stopRotation() {
            window.clearInterval(rotationTimer);
            rotationTimer = null;
        }

        function startRotation() {
            stopRotation();
            if (reducedMotion.matches || document.hidden || aboutCarousel.matches(':hover') || aboutCarousel.contains(document.activeElement)) return;
            rotationTimer = window.setInterval(() => showSlide(activeSlide + 1), 5000);
        }

        aboutCarousel.addEventListener('click', function(event) {
            if (event.target.closest('[data-about-previous]')) {
                showSlide(activeSlide - 1);
            } else if (event.target.closest('[data-about-next]')) {
                showSlide(activeSlide + 1);
            } else {
                const indicator = event.target.closest('[data-about-indicator]');
                if (indicator) showSlide(Number(indicator.dataset.aboutIndicator));
            }
        });
        aboutCarousel.addEventListener('mouseenter', stopRotation);
        aboutCarousel.addEventListener('mouseleave', startRotation);
        aboutCarousel.addEventListener('focusin', stopRotation);
        aboutCarousel.addEventListener('focusout', function(event) {
            if (!aboutCarousel.contains(event.relatedTarget)) startRotation();
        });
        document.addEventListener('visibilitychange', startRotation);
        reducedMotion.addEventListener('change', startRotation);
        startRotation();
    }

    // Open menu when hamburger is clicked
    hamburgerBtn.addEventListener('click', function() {
        navCenter.classList.add('active');
        menuBackdrop.classList.add('active');
        hamburgerBtn.classList.add('active');
    });

    // Close menu when close button is clicked
    menuClose.addEventListener('click', function() {
        closeMenu();
    });

    // Close menu when backdrop is clicked
    menuBackdrop.addEventListener('click', function() {
        closeMenu();
    });

    // Close menu when a menu item is clicked
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Remove active class from all items
            menuItems.forEach(i => i.classList.remove('active'));
            // Add active class to clicked item
            this.classList.add('active');
            
            // Close menu after selection
            closeMenu();
        });
    });

    // Function to close menu
    function closeMenu() {
        navCenter.classList.remove('active');
        menuBackdrop.classList.remove('active');
        hamburgerBtn.classList.remove('active');
    }

    // Close menu when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768) {
            if (navCenter.classList.contains('active')) {
                const clickedInsideNav = navCenter.contains(event.target);
                const clickedBtn = hamburgerBtn.contains(event.target);

                if (!clickedInsideNav && !clickedBtn) {
                    closeMenu();
                }
            }
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeMenu();
        }
    });
});