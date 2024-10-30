import './bootstrap';

// Handle mobile menu
document.addEventListener('DOMContentLoaded', () => {
    const mobileMenuButton = document.querySelector('[aria-controls="mobile-menu"]');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', () => {
            const expanded = mobileMenuButton.getAttribute('aria-expanded') === 'true';
            mobileMenuButton.setAttribute('aria-expanded', String(!expanded));
            mobileMenu.classList.toggle('hidden');
        });
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', (event) => {
        if (!mobileMenu?.contains(event.target) && !mobileMenuButton?.contains(event.target)) {
            mobileMenu?.classList.add('hidden');
            mobileMenuButton?.setAttribute('aria-expanded', 'false');
        }
    });

    // Handle smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Close mobile menu after clicking a link
                mobileMenu?.classList.add('hidden');
                mobileMenuButton?.setAttribute('aria-expanded', 'false');
            }
        });
    });

    // Handle header transparency on scroll
    const header = document.querySelector('header');
    let lastScroll = 0;

    window.addEventListener('scroll', () => {
        if (!header) return;

        const currentScroll = window.pageYOffset;
        
        if (currentScroll <= 0) {
            header.classList.remove('shadow-md');
            header.classList.add('shadow-sm');
        } else {
            header.classList.add('shadow-md');
            header.classList.remove('shadow-sm');
        }

        if (currentScroll > lastScroll && currentScroll > 100) {
            // Scrolling down & past the header
            header.classList.add('-translate-y-full');
        } else {
            // Scrolling up
            header.classList.remove('-translate-y-full');
        }

        lastScroll = currentScroll;
    });
});
