@if (session('success'))
    <div class="rounded-lg bg-emerald-50 border border-emerald-300 text-emerald-700 text-sm px-4 py-3 flex items-center gap-2">
        <i class="fa-solid fa-circle-check text-base"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="rounded-lg bg-red-50 border border-red-300 text-red-700 text-sm px-4 py-3 flex items-center gap-2">
        <i class="fa-solid fa-triangle-exclamation text-base"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="rounded-lg bg-red-50 border border-red-300 text-red-700 text-sm px-4 py-3">
        <p class="font-bold flex items-center gap-2"><i class="fa-solid fa-circle-exclamation"></i> Data belum dapat disimpan:</p>
        <ul class="mt-1 list-disc pl-6 text-xs space-y-0.5">
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    </div>
@endif