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
            
            @if($component)
                <!-- Загружаем соответствующий компонент формы -->
                @livewire($component, ['type_id' => $type_id], key('resource-form-' . $type_id))
            @else
                <!-- Если тип не выбран или не найден компонент, показываем выбор типа -->
                <div class="mb-4">
                    <a href="{{ route('resources') }}" 
                       class="btn btn-primary p-2 border rounded-lg disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5 hover:bg-zinc-800/5 hover:text-zinc-800 dark:hover:bg-white/[7%] dark:hover:text-white">
                        <i class="fas fa-arrow-left me-1"></i> {{ __('ui.back') }}
                    </a>
                </div>
                
                <div class="mb-3">
                    <label for="type_id" class="form-label">{{ __('moonshine.resources.resource_type') }} <span class="text-danger">*</span></label>
                    <select 
                           id="type_id"
                           wire:model.blur="type_id"
                           class="w-100 border rounded-lg block disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5"
                           required>
                        <option value="">{{ __('ui.select') ?: 'Выберите...' }}</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}">{{ __('moonshine.types.values.' . $type->name) ?: $type->name }}</option>
                        @endforeach
                    </select>
                    @error('type_id')
                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    {{ __('ui.select_type_to_continue') ?: 'Выберите тип ресурса для продолжения' }}
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:load', function () {
        // Скрываем сообщение об успехе через 3 секунды
        Livewire.on('resource-created', () => {
            setTimeout(() => {
                const alert = document.querySelector('.alert-success');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 3000);
        });
    });
</script>
@endpush