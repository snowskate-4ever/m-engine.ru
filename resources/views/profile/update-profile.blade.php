<div>
    <div class="card">
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
                    <label for="name" class="form-label">{{ __('ui.username') }} <span class="text-danger">*</span></label>
                    <input type="text" 
                           id="name"
                           wire:model.lazy="name"
                           class="w-100 border rounded-lg block disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">{{ __('ui.email') }} <span class="text-danger">*</span></label>
                    <input type="email" 
                           id="email"
                           wire:model.lazy="email"
                           class="w-100 border rounded-lg block disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Phone -->
                <div class="mb-3">
                    <label for="phone" class="form-label">{{ __('ui.phone') }}</label>
                    <input type="phone" 
                           id="phone"
                           wire:model.lazy="phone"
                           class="w-100 border rounded-lg block disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                    <button type="submit" class="bg-teal-400"> Сохранить </button>

                <!-- Кнопки -->
                <div class="d-flex justify-content-between">
                    <button type="submit" 
                            wire:loading.attr="disabled"
                            class="btn btn-primary p-2 border rounded-lg appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] bg-teal text-zinc-700 placeholder-zinc-400  dark:text-zinc-300 shadow-xs border-zinc-200 border-b-zinc-300/80 hover:bg-zinc-800/5 hover:text-zinc-800 dark:hover:bg-white/[7%] dark:hover:text-white">
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