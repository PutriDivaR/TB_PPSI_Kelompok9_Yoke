<!-- Profile Edit Modal / Modal Edit Profil Pengguna -->
<div x-cloak 
     x-show="showProfileModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="showProfileModal = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto"
     style="display: none;">

    <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-100 transform transition-all my-8"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.away="showProfileModal = false"
         x-data="{
             previewUrl: '{{ Auth::check() ? Auth::user()->foto_profil_url : '' }}',
             showPassword: false,
             showConfirmPassword: false,
             password: '',
             confirmPassword: '',
             clientError: '',
             handlePhotoChange(event) {
                 const file = event.target.files[0];
                 if (file) {
                     if (file.size > 2 * 1024 * 1024) {
                         this.clientError = 'Ukuran berkas foto terlalu besar (maksimal 2MB).';
                         event.target.value = '';
                         return;
                     }
                     this.clientError = '';
                     this.previewUrl = URL.createObjectURL(file);
                 }
             },
             validateAndSubmit(event) {
                 this.clientError = '';
                 if (this.password.length > 0) {
                     if (this.password.length < 4) {
                         this.clientError = 'Kata sandi baru minimal harus 4 karakter.';
                         return;
                     }
                     if (this.password !== this.confirmPassword) {
                         this.clientError = 'Kata sandi baru dan konfirmasi kata sandi tidak cocok. Harap periksa kembali.';
                         return;
                     }
                 }
                 // Submit the form cleanly
                 event.target.submit();
             }
         }">

        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Edit Profil Saya</h3>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui nama, foto profil, dan kata sandi akun Anda.</p>
            </div>
            <button type="button" @click="showProfileModal = false; clientError = ''" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        {{-- Alert Kesalahan di Dalam Modal (Instan Tanpa Menutup Modal) --}}
        <div x-cloak x-show="clientError" 
             x-transition 
             class="mb-4 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold px-4 py-3 rounded-xl flex items-start space-x-2 shadow-2xs">
            <i class="fa-solid fa-circle-exclamation text-red-600 text-sm mt-0.5 flex-shrink-0"></i>
            <span x-text="clientError" class="leading-relaxed"></span>
        </div>

        @if($errors->hasAny(['nama', 'kata_sandi', 'kata_sandi_confirmation', 'foto_profil']))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold px-4 py-3 rounded-xl space-y-1 shadow-2xs">
                @foreach($errors->get('nama') as $err)
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-circle-exclamation text-red-500 text-[11px]"></i>
                        <span>{{ $err }}</span>
                    </div>
                @endforeach
                @foreach($errors->get('kata_sandi') as $err)
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-circle-exclamation text-red-500 text-[11px]"></i>
                        <span>{{ $err }}</span>
                    </div>
                @endforeach
                @foreach($errors->get('kata_sandi_confirmation') as $err)
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-circle-exclamation text-red-500 text-[11px]"></i>
                        <span>{{ $err }}</span>
                    </div>
                @endforeach
                @foreach($errors->get('foto_profil') as $err)
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-circle-exclamation text-red-500 text-[11px]"></i>
                        <span>{{ $err }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('profil.update') }}" method="POST" enctype="multipart/form-data" @submit.prevent="validateAndSubmit($event)" class="space-y-4">
            @csrf

            <!-- Foto Profil Upload & Live Preview -->
            <div class="flex flex-col items-center justify-center pb-2">
                <div class="relative group">
                    <img :src="previewUrl || 'https://ui-avatars.com/api/?name={{ urlencode(Auth::check() ? Auth::user()->nama : 'Pengguna') }}&background=1E293B&color=fff'" 
                         alt="Foto Profil" 
                         class="w-20 h-20 rounded-full object-cover border-2 border-slate-200 shadow-sm group-hover:border-red-500 transition">
                    
                    <label for="inputFotoProfil" 
                           class="absolute bottom-0 right-0 w-7 h-7 bg-red-600 hover:bg-red-700 text-white rounded-full flex items-center justify-center shadow-md cursor-pointer transition"
                           title="Ganti foto profil">
                        <i class="fa-solid fa-camera text-[11px]"></i>
                    </label>
                    <input type="file" 
                           id="inputFotoProfil" 
                           name="foto_profil" 
                           accept="image/png, image/jpeg, image/jpg, image/webp" 
                           @change="handlePhotoChange($event)"
                           class="hidden">
                </div>
                <div class="mt-2 text-[11px] text-slate-400 text-center">
                    Klik ikon kamera untuk mengunggah foto baru <br>
                    <span class="text-[10px] text-slate-400">(JPG, PNG, atau WEBP maks 2MB)</span>
                </div>
            </div>

            <!-- Nama / Username -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                    NAMA LENGKAP / USERNAME <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="text" 
                           name="nama" 
                           value="{{ Auth::check() ? Auth::user()->nama : '' }}"
                           required
                           placeholder="Nama lengkap Anda"
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl pl-10 pr-4 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <i class="fa-solid fa-user absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                </div>
            </div>

            <!-- Email & Peran (Info Readonly) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        ALAMAT EMAIL
                    </label>
                    <div class="relative">
                        <input type="text" 
                               value="{{ Auth::check() ? Auth::user()->email : '' }}" 
                               disabled
                               class="w-full bg-slate-100 border border-slate-200 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-500 cursor-not-allowed">
                        <i class="fa-solid fa-lock absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        PERAN SISTEM
                    </label>
                    <div class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-700 font-bold flex items-center space-x-1.5">
                        <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                        <span>{{ Auth::check() && Auth::user()->peran ? Auth::user()->peran->nama_peran : 'Pengguna' }}</span>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="pt-2 border-t border-slate-100">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">
                    UBAH KATA SANDI <span class="normal-case font-normal">(Kosongkan jika tidak ingin mengganti)</span>
                </div>
            </div>

            <!-- Kata Sandi Baru -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                    KATA SANDI BARU
                </label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" 
                           name="kata_sandi" 
                           x-model="password"
                           placeholder="Minimal 4 karakter"
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl pl-10 pr-10 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <i class="fa-solid fa-key absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                    <button type="button" 
                            @click="showPassword = !showPassword"
                            class="absolute right-3.5 top-2.5 text-slate-400 hover:text-slate-600"
                            tabindex="-1">
                        <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Konfirmasi Kata Sandi Baru -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                    KONFIRMASI KATA SANDI BARU
                </label>
                <div class="relative">
                    <input :type="showConfirmPassword ? 'text' : 'password'" 
                           name="kata_sandi_confirmation" 
                           x-model="confirmPassword"
                           placeholder="Ulangi kata sandi baru"
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl pl-10 pr-10 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <i class="fa-solid fa-shield-halved absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                    <button type="button" 
                            @click="showConfirmPassword = !showConfirmPassword"
                            class="absolute right-3.5 top-2.5 text-slate-400 hover:text-slate-600"
                            tabindex="-1">
                        <i :class="showConfirmPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-3 flex items-center space-x-2">
                <button type="submit" 
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 px-4 rounded-xl text-xs shadow-md hover:shadow-lg transition flex items-center justify-center space-x-1.5 cursor-pointer">
                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                    <span>Simpan Perubahan</span>
                </button>
                <button type="button" 
                        @click="showProfileModal = false; clientError = ''"
                        class="px-4 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold rounded-xl text-xs transition cursor-pointer">
                    Batal
                </button>
            </div>
        </form>

    </div>
</div>
