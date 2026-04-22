@props([
    'name',
    'label' => '',
    'placeholder' => 'Cari data...',
    'options' => [],
    'selected' => null,
    'required' => false,
    'valueKey' => 'id',
    'labelKey' => 'name',
    'descriptionKey' => null,
])

<div
    x-data="searchableSelect({
        fieldName: '{{ $name }}',
        options: {{ \Illuminate\Support\Js::from($options) }},
        selected: {{ \Illuminate\Support\Js::from($selected) }},
        valueKey: '{{ $valueKey }}',
        labelKey: '{{ $labelKey }}',
        descriptionKey: {{ \Illuminate\Support\Js::from($descriptionKey) }},
        placeholder: '{{ $placeholder }}'
    })"
    x-init="init()"
    class="space-y-2"
>
    @if($label)
        <label class="form-label text-slate-600 font-semibold text-xs uppercase tracking-wider">{{ $label }}</label>
    @endif

    <input type="hidden" x-ref="hiddenInput" name="{{ $name }}" :value="selectedValue" {{ $required ? 'required' : '' }}>

    <div class="relative" @keydown.escape.window="open = false" @click.outside="open = false">
        <button
            type="button"
            @click="toggle()"
            class="w-full form-input text-left flex items-center justify-between gap-3"
            :class="open ? 'border-brand ring-1 ring-brand' : ''"
        >
            <div class="min-w-0">
                <div class="font-medium text-slate-800 truncate" x-text="selectedLabel"></div>
                <div
                    x-show="selectedDescription"
                    class="text-xs text-slate-400 truncate"
                    x-text="selectedDescription"
                ></div>
            </div>
            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div
            x-show="open"
            x-transition
            class="absolute z-40 mt-2 w-full rounded-xl border border-slate-200 bg-white shadow-2xl overflow-hidden"
            style="display: none;"
        >
            <div class="p-3 border-b border-slate-100">
                <input
                    type="text"
                    x-model="query"
                    x-ref="search"
                    class="form-input"
                    :placeholder="placeholder"
                >
            </div>

            <div class="max-h-64 overflow-y-auto">
                <template x-for="option in filteredOptions" :key="option[valueKey]">
                    <button
                        type="button"
                        @click="choose(option)"
                        class="w-full px-4 py-3 text-left hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-b-0"
                    >
                        <div class="font-medium text-slate-800" x-text="option[labelKey]"></div>
                        <div
                            x-show="descriptionKey && option[descriptionKey]"
                            class="text-xs text-slate-400 mt-0.5"
                            x-text="option[descriptionKey]"
                        ></div>
                    </button>
                </template>

                <div x-show="filteredOptions.length === 0" class="px-4 py-6 text-sm text-slate-400 text-center">
                    Tidak ada data yang cocok.
                </div>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            function searchableSelect(config) {
                return {
                    open: false,
                    query: '',
                    fieldName: config.fieldName || '',
                    options: config.options || [],
                    selectedValue: config.selected ?? '',
                    valueKey: config.valueKey || 'id',
                    labelKey: config.labelKey || 'name',
                    descriptionKey: config.descriptionKey || null,
                    placeholder: config.placeholder || 'Cari data...',

                    init() {
                        window.addEventListener('searchable-select:update', (event) => {
                            if (event.detail?.name !== this.fieldName) {
                                return;
                            }

                            if (Array.isArray(event.detail?.options)) {
                                this.options = event.detail.options;
                            }

                            if (Object.prototype.hasOwnProperty.call(event.detail || {}, 'selected')) {
                                this.selectedValue = event.detail.selected ?? '';
                            }
                        });
                    },

                    get filteredOptions() {
                        const keyword = this.query.toLowerCase().trim();

                        if (!keyword) {
                            return this.options;
                        }

                        return this.options.filter((option) => {
                            const label = String(option[this.labelKey] || '').toLowerCase();
                            const description = this.descriptionKey
                                ? String(option[this.descriptionKey] || '').toLowerCase()
                                : '';

                            return label.includes(keyword) || description.includes(keyword);
                        });
                    },

                    get selectedOption() {
                        return this.options.find((option) => String(option[this.valueKey]) === String(this.selectedValue)) || null;
                    },

                    get selectedLabel() {
                        return this.selectedOption?.[this.labelKey] || 'Pilih data';
                    },

                    get selectedDescription() {
                        return this.descriptionKey ? (this.selectedOption?.[this.descriptionKey] || '') : '';
                    },

                    toggle() {
                        this.open = !this.open;

                        if (this.open) {
                            this.$nextTick(() => this.$refs.search?.focus());
                        }
                    },

                    choose(option) {
                        this.selectedValue = option[this.valueKey];
                        this.open = false;
                        this.query = '';
                        this.$nextTick(() => this.dispatchSelectionChange());
                    },

                    dispatchSelectionChange() {
                        const input = this.$refs.hiddenInput;

                        if (!input) {
                            return;
                        }

                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    },
                };
            }
        </script>
    @endpush
@endonce
