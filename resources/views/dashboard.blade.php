<x-layouts.second_level_layout :title="__('ui.dashboard')" :buttons="$buttons">
    <div class="w-full">
        @if(!empty($data['stat_cards']))
        @php
            $items = $data['stat_cards'];
            $chunkSize = 3;
            $itemsArray = [];
            $itemsKeys = [];
            foreach($items as $key => $value) {
                $itemsArray[] = $value;
                $itemsKeys[] = $key;
            }
            $chunks = array_chunk($itemsArray, $chunkSize);
            $chunksKeys = array_chunk($itemsKeys, $chunkSize);
        @endphp
        <div id="stat_cards" class="relative w-full overflow-hidden rounded-xl" data-carousel="slide" style="overflow: hidden !important;">
            <!-- Carousel wrapper -->
            <div id="carousel-wrapper" class="flex h-56 md:h-96 transition-transform duration-300 ease-in-out" style="transform: translateX(0%);">
                <!-- Group items into sets of 3 -->
                @foreach($chunks as $chunkIndex => $chunk)
                    <div class="flex w-full flex-shrink-0 gap-4" data-slide="{{ $chunkIndex }}" style="min-width: 100%; width: 100%;">
                        @foreach($chunk as $index => $card)
                            @php
                                $key = $chunksKeys[$chunkIndex][$index];
                            @endphp
                            <div class="flex-1 border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800 rounded-lg" id="{{ $key }}">
                                Статистика по {{ $key }}<br>
                                Всего: {{ $key }} - {{ $card['count_all'] }}<br>
                                Мои: {{ $key }} - {{ $card['my_items'] }}
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
            <!-- Slider indicators -->
            @if(count($chunks) > 1)
            <div class="absolute z-30 flex -translate-x-1/2 bottom-5 left-1/2 space-x-3 rtl:space-x-reverse">
                @foreach($chunks as $chunkIndex => $chunk)
                    <button type="button" class="w-3 h-3 rounded-full bg-gray-300 transition-colors duration-200 {{ $chunkIndex === 0 ? 'bg-blue-600' : '' }}" aria-current="{{ $chunkIndex === 0 ? 'true' : 'false' }}" aria-label="Slide {{ $chunkIndex }}" data-carousel-slide-to="{{ $chunkIndex }}"></button>
                @endforeach
            </div>
            @endif
            <!-- Slider controls -->
            @if(count($chunks) > 1)
            <button type="button" class="absolute top-0 start-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none" data-carousel-prev>
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 dark:bg-gray-800/30 group-hover:bg-white/50 dark:group-hover:bg-gray-800/60 group-focus:ring-4 group-focus:ring-white dark:group-focus:ring-gray-800/70 group-focus:outline-none">
                    <svg class="w-5 h-5 text-white rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
                    <span class="sr-only">Previous</span>
                </span>
            </button>
            <button type="button" class="absolute top-0 end-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none" data-carousel-next>
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 dark:bg-gray-800/30 group-hover:bg-white/50 dark:group-hover:bg-gray-800/60 group-focus:ring-4 group-focus:ring-white dark:group-focus:ring-gray-800/70 group-focus:outline-none">
                    <svg class="w-5 h-5 text-white rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                    <span class="sr-only">Next</span>
                </span>
            </button>
            @endif
        </div>


        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const carousel = document.getElementById('stat_cards');
            if (carousel) {
                const prevButton = carousel.querySelector('[data-carousel-prev]');
                const nextButton = carousel.querySelector('[data-carousel-next]');
                const carouselWrapper = document.getElementById('carousel-wrapper');
                const slides = carousel.querySelectorAll('[data-slide]');
                const indicators = carousel.querySelectorAll('[data-carousel-slide-to]');
                
                if (carouselWrapper && slides.length > 0) {
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
                                indicator.setAttribute('aria-current', 'true');
                            } else {
                                indicator.classList.remove('bg-blue-600');
                                indicator.classList.add('bg-gray-300');
                                indicator.setAttribute('aria-current', 'false');
                            }
                        });
                    }
                    
                    // Next slide (rotate right)
                    if (nextButton) {
                        nextButton.addEventListener('click', function() {
                            currentIndex = (currentIndex + 1) % slideCount;
                            updateSlidePosition(currentIndex);
                        });
                    }
                    
                    // Previous slide (rotate left)
                    if (prevButton) {
                        prevButton.addEventListener('click', function() {
                            currentIndex = (currentIndex - 1 + slideCount) % slideCount;
                            updateSlidePosition(currentIndex);
                        });
                    }
                    
                    // Indicator click
                    indicators.forEach((indicator, index) => {
                        indicator.addEventListener('click', function() {
                            currentIndex = index;
                            updateSlidePosition(currentIndex);
                        });
                    });
                }
            }
        });
        </script>
        @endif
    </div>
</x-layouts.second_level_layout>