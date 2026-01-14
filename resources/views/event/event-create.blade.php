<div>
    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Сообщение об успехе -->
            @if (session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            <form wire:submit.prevent="save">
                <!-- Имя -->
                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('ui.name') }} <span class="text-danger">*</span></label>
                    <input type="text" 
                           id="name"
                           wire:model.lazy="name"
                           class="w-100 border rounded-lg block disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5">
                    @error('name')
                        <div class="text-danger text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label for="description" class="form-label">{{ __('ui.description') }} <span class="text-danger">*</span></label>
                    <textarea 
                           id="description"
                           wire:model.lazy="description"
                           rows="4"
                           class="w-100 border rounded-lg block disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5"></textarea>
                    @error('description')
                        <div class="text-danger text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Resource -->
                <div class="mb-3">
                    <label for="resource_id" class="form-label">Ресурс</label>
                    <div class="relative">
                        <input 
                            type="text" 
                            wire:model.live="resourceSearch"
                            placeholder="Поиск ресурса..."
                            class="w-100 border rounded-lg block disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5">
                        @if($resourceSearch && count($resources) > 0)
                            <div class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-zinc-200 dark:border-white/10 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                @foreach($resources as $resource)
                                    <div 
                                        wire:click="$set('resource_id', {{ $resource->id }}); $set('resourceSearch', 'ID: {{ $resource->id }}')"
                                        class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                                    >
                                        ID: {{ $resource->id }} 
                                        @if($resource->type)
                                            - {{ __('moonshine.types.values.' . $resource->type->name) }}
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @if($resource_id)
                        @php
                            $selectedResource = $resources->firstWhere('id', $resource_id);
                        @endphp
                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Выбран: ID {{ $resource_id }}
                            @if($selectedResource && $selectedResource->type)
                                - {{ __('moonshine.types.values.' . $selectedResource->type->name) }}
                            @endif
                            <button 
                                type="button" 
                                wire:click="$set('resource_id', null); $set('resourceSearch', '')"
                                class="ml-2 text-red-600 hover:text-red-800"
                            >
                                ✕
                            </button>
                        </div>
                    @endif
                    @error('resource_id')
                        <div class="text-danger text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Room -->
                <div class="mb-3">
                    <label for="room_id" class="form-label">Комната</label>
                    <div class="relative">
                        <input 
                            type="text" 
                            wire:model.live="roomSearch"
                            placeholder="Поиск комнаты..."
                            class="w-100 border rounded-lg block disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5">
                        @if($roomSearch && count($rooms) > 0)
                            <div class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-zinc-200 dark:border-white/10 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                @foreach($rooms as $room)
                                    <div 
                                        wire:click="$set('room_id', {{ $room->id }}); $set('roomSearch', '{{ $room->name }}')"
                                        class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                                    >
                                        {{ $room->name }}
                                        @if($room->resource)
                                            ({{ __('moonshine.types.values.' . $room->resource->type->name ?? '') }})
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @if($room_id)
                        @php
                            $selectedRoom = $rooms->firstWhere('id', $room_id);
                        @endphp
                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Выбрана: {{ $selectedRoom->name ?? 'ID ' . $room_id }}
                            <button 
                                type="button" 
                                wire:click="$set('room_id', null); $set('roomSearch', '')"
                                class="ml-2 text-red-600 hover:text-red-800"
                            >
                                ✕
                            </button>
                        </div>
                    @endif
                    @error('room_id')
                        <div class="text-danger text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Start At -->
                <div class="mb-3">
                    <label for="start_at" class="form-label">Начало</label>
                    <input 
                        type="datetime-local" 
                        id="start_at"
                        wire:model.lazy="start_at"
                        class="w-100 border rounded-lg block disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5">
                    @error('start_at')
                        <div class="text-danger text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- End At -->
                <div class="mb-3">
                    <label for="end_at" class="form-label">Конец</label>
                    <input 
                        type="datetime-local" 
                        id="end_at"
                        wire:model.lazy="end_at"
                        class="w-100 border rounded-lg block disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5">
                    @error('end_at')
                        <div class="text-danger text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Active -->
                <div class="mb-3 flex items-center" x-data="{ 
                        active: @entangle('active'),
                        activeText: '',
                        notActiveText: '',
                        init() {
                            this.activeText = this.$el.dataset.active || '{{ __('ui.active') }}';
                            this.notActiveText = this.$el.dataset.notactive || '{{ __('ui.notactive') }}';
                        }
                    }"
                    data-active="{{ __('ui.active') }}" 
                    data-notactive="{{ __('ui.notactive') }}">
                    <input 
                        type="checkbox" 
                        wire:model.live="active"
                        class="checkboxx ml-2 w-5 h-5 appearance-none border cursor-pointer border-gray-300 rounded-md mr-2 hover:border-indigo-500 hover:bg-indigo-100 checked:bg-no-repeat checked:bg-center checked:border-indigo-500 checked:bg-indigo-100"
                        id="active"
                    >
                    <label for="active" 
                        class="text-sm ml-2 font-norma cursor-pointer"
                        :class="active ? 'dark:text-white text-black' : 'text-gray-600'">
                        <span x-text="active ? activeText : notActiveText"></span>
                    </label>
                    
                    @error('active')
                        <div class="text-danger text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Кнопки -->
                <div class="d-flex justify-content-between">
                    <button type="submit" 
                            wire:loading.attr="disabled"
                            class="btn btn-primary p-2 border rounded-lg disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5 hover:bg-zinc-800/5 hover:text-zinc-800 dark:hover:bg-white/[7%] dark:hover:text-white">
                        <span wire:loading.remove wire:target="save">
                            <i class="fas fa-save me-1"></i> Сохранить
                        </span>
                        <span wire:loading wire:target="save">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            Сохранение...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:load', function () {
        // Скрываем сообщение об успехе через 3 секунды
        Livewire.on('profile-updated', () => {
            console.log('profile-updated')
            setTimeout(() => {
                const alert = document.querySelector('.alert-success');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 3000);
        });
        
        // Инициализация маски для телефона (если используется)
        const phoneInput = document.getElementById('phone');
        if (phoneInput) {
            // Здесь можно подключить библиотеку mask.js или inputmask
            // Пример: new Inputmask("+7 (999) 999-99-99").mask(phoneInput);
        }
    });
</script>
@endpush