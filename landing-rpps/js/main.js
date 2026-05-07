/**
 * Landing Page RPPS - JavaScript
 * Handles navigation, scroll effects, and animations
 */

(function() {
    'use strict';

    // ============================================
    // DOM Elements
    // ============================================
    const navbar = document.getElementById('navbar');
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const scrollRevealElements = document.querySelectorAll('.scroll-reveal');

    // ============================================
    // Mobile Menu Toggle
    // ============================================
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('active');
        });

        // Close mobile menu when clicking a link
        const mobileLinks = mobileMenu.querySelectorAll('a');
        mobileLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                mobileMenu.classList.remove('active');
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!mobileMenu.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
                mobileMenu.classList.remove('active');
            }
        });
    }

    // ============================================
    // Navbar Scroll Effect
    // ============================================
    let lastScroll = 0;

    function handleNavbarScroll() {
        const currentScroll = window.pageYOffset;

        if (currentScroll > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }

        lastScroll = currentScroll;
    }

    window.addEventListener('scroll', handleNavbarScroll);
    // Initial check
    handleNavbarScroll();

    // ============================================
    // Scroll Reveal Animation
    // ============================================
    function initScrollReveal() {
        if (!('IntersectionObserver' in window)) {
            // Fallback for browsers that don't support IntersectionObserver
            scrollRevealElements.forEach(function(el) {
                el.classList.add('revealed');
            });
            return;
        }

        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        scrollRevealElements.forEach(function(el) {
            observer.observe(el);
        });
    }

    // Initialize scroll reveal
    initScrollReveal();

    // ============================================
    // Smooth Scroll for Anchor Links
    // ============================================
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                
                if (targetId === '#') return;
                
                const target = document.querySelector(targetId);
                if (target) {
                    const navbarHeight = navbar ? navbar.offsetHeight : 0;
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - navbarHeight;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    initSmoothScroll();

    // ============================================
    // Course Card Hover Effects
    // ============================================
    function initCourseCardEffects() {
        const courseCards = document.querySelectorAll('.course-card');
        
        courseCards.forEach(function(card) {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    }

    initCourseCardEffects();

    // ============================================
    // Certification Card Hover Effects
    // ============================================
    function initCertificationCardEffects() {
        const certCards = document.querySelectorAll('.certification-card');
        
        certCards.forEach(function(card) {
            card.addEventListener('mouseenter', function() {
                const icon = this.querySelector('.certification-card-icon');
                if (icon) {
                    icon.style.transform = 'scale(1.1)';
                }
            });
            
            card.addEventListener('mouseleave', function() {
                const icon = this.querySelector('.certification-card-icon');
                if (icon) {
                    icon.style.transform = 'scale(1)';
                }
            });
        });
    }

    initCertificationCardEffects();

    // ============================================
    // Differential Card Hover Effects
    // ============================================
    function initDifferentialCardEffects() {
        const diffCards = document.querySelectorAll('.differential-card');
        
        diffCards.forEach(function(card) {
            card.addEventListener('mouseenter', function() {
                const icon = this.querySelector('.differential-icon');
                if (icon) {
                    icon.style.transform = 'scale(1.1)';
                }
            });
            
            card.addEventListener('mouseleave', function() {
                const icon = this.querySelector('.differential-icon');
                if (icon) {
                    icon.style.transform = 'scale(1)';
                }
            });
        });
    }

    initDifferentialCardEffects();

    // ============================================
    // Button Ripple Effect
    // ============================================
    function initRippleEffect() {
        const buttons = document.querySelectorAll('.btn-primary, .btn-dark, .course-card-btn');
        
        buttons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const ripple = document.createElement('span');
                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.3);
                    width: 100px;
                    height: 100px;
                    left: ${x - 50}px;
                    top: ${y - 50}px;
                    transform: scale(0);
                    animation: ripple 0.6s ease-out;
                    pointer-events: none;
                `;
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(function() {
                    ripple.remove();
                }, 600);
            });
        });
    }

    // Add ripple animation keyframes
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

    initRippleEffect();

    // ============================================
    // Parallax Effect for Hero Blobs
    // ============================================
    function initParallaxEffect() {
        const blobs = document.querySelectorAll('.hero-blob');
        
        if (blobs.length === 0) return;
        
        let ticking = false;
        
        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    const scrolled = window.pageYOffset;
                    
                    blobs.forEach(function(blob, index) {
                        const speed = 0.1 + (index * 0.05);
                        const yPos = -(scrolled * speed);
                        blob.style.transform = 'translateY(' + yPos + 'px)';
                    });
                    
                    ticking = false;
                });
                
                ticking = true;
            }
        });
    }

    initParallaxEffect();

    // ============================================
    // Counter Animation for Stats
    // ============================================
    function animateCounter(element, target, suffix) {
        suffix = suffix || '';
        let current = 0;
        const increment = target / 50;
        const duration = 1500;
        const stepTime = duration / 50;
        
        const timer = setInterval(function() {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current) + suffix;
        }, stepTime);
    }

    function initCounterAnimation() {
        const statValues = document.querySelectorAll('.hero-stat-value');
        
        if (statValues.length === 0) return;
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const text = entry.target.textContent;
                    
                    if (text.includes('+')) {
                        const number = parseInt(text);
                        animateCounter(entry.target, number, '+');
                    } else if (text.includes('%')) {
                        const number = parseInt(text);
                        animateCounter(entry.target, number, '%');
                    }
                    
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        statValues.forEach(function(stat) {
            observer.observe(stat);
        });
    }

    initCounterAnimation();

    // ============================================
    // Active Navigation Link Highlight
    // ============================================
    function initActiveNavHighlight() {
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.navbar-links a, .mobile-menu a');
        
        if (sections.length === 0 || navLinks.length === 0) return;
        
        window.addEventListener('scroll', function() {
            let current = '';
            const scrollPosition = window.pageYOffset + 100;
            
            sections.forEach(function(section) {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.offsetHeight;
                
                if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                    current = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(function(link) {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });
    }

    initActiveNavHighlight();

    // ============================================
    // Lazy Loading Images
    // ============================================
    function initLazyLoading() {
        if (!('IntersectionObserver' in window)) {
            // Fallback: load all images immediately
            document.querySelectorAll('img[data-src]').forEach(function(img) {
                img.src = img.dataset.src;
            });
            return;
        }
        
        const imageObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(function(img) {
            imageObserver.observe(img);
        });
    }

    initLazyLoading();

    // ============================================
    // Performance: Debounce Function
    // ============================================
    function debounce(func, wait) {
        let timeout;
        return function executedFunction() {
            const context = this;
            const args = arguments;
            const later = function() {
                timeout = null;
                func.apply(context, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // ============================================
    // Initialize All Modules
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Landing Page RPPS - JavaScript initialized');
    });

})();
