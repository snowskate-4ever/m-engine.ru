// Carousel functionality
document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.getElementById('stat_cards');
    if (carousel) {
        const prevButton = carousel.querySelector('[data-carousel-prev]');
        const nextButton = carousel.querySelector('[data-carousel-next]');
        const carouselWrapper = carousel.querySelector('div.flex.h-56.md\\:h-96');
        const slides = carousel.querySelectorAll('div.flex.w-full');
        const indicators = carousel.querySelectorAll('[data-carousel-slide-to]');
        
        if (prevButton && nextButton && carouselWrapper && slides.length > 0) {
            let currentIndex = 0;
            const slideCount = slides.length;
            
            // Update slide position
            function updateSlidePosition(index) {
                // Calculate the transform value to show the current slide
                const translateX = -(index * 100);
                carouselWrapper.style.transform = `translateX(${translateX}%)`;
                
                // Update indicators
                indicators.forEach((indicator, i) => {
                    if (i === index) {
                        indicator.classList.remove('bg-gray-300');
                        indicator.classList.add('bg-blue-600');
                    } else {
                        indicator.classList.remove('bg-blue-600');
                        indicator.classList.add('bg-gray-300');
                    }
                });
            }
            
            // Next slide
            nextButton.addEventListener('click', function() {
                currentIndex = (currentIndex + 1) % slideCount;
                updateSlidePosition(currentIndex);
            });
            
            // Previous slide
            prevButton.addEventListener('click', function() {
                currentIndex = (currentIndex - 1 + slideCount) % slideCount;
                updateSlidePosition(currentIndex);
            });
            
            // Indicator click
            indicators.forEach((indicator, index) => {
                indicator.addEventListener('click', function() {
                    currentIndex = index;
                    updateSlidePosition(currentIndex);
                });
            });
            
            // Auto-scroll functionality
            let scrollInterval;
            const autoScroll = function() {
                scrollInterval = setInterval(() => {
                    currentIndex = (currentIndex + 1) % slideCount;
                    updateSlidePosition(currentIndex);
                }, 5000); // Scroll every 5 seconds
            };
            
            // Start auto-scroll
            autoScroll();
            
            // Pause auto-scroll on hover
            carousel.addEventListener('mouseenter', function() {
                clearInterval(scrollInterval);
            });
            
            carousel.addEventListener('mouseleave', function() {
                autoScroll();
            });
            
            // Initialize first indicator as active
            if (indicators.length > 0) {
                indicators[0].classList.add('bg-blue-600');
            }
        }
    }
});