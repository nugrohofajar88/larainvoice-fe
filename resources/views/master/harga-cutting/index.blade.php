@extends('layouts.app')
@section('title', 'Harga Cutting')
@section('page-title', 'Harga Cutting')

@section('content')
<div x-data="{
    showModal: false,
    editMode: false,
    loading: false,
    multiItems: [],
    selectedMachine: '',
    selectedMaterial: '',
    formData: {
        machine_type_id: '',
        plate_type_id: '',
        items: []
    },

    formatIDR(value) {
        if (!value) return '0';
        return new Intl.NumberFormat('id-ID').format(value);
    },

    addRow() {
        if (this.formData.items.length > 0) {
            const lastRow = this.formData.items[this.formData.items.length - 1];
            if (!lastRow.size_id) {
                window.toast.warning('Silakan pilih ukuran terlebih dahulu pada baris terakhir');
                return;
            }
            if (!lastRow.price_easy && !lastRow.price_medium && !lastRow.price_difficult && !lastRow.price_per_minute) {
                window.toast.warning('Setidaknya satu kolom harga harus terisi');
                return;
            }
        }

        this.formData.items.push({
            size_id: '',
            price_easy: 0,
            price_medium: 0,
            price_difficult: 0,
            price_per_minute: 0,
            discount_pct: 0
        });
    },

    removeRow(index) {
        if (this.formData.items.length > 1) {
            this.formData.items.splice(index, 1);
        } else {
            window.toast.warning('Minimal harus ada satu baris data');
        }
    },

    openCreate() {
        this.editMode = false;
        this.formData = {
            machine_type_id: '',
            plate_type_id: '',
            items: [{
                size_id: '',
                price_easy: 0,
                price_medium: 0,
                price_difficult: 0,
                price_per_minute: 0,
                discount_pct: 0
            }]
        };
        this.showModal = true;
    },

    async openEdit(item) {
        this.editMode = true;
        this.loading = true;
        this.showModal = true;
        this.selectedMachine = item.machine_type?.name || item.machine_type;
        this.selectedMaterial = item.plate_type?.name || item.plate_type;

        try {
            const response = await axios.get('{{ route('master.harga-cutting.multi') }}', {
                params: { 
                    machine_type_id: item.machine_type_id, 
                    plate_type_id: item.plate_type_id 
                }
            });
            this.multiItems = response.data.map(v => ({
                id: v.id,
                size_id: v.size_id,
                size_name: v.size?.name || v.size?.value || 'N/A',
                price_easy: v.price_easy,
                price_medium: v.price_medium,
                price_difficult: v.price_difficult,
                price_per_minute: v.price_per_minute,
                discount_pct: v.discount_pct || 0,
                is_active: !!v.is_active
            }));
        } catch (error) {
            window.toast.error('Gagal memuat daftar harga');
            this.showModal = false;
        } finally {
            this.loading = false;
        }
    },

    async submitForm() {
        // Validation for Create Mode
        if (!this.editMode) {
            if (!this.formData.machine_type_id || !this.formData.plate_type_id) {
                window.toast.warning('Pilih Jenis Mesin dan Material terlebih dahulu');
                return;
            }
            
            for (let i = 0; i < this.formData.items.length; i++) {
                const item = this.formData.items[i];
                if (!item.size_id) {
                    window.toast.warning(`Baris ${i + 1}: Ukuran belum dipilih`);
                    return;
                }
                if (!item.price_easy && !item.price_medium && !item.price_difficult && !item.price_per_minute) {
                    window.toast.warning(`Baris ${i + 1}: Setidaknya satu kolom harga harus terisi`);
                    return;
                }
            }
        }

        this.loading = true;
        try {
            if (this.editMode) {
                const response = await axios.put('{{ route('master.harga-cutting.batch') }}', { items: this.multiItems });
                window.toast.success(response.data.message || 'Harga berhasil diperbarui');
            } else {
                const response = await axios.post('{{ route('master.harga-cutting.store') }}', this.formData);
                window.toast.success(response.data.message || 'Data berhasil ditambahkan');
            }
            this.showModal = false;
            setTimeout(() => window.location.reload(), 500);
        } catch (error) {
            window.toast.error(error.response?.data?.message || 'Gagal menyimpan harga');
        } finally {
            this.loading = false;
        }
    }
}">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="page-title text-2xl font-bold text-slate-900 flex items-center gap-3">
                <svg class="w-8 h-8 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758L6.243 12m11.514 0L14.757 12"/></svg>
                Master Harga Jasa Cutting
            </h1>
            <p class="text-sm text-slate-500 mt-1">Konfigurasi tarif pemotongan berdasarkan mesin, material, dan kompleksitas desain</p>
        </div>
        @if(\App\Helpers\MenuHelper::hasPermission('cutting-price', 'create'))
        <button @click="openCreate()" class="btn btn-primary px-6 py-3 rounded-2xl shadow-xl shadow-brand/20 flex items-center gap-2 transition-all hover:scale-[1.02]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span class="font-bold">Tambah Harga Cutting</span>
        </button>
        @endif
    </div>

    <div class="card shadow-sm border-slate-200 overflow-hidden">
        <div class="card-header bg-slate-50/50 border-b border-slate-100 flex justify-between items-center py-4">
            <div class="flex items-center gap-2">
                <span class="w-2 h-6 bg-brand rounded-full"></span>
                <h2 class="font-bold text-slate-800">Daftar Harga Cutting</h2>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr class="bg-slate-100/30">
                        <th class="w-48 text-left py-4 px-6 font-bold text-slate-700">Tipe Mesin</th>
                        <th class="text-left py-4 px-6 font-bold text-slate-700">Jenis Plat</th>
                        <th class="text-left py-4 px-6 font-bold text-slate-700">Ukuran</th>
                        <th class="text-center py-4 px-6 font-bold text-green-700">EASY</th>
                        <th class="text-center py-4 px-6 font-bold text-yellow-700">MEDIUM</th>
                        <th class="text-center py-4 px-6 font-bold text-red-700">DIFFICULT</th>
                        <th class="text-center py-4 px-6 font-bold text-sky-700">PER MENIT</th>
                        <th class="text-center py-4 px-6 font-bold text-orange-600">DISC</th>
                        <th class="text-center py-4 px-6 font-bold text-slate-700">Status</th>
                        <th class="w-20 text-center py-4 px-6 font-bold text-slate-700">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($prices as $p)
                    <tr class="hover:bg-slate-50/80 transition-colors {{ !($p['is_active'] ?? true) ? 'bg-slate-50/50 grayscale-[0.5]' : '' }}">
                        <td class="px-6 py-4">
                            <span class="font-semibold text-slate-700">{{ $p['machine_type']['name'] ?? $p['machine_type'] }}</span>
                        </td>
                        <td class="px-6 py-4 text-slate-600 font-medium">{{ $p['plate_type']['name'] ?? $p['plate_type'] }}</td>
                        <td class="px-6 py-4">
                            <span class="badge badge-outline text-xs px-2 py-0.5">{{ $p['size']['name'] ?? ($p['size']['value'] ?? $p['size']) }}</span>
                        </td>
                        <td class="px-6 py-4 text-center font-mono text-sm text-green-600">Rp {{ number_format($p['price_easy'] ?? ($p['easy'] ?? 0), 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center font-mono text-sm text-yellow-600">Rp {{ number_format($p['price_medium'] ?? ($p['medium'] ?? 0), 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center font-mono text-sm text-red-600">Rp {{ number_format($p['price_difficult'] ?? ($p['difficult'] ?? 0), 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center font-mono text-sm text-sky-600">Rp {{ number_format($p['price_per_minute'] ?? ($p['per_minute'] ?? 0), 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center font-mono text-sm text-orange-600">{{ number_format($p['discount_pct'] ?? 0, 1) }}%</td>
                        <td class="px-6 py-4 text-center">
                            @if($p['is_active'] ?? true)
                            <span class="badge badge-success text-[10px]">Aktif</span>
                            @else
                            <span class="badge badge-error text-[10px]">Non-Aktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(\App\Helpers\MenuHelper::hasPermission('cutting-price', 'edit'))
                            <button type="button" @click="openEdit({{ json_encode($p) }})" class="p-2 text-slate-400 hover:text-brand hover:bg-brand/5 rounded-lg transition-all" title="Edit Massal Ukuran">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>
            <div class="relative inline-block w-full max-w-6xl p-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900" x-text="editMode ? 'Multi-Edit Harga Cutting' : 'Tambah Harga Cutting Baru'"></h3>
                        <p class="text-xs text-slate-500 mt-1" x-text="editMode ? 'Mengatur tarif pemotongan untuk semua ukuran secara massal.' : 'Daftarkan kombinasi mesin, material, dan harga baru secara massal.'"></p>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 p-2 hover:bg-slate-100 rounded-xl transition-all"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <!-- Create Mode Form -->
                <template x-if="!editMode">
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-6 rounded-3xl border border-slate-200">
                            <div class="form-group pb-0 mb-0">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Jenis Mesin</label>
                                <select x-model="formData.machine_type_id" class="form-input !py-3 !rounded-xl !bg-white">
                                    <option value="">Pilih Mesin</option>
                                    @foreach($machineTypes as $mt)
                                        <option value="{{ $mt['id'] }}">{{ $mt['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group pb-0 mb-0">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Jenis Material (Plat)</label>
                                <select x-model="formData.plate_type_id" class="form-input !py-3 !rounded-xl !bg-white">
                                    <option value="">Pilih Material</option>
                                    @foreach($plateTypes as $pt)
                                        <option value="{{ $pt['id'] }}">{{ $pt['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="overflow-hidden border border-slate-200 rounded-2xl shadow-sm">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Ukuran</th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-green-600 uppercase tracking-wider text-center">Easy (Rp)</th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-yellow-600 uppercase tracking-wider text-center">Medium (Rp)</th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-red-600 uppercase tracking-wider text-center">Diff (Rp)</th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-sky-600 uppercase tracking-wider text-center">Mnt (Rp)</th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-orange-600 uppercase tracking-wider text-center w-20">Disc (%)</th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center w-20">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="(item, index) in formData.items" :key="index">
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-3 py-2">
                                                <select x-model="item.size_id" class="w-full px-2 py-2 border-slate-200 rounded-lg text-xs focus:ring-brand">
                                                    <option value="">Pilih Ukuran</option>
                                                    @foreach($sizes as $sz)
                                                        <option value="{{ $sz['id'] }}">{{ $sz['name'] ?? $sz['value'] }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="text" :value="formatIDR(item.price_easy)" @input="item.price_easy = $event.target.value.replace(/[^0-9]/g, ''); $event.target.value = formatIDR(item.price_easy)" class="w-full px-2 py-2 text-right font-mono text-xs border-slate-200 focus:ring-brand rounded-lg">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="text" :value="formatIDR(item.price_medium)" @input="item.price_medium = $event.target.value.replace(/[^0-9]/g, ''); $event.target.value = formatIDR(item.price_medium)" class="w-full px-2 py-2 text-right font-mono text-xs border-slate-200 focus:ring-brand rounded-lg">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="text" :value="formatIDR(item.price_difficult)" @input="item.price_difficult = $event.target.value.replace(/[^0-9]/g, ''); $event.target.value = formatIDR(item.price_difficult)" class="w-full px-2 py-2 text-right font-mono text-xs border-slate-200 focus:ring-brand rounded-lg">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="text" :value="formatIDR(item.price_per_minute)" @input="item.price_per_minute = $event.target.value.replace(/[^0-9]/g, ''); $event.target.value = formatIDR(item.price_per_minute)" class="w-full px-2 py-2 text-right font-mono text-xs border-slate-200 focus:ring-brand rounded-lg">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="text" :value="item.discount_pct" @input="item.discount_pct = $event.target.value.replace(/[^0-9.]/g, ''); $event.target.value = item.discount_pct" class="w-full px-1 py-2 text-center font-mono text-xs border-slate-200 focus:ring-brand rounded-lg" placeholder="0">
                                            </td>
                                            <td class="px-3 py-2 text-center flex items-center justify-center gap-2 mt-1">
                                                <button type="button" @click="addRow()" class="p-1.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors shadow-sm">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                </button>
                                                <button type="button" @click="removeRow(index)" class="p-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors shadow-sm">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <!-- Edit Mode Table -->
                <template x-if="editMode">
                    <div>
                        <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100 mb-6">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tipe Mesin</label>
                                <p class="text-slate-800 font-semibold" x-text="selectedMachine"></p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Jenis Material</label>
                                <p class="text-slate-800 font-semibold" x-text="selectedMaterial"></p>
                            </div>
                        </div>

                        <div class="overflow-hidden border border-slate-200 rounded-2xl shadow-sm mb-8">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Size</th>
                                        <th class="px-4 py-3 text-xs font-bold text-green-600 uppercase tracking-wider text-center">Easy (Rp)</th>
                                        <th class="px-4 py-3 text-xs font-bold text-yellow-600 uppercase tracking-wider text-center">Medium (Rp)</th>
                                        <th class="px-4 py-3 text-xs font-bold text-red-600 uppercase tracking-wider text-center">Diff (Rp)</th>
                                        <th class="px-4 py-3 text-xs font-bold text-sky-600 uppercase tracking-wider text-center">Mnt (Rp)</th>
                                        <th class="px-4 py-3 text-xs font-bold text-orange-600 uppercase tracking-wider text-center w-20">Disc</th>
                                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-center w-20">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 italic-last-row">
                                    <template x-for="item in multiItems" :key="item.id">
                                        <tr class="hover:bg-slate-50/50 transition-colors" :class="!item.is_active ? 'bg-slate-50/20 grayscale-[0.5]' : ''">
                                            <td class="px-4 py-3">
                                                <span class="badge badge-outline text-xs" :class="item.is_active ? 'border-brand text-brand' : ''" x-text="item.size_name"></span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="text" :value="formatIDR(item.price_easy)" @input="item.price_easy = $event.target.value.replace(/[^0-9]/g, ''); $event.target.value = formatIDR(item.price_easy)" class="w-full px-2 py-2 text-right font-mono text-xs border-0 focus:ring-2 focus:ring-brand rounded-lg bg-transparent hover:bg-white transition-all shadow-sm" :disabled="!item.is_active">
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="text" :value="formatIDR(item.price_medium)" @input="item.price_medium = $event.target.value.replace(/[^0-9]/g, ''); $event.target.value = formatIDR(item.price_medium)" class="w-full px-2 py-2 text-right font-mono text-xs border-0 focus:ring-2 focus:ring-brand rounded-lg bg-transparent hover:bg-white transition-all shadow-sm" :disabled="!item.is_active">
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="text" :value="formatIDR(item.price_difficult)" @input="item.price_difficult = $event.target.value.replace(/[^0-9]/g, ''); $event.target.value = formatIDR(item.price_difficult)" class="w-full px-2 py-2 text-right font-mono text-xs border-0 focus:ring-2 focus:ring-brand rounded-lg bg-transparent hover:bg-white transition-all shadow-sm" :disabled="!item.is_active">
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="text" :value="formatIDR(item.price_per_minute)" @input="item.price_per_minute = $event.target.value.replace(/[^0-9]/g, ''); $event.target.value = formatIDR(item.price_per_minute)" class="w-full px-2 py-2 text-right font-mono text-xs border-0 focus:ring-2 focus:ring-brand rounded-lg bg-transparent hover:bg-white transition-all shadow-sm" :disabled="!item.is_active">
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="text" :value="item.discount_pct" @input="item.discount_pct = $event.target.value.replace(/[^0-9.]/g, ''); $event.target.value = item.discount_pct" class="w-full px-1 py-2 text-center font-mono text-xs border-0 focus:ring-2 focus:ring-brand rounded-lg bg-transparent hover:bg-white transition-all shadow-sm" :disabled="!item.is_active" placeholder="0">
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button type="button" @click="item.is_active = !item.is_active" 
                                                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none shadow-inner"
                                                    :class="item.is_active ? 'bg-brand' : 'bg-slate-300'">
                                                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform shadow-sm"
                                                        :class="item.is_active ? 'translate-x-5' : 'translate-x-0.5'"></span>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" @click="showModal = false" class="btn btn-outline px-8 py-3 rounded-2xl">Batal</button>
                    <button type="button" @click="submitForm()" class="btn btn-primary px-10 py-3 rounded-2xl shadow-xl shadow-brand/20 flex items-center justify-center min-w-[200px]" :disabled="loading">
                        <span x-show="!loading" x-text="editMode ? 'Simpan Perubahan' : 'Tambahkan Data'"></span>
                        <svg x-show="loading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
