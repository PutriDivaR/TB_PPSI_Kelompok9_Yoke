@extends('layouts.admin')

@section('title', 'Edit Pengguna')

@section('content')
@php
    $selectedMode = old('mode', 'edit');
    if (! in_array($selectedMode, ['edit', 'ganti_pemegang'], true)) {
        $selectedMode = 'edit';
    }
@endphp

<div class="mx-auto max-w-3xl space-y-6" x-data="{ mode: @js($selectedMode) }">
    <div>
        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">KELOLA PENGGUNA</div>
        <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-900">Edit Pengguna</h1>
        <p class="mt-1 text-sm text-slate-500">Perbarui data akun atau ganti pemegang akun distributor.</p>
    </div>

    @if($isDistributor)
        <fieldset class="rounded-2xl border border-slate-200 bg-white p-5">
            <legend class="px-1 text-[11px] font-bold uppercase tracking-wider text-slate-500">TINDAKAN</legend>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 p-4"
                       :class="mode === 'edit' ? 'border-sky-500 bg-sky-50' : 'bg-white'">
                    <input type="radio" name="mode" value="edit" x-model="mode" form="edit-pengguna"
                           @checked($selectedMode === 'edit')>
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">Edit data pemegang yang sama</span>
                        <span class="mt-1 block text-xs text-slate-500">Perbarui nama atau email tanpa mengubah akun dan riwayatnya.</span>
                    </span>
                </label>
                <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 p-4"
                       :class="mode === 'ganti_pemegang' ? 'border-amber-500 bg-amber-50' : 'bg-white'">
                    <input type="radio" name="mode" value="ganti_pemegang" x-model="mode" form="edit-pengguna"
                           @checked($selectedMode === 'ganti_pemegang')>
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">Ganti pemegang (orang baru)</span>
                        <span class="mt-1 block text-xs text-slate-500">Arsipkan akun lama dan buat akun baru untuk perusahaan yang sama.</span>
                    </span>
                </label>
            </div>
            @error('mode')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </fieldset>
    @else
        <input type="hidden" name="mode" value="edit" form="edit-pengguna">
    @endif

    <form id="edit-pengguna" action="{{ route('admin.pengguna.update', $pengguna) }}" method="POST"
          onsubmit="return confirm('Simpan perubahan? Jika mengganti pemegang, akun lama akan diarsipkan dan riwayatnya tetap terjaga.')"
          class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs">
        @csrf
        @method('PUT')

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
            <p><span class="font-semibold">Peran:</span> {{ $pengguna->peran->nama_peran }}</p>
            @if($pengguna->distributor)
                <p class="mt-1"><span class="font-semibold">Perusahaan:</span> {{ $pengguna->distributor->nama }}</p>
            @endif
            <p class="mt-2 text-xs text-slate-500">Peran dan perusahaan tidak diubah dari formulir ini.</p>
        </div>

        <div x-cloak x-show="mode === 'edit'" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label for="edit_nama" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-500">NAMA</label>
                <input id="edit_nama" name="nama" type="text"
                       value="{{ $selectedMode === 'edit' ? old('nama', $pengguna->nama) : $pengguna->nama }}"
                       :disabled="mode !== 'edit'" :required="mode === 'edit'" maxlength="150"
                       @disabled($selectedMode !== 'edit') @required($selectedMode === 'edit')
                       class="w-full rounded-xl border border-blue-100 bg-[#EBF3FB] px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                @if($selectedMode === 'edit')
                    @error('nama')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                @endif
            </div>
            <div>
                <label for="edit_email" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-500">EMAIL</label>
                <input id="edit_email" name="email" type="email"
                       value="{{ $selectedMode === 'edit' ? old('email', $pengguna->email) : $pengguna->email }}"
                       :disabled="mode !== 'edit'" :required="mode === 'edit'" maxlength="254"
                       @disabled($selectedMode !== 'edit') @required($selectedMode === 'edit')
                       class="w-full rounded-xl border border-blue-100 bg-[#EBF3FB] px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                @if($selectedMode === 'edit')
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                @endif
            </div>
        </div>

        @if($isDistributor)
            <div x-cloak x-show="mode === 'ganti_pemegang'" class="space-y-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div>
                    <p class="text-sm font-bold text-amber-900">Akun pemegang lama akan diarsipkan</p>
                    <p class="mt-1 text-xs text-amber-800">
                        {{ $pengguna->nama }} tidak lagi dapat masuk. Riwayat aktivitas dan persetujuan lama tetap tercatat atas nama pemegang lama.
                    </p>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="new_nama" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-600">NAMA PEMEGANG BARU</label>
                        <input id="new_nama" name="nama" type="text"
                               value="{{ $selectedMode === 'ganti_pemegang' ? old('nama') : '' }}"
                               :disabled="mode !== 'ganti_pemegang'" :required="mode === 'ganti_pemegang'" maxlength="150"
                               @disabled($selectedMode !== 'ganti_pemegang') @required($selectedMode === 'ganti_pemegang')
                               class="w-full rounded-xl border border-amber-200 bg-white px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
                        @if($selectedMode === 'ganti_pemegang')
                            @error('nama')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <label for="new_email" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-600">EMAIL PEMEGANG BARU</label>
                        <input id="new_email" name="email" type="email"
                               value="{{ $selectedMode === 'ganti_pemegang' ? old('email') : '' }}"
                               :disabled="mode !== 'ganti_pemegang'" :required="mode === 'ganti_pemegang'" maxlength="254"
                               @disabled($selectedMode !== 'ganti_pemegang') @required($selectedMode === 'ganti_pemegang')
                               class="w-full rounded-xl border border-amber-200 bg-white px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
                        @if($selectedMode === 'ganti_pemegang')
                            @error('email')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <label for="new_kata_sandi" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-600">KATA SANDI PEMEGANG BARU</label>
                        <input id="new_kata_sandi" name="kata_sandi" type="password" minlength="8"
                               :disabled="mode !== 'ganti_pemegang'" :required="mode === 'ganti_pemegang'"
                               @disabled($selectedMode !== 'ganti_pemegang') @required($selectedMode === 'ganti_pemegang')
                               autocomplete="new-password"
                               class="w-full rounded-xl border border-amber-200 bg-white px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
                        @if($selectedMode === 'ganti_pemegang')
                            @error('kata_sandi')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <label for="new_kata_sandi_confirmation" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-600">KONFIRMASI KATA SANDI</label>
                        <input id="new_kata_sandi_confirmation" name="kata_sandi_confirmation" type="password" minlength="8"
                               :disabled="mode !== 'ganti_pemegang'" :required="mode === 'ganti_pemegang'"
                               @disabled($selectedMode !== 'ganti_pemegang') @required($selectedMode === 'ganti_pemegang')
                               autocomplete="new-password"
                               class="w-full rounded-xl border border-amber-200 bg-white px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>
                <p class="text-xs text-amber-800">Gunakan email baru yang belum terdaftar. Kata sandi minimal 8 karakter.</p>
            </div>
        @endif

        <div class="flex flex-wrap gap-2 pt-1">
            <button type="submit"
                    class="rounded-xl bg-red-600 px-5 py-2.5 text-xs font-semibold text-white transition hover:bg-red-700">
                Simpan Perubahan
            </button>
            <a href="{{ route('admin.pengguna') }}"
               class="rounded-xl bg-slate-200 px-5 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-300">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
