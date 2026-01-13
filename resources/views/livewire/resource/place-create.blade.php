<div>
    <form wire:submit.prevent="save" x-data="{ startAt: @entangle('start_at') }">
        <!-- Кнопка назад в начале формы -->
        <div class="mb-4">
            <a href="{{ $type_id ? route('resources.by_type', ['type_id' => $type_id]) : route('resources') }}" 
               class="btn btn-primary p-2 border rounded-lg disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5 hover:bg-zinc-800/5 hover:text-zinc-800 dark:hover:bg-white/[7%] dark:hover:text-white">
                <i class="fas fa-arrow-left me-1"></i> {{ __('ui.back') }}
            </a>
        </div>

        <input type="hidden" wire:model="type_id" value="{{ $type_id }}">

        <!-- Название -->
        <div class="mb-3">
            <label for="name" class="form-label">{{ __('moonshine.resources.name') }} <span class="text-danger">*</span></label>
            <input type="text" 
                   id="name"
                   wire:model.blur="name"
                   class="w-100 border rounded-lg block disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5"
                   required>
            @error('name')
                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Описание -->
        <div class="mb-3">
            <label for="description" class="form-label">{{ __('moonshine.resources.description') }} <span class="text-danger">*</span></label>
            <textarea 
                   id="description"
                   wire:model.blur="description"
                   rows="4"
                   class="w-100 border rounded-lg block disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5"
                   required></textarea>
            @error('description')
                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Дата начала -->
        <div class="mb-3">
            <label for="start_at" class="form-label">{{ __('moonshine.resources.start_at') }} <span class="text-danger">*</span></label>
            <input type="date" 
                   id="start_at"
                   wire:model.blur="start_at"
                   min="{{ date('Y-m-d') }}"
                   class="w-100 border rounded-lg block disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5"
                   required>
            @error('start_at')
                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Дата окончания -->
        <div class="mb-3">
            <label for="end_at" class="form-label">{{ __('moonshine.resources.end_at') }}</label>
            <input type="date" 
                   id="end_at"
                   wire:model.blur="end_at"
                   x-bind:min="startAt || '{{ date('Y-m-d') }}'"
                   class="w-100 border rounded-lg block disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5">
            @error('end_at')
                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
            @enderror
            <small class="text-gray-500 text-xs mt-1 block">{{ __('ui.optional') }}</small>
        </div>

        <!-- Активность -->
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
                class="checkboxx ml-2 w-5 h-5 appearance-none border cursor-pointer border-gray-300 rounded-md mr-2 hover:border-indigo-500 hover:bg-indigo-100 checked:bg-no-repeat checked:bg-center checked:border-indigo-500 checked:bg-indigo-100"
                x-model="active"
                id="active"
                wire:model="active"
            >
            <label for="active" 
                class="text-sm ml-2 font-normal cursor-pointer"
                :class="active ? 'dark:text-white text-black' : 'text-gray-600'">
                <span x-text="active ? activeText : notActiveText"></span>
            </label>
            
            @error('active')
                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Кнопка сохранить в конце формы -->
        <div class="d-flex justify-content-end mt-4">
            <button type="submit" 
                    wire:loading.attr="disabled"
                    class="btn btn-primary p-2 border rounded-lg disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-3 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5 hover:bg-zinc-800/5 hover:text-zinc-800 dark:hover:bg-white/[7%] dark:hover:text-white">
                <span wire:loading.remove wire:target="save">
                    <i class="fas fa-save me-1"></i> {{ __('ui.save') }}
                </span>
                <span wire:loading wire:target="save">
                    <span class="spinner-border spinner-border-sm me-1"></span>
                    {{ __('ui.loading') }}
                </span>
            </button>
        </div>
    </form>
</div>
