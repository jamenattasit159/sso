// main.js - Frontend JavaScript

document.addEventListener('DOMContentLoaded', function () {

    // Smooth scrolling for anchor links
    initializeSmoothScroll();

    // Initialize animations on scroll
    initializeScrollAnimations();

    // Initialize mobile menu
    initializeMobileMenu();

    // Initialize search functionality
    initializeSearch();
});

/**
 * Initialize smooth scrolling
 */
function initializeSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;

            e.preventDefault();
            const target = document.querySelector(href);

            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Initialize scroll animations
 */
function initializeScrollAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });

    document.querySelectorAll('.card, .director-card, .announce-card').forEach(el => {
        observer.observe(el);
    });
}

/**
 * Initialize mobile menu
 */
function initializeMobileMenu() {
    const toggleBtn = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (toggleBtn && navMenu) {
        toggleBtn.addEventListener('click', function () {
            navMenu.classList.toggle('active');
        });
    }
}

/**
 * Initialize search functionality
 */
function initializeSearch() {
    const searchInput = document.querySelector('.search-input');

    if (searchInput) {
        searchInput.addEventListener('keyup', function (e) {
            if (e.key === 'Enter') {
                performSearch(this.value);
            }
        });
    }
}

/**
 * Perform search
 */
function performSearch(query) {
    if (!query.trim()) {
        alert('กรุณากรอกคำค้นหา');
        return;
    }

    // Redirect to search page
    window.location.href = `search.php?q=${encodeURIComponent(query)}`;
}

/**
 * Format date to Thai format
 */
function formatThaiDate(dateString) {
    const date = new Date(dateString);
    const months = [
        'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน',
        'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม',
        'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];

    const day = date.getDate();
    const month = months[date.getMonth()];
    const year = date.getFullYear() + 543; // Buddhist year

    return `${day} ${month} ${year}`;
}

/**
 * Add animation class to element
 */
function addAnimation(element, animation) {
    element.classList.add('animated', animation);
}

/**
 * Lazy load images
 */
function initializeLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
}

/**
 * Scroll to top functionality
 */
function initializeScrollToTop() {
    const scrollBtn = document.querySelector('.scroll-to-top');

    if (scrollBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                scrollBtn.style.display = 'block';
            } else {
                scrollBtn.style.display = 'none';
            }
        });

        scrollBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

/**
 * Print page
 */
function printPage() {
    window.print();
}

/**
 * Share page
 */
function sharePage(title, url) {
    if (navigator.share) {
        navigator.share({
            title: title,
            url: url
        }).catch(err => console.log('Error sharing:', err));
    } else {
        alert('ยังไม่สนับสนุนการแชร์');
    }
}

// CSS for animations
const style = document.createElement('style');
style.innerHTML = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeInLeft {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes fadeInRight {
        from {
            opacity: 0;
            transform: translateX(30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(100px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animated {
        animation: fadeInUp 0.6s ease-out forwards;
    }
    
    .card.animated {
        animation: slideInUp 0.6s ease-out forwards;
    }
    
    .director-card.animated {
        animation: fadeInUp 0.6s ease-out forwards;
    }
    
    .announce-card.animated {
        animation: fadeInUp 0.6s ease-out forwards;
    }
    
    img.loaded {
        animation: fadeIn 0.3s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
`;
document.head.appendChild(style);