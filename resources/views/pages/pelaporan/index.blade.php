@extends('layouts.app')
@section('title', 'Pelaporan & Stok')
@section('module_title', 'Pelaporan & Stok')

@section('content')
<div id="pelaporan-app" class="space-y-6" data-start="{{ $start }}" data-end="{{ $end }}">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Modul Pelaporan · MHD. Hasbi</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Ringkasan Penjualan & Stok</h1>
            <p class="mt-2 text-sm text-slate-500">Data dan transaksi dimuat melalui API Pelaporan Waypoint.</p>
        </div>
        <a id="export-link" href="#" class="rounded-lg bg-red-600 px-5 py-3 text-xs font-bold text-white hover:bg-red-700">Ekspor CSV</a>
    </div>

    <div id="notice" class="hidden rounded-xl border p-4 text-sm" role="status"></div>
    <div id="auth-warning" class="hidden rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
        Sesi login tidak tersedia untuk API Pelaporan. Silakan <a class="font-bold underline" href="{{ route('login') }}">login</a>, lalu buka kembali halaman ini.
    </div>

    <div id="report-content" class="hidden space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm">
            <span id="actor-label" class="font-semibold text-slate-700"></span>
            <span class="text-xs text-slate-500">Permintaan data, penyimpanan, dan ekspor memakai API yang sama dengan frontend modul.</span>
        </div>

        <form id="filter-form" class="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 md:grid-cols-2 xl:grid-cols-6">
            <div><label for="mulai" class="mb-2 block text-xs font-bold text-slate-600">Tanggal mulai</label><input id="mulai" name="mulai" type="date" value="{{ $start }}" required class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"></div>
            <div><label for="selesai" class="mb-2 block text-xs font-bold text-slate-600">Tanggal akhir</label><input id="selesai" name="selesai" type="date" value="{{ $end }}" required class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"></div>
            <div id="distributor-filter-wrap" class="hidden"><label for="filter-distributor" class="mb-2 block text-xs font-bold text-slate-600">Distributor</label><select id="filter-distributor" name="id_distributor" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"><option value="">Semua distributor</option></select></div>
            <div><label for="fiskal" class="mb-2 block text-xs font-bold text-slate-600">Tahun fiskal</label><select id="fiskal" name="id_tahun_fiskal" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"><option value="">Rentang tanggal</option></select></div>
            <div><label for="filter-product" class="mb-2 block text-xs font-bold text-slate-600">Produk</label><select id="filter-product" name="id_produk" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"><option value="">Semua produk</option></select></div>
            <div class="flex items-end"><button class="w-full rounded-lg bg-red-600 px-4 py-3 text-xs font-bold text-white">Terapkan Filter</button></div>
        </form>

        <div class="grid gap-4 md:grid-cols-3">
            <article class="rounded-xl border border-slate-200 bg-white p-6"><p class="text-xs font-bold text-slate-500">SELL-IN</p><p id="total-sell-in" class="mt-4 text-3xl font-bold">—</p><p class="mt-2 text-xs text-slate-500">Karton dikirim dari pabrik</p></article>
            <article class="rounded-xl border border-slate-200 bg-white p-6"><p class="text-xs font-bold text-slate-500">SELL-OUT</p><p id="total-sell-out" class="mt-4 text-3xl font-bold">—</p><p class="mt-2 text-xs text-slate-500">Karton terjual distributor</p></article>
            <article class="rounded-xl border border-slate-200 bg-white p-6"><p class="text-xs font-bold text-slate-500">CAKUPAN</p><p id="total-coverage" class="mt-4 text-3xl font-bold">—</p><p class="mt-2 text-xs text-slate-500">Produk · distributor</p></article>
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 p-5"><h2 class="text-sm font-bold">Tren bulanan</h2><p class="mt-1 text-xs text-slate-500">Jumlah Sell-In dan Sell-Out berdasarkan rentang filter.</p></div>
            <div class="overflow-x-auto"><table class="w-full whitespace-nowrap text-left text-xs"><thead class="bg-slate-50 uppercase text-slate-500"><tr><th class="px-5 py-3">Bulan</th><th class="px-5 py-3">Sell-In (karton)</th><th class="px-5 py-3">Sell-Out (karton)</th></tr></thead><tbody id="trend-body" class="divide-y divide-slate-100"></tbody></table></div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 p-5"><h2 class="text-sm font-bold">Rekonsiliasi per Distributor & Produk</h2><p class="mt-1 text-xs text-slate-500">Rekonsiliasi memerlukan saldo awal dan snapshot stok pada tanggal akhir periode.</p></div>
            <div class="overflow-x-auto"><table class="w-full whitespace-nowrap text-left text-xs"><thead class="bg-slate-50 uppercase text-slate-500"><tr><th class="px-4 py-3">Distributor / SKU</th><th class="px-4 py-3">Sell-In</th><th class="px-4 py-3">Sell-Out</th><th class="px-4 py-3">Stok fisik</th><th class="px-4 py-3">Layak jual</th><th class="px-4 py-3">Tanggal stok</th><th class="px-4 py-3">Ekspektasi</th><th class="px-4 py-3">Selisih</th><th class="px-4 py-3">Status</th></tr></thead><tbody id="summary-body" class="divide-y divide-slate-100"></tbody></table></div>
        </section>

        <div id="entry-forms" class="hidden grid gap-6 xl:grid-cols-2">
            <form id="sell-out-form" class="space-y-4 rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="font-bold">Catat Sell-Out</h2><p class="text-xs text-slate-500">Distributor diambil dari akun yang login.</p>
                <label class="block text-xs font-semibold" for="sale-product">Produk / SKU</label><select id="sale-product" name="id_produk" required class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"><option value="">Pilih produk</option></select>
                <div class="grid gap-4 md:grid-cols-2"><div><label class="mb-2 block text-xs font-semibold" for="sale-date">Tanggal penjualan</label><input id="sale-date" name="tanggal" type="date" required value="{{ now()->format('Y-m-d') }}" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"></div><div><label class="mb-2 block text-xs font-semibold" for="sale-qty">Jumlah (karton)</label><input id="sale-qty" name="jumlah" type="number" required min="1" step="1" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"></div></div>
                <label class="block text-xs font-semibold" for="sale-channel">Kanal</label><select id="sale-channel" name="kanal" required class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"><option>Retail</option><option>General Trade</option><option>Modern Trade</option></select>
                <button class="w-full rounded-lg bg-red-600 px-4 py-3 text-xs font-bold text-white">Simpan Sell-Out</button>
            </form>
            <form id="stock-form" class="space-y-4 rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="font-bold">Catat Snapshot Stok</h2><p class="text-xs text-slate-500">Tanggal dan produk yang sama akan memperbarui snapshot.</p>
                <label class="block text-xs font-semibold" for="stock-product">Produk / SKU</label><select id="stock-product" name="id_produk" required class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"><option value="">Pilih produk</option></select>
                <label class="block text-xs font-semibold" for="stock-date">Tanggal snapshot</label><input id="stock-date" name="periode" type="date" required value="{{ now()->format('Y-m-d') }}" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm">
                <div class="grid gap-4 md:grid-cols-2"><div><label class="mb-2 block text-xs font-semibold" for="physical">Stok fisik (karton)</label><input id="physical" name="stok_fisik" type="number" required min="0" step="1" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"></div><div><label class="mb-2 block text-xs font-semibold" for="saleable">Stok layak jual (karton)</label><input id="saleable" name="stok_layak_jual" type="number" required min="0" step="1" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"></div></div>
                <p class="text-xs text-slate-500">0 ≤ stok layak jual ≤ stok fisik. Klaim belum otomatis mengurangi stok.</p>
                <button class="w-full rounded-lg bg-slate-800 px-4 py-3 text-xs font-bold text-white">Simpan Snapshot</button>
            </form>
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 p-5"><h2 class="text-sm font-bold">Riwayat Sell-Out</h2></div>
            <div class="overflow-x-auto"><table class="w-full whitespace-nowrap text-left text-xs"><thead class="bg-slate-50 uppercase text-slate-500"><tr><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Distributor</th><th class="px-5 py-3">Produk / SKU</th><th class="px-5 py-3">Kanal</th><th class="px-5 py-3">Karton</th></tr></thead><tbody id="sell-out-body" class="divide-y divide-slate-100"></tbody></table></div>
            <div class="flex items-center justify-between p-4 text-xs"><button id="sell-out-prev" class="rounded border px-3 py-2">Sebelumnya</button><span id="sell-out-page" class="text-slate-500"></span><button id="sell-out-next" class="rounded border px-3 py-2">Berikutnya</button></div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 p-5"><h2 class="text-sm font-bold">Riwayat Snapshot Stok</h2></div>
            <div class="overflow-x-auto"><table class="w-full whitespace-nowrap text-left text-xs"><thead class="bg-slate-50 uppercase text-slate-500"><tr><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Distributor</th><th class="px-5 py-3">Produk / SKU</th><th class="px-5 py-3">Stok fisik</th><th class="px-5 py-3">Stok layak jual</th></tr></thead><tbody id="stock-body" class="divide-y divide-slate-100"></tbody></table></div>
            <div class="flex items-center justify-between p-4 text-xs"><button id="stock-prev" class="rounded border px-3 py-2">Sebelumnya</button><span id="stock-page" class="text-slate-500"></span><button id="stock-next" class="rounded border px-3 py-2">Berikutnya</button></div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('pelaporan-app');
    const content = document.getElementById('report-content');
    const warning = document.getElementById('auth-warning');
    const notice = document.getElementById('notice');
    const filterForm = document.getElementById('filter-form');
    const byId = (id) => document.getElementById(id);
    const state = { role: null, sellOutPage: 1, stockPage: 1, csrf: null };
    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    const number = (value) => value === null || value === undefined ? '—' : new Intl.NumberFormat('id-ID').format(value);
    const api = async (url, options = {}) => {
        const response = await fetch(url, { credentials: 'same-origin', ...options, headers: { Accept: 'application/json', ...(options.headers || {}) } });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const message = payload.message || (payload.errors ? Object.values(payload.errors).flat().join(' ') : `Permintaan gagal (${response.status}).`);
            const error = new Error(message); error.status = response.status; throw error;
        }
        return payload;
    };
    const showNotice = (message, ok = false) => {
        notice.textContent = message;
        notice.className = `rounded-xl border p-4 text-sm ${ok ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800'}`;
        notice.classList.remove('hidden');
    };
    const filters = () => {
        const params = new URLSearchParams();
        for (const name of ['mulai', 'selesai', 'id_tahun_fiskal', 'id_distributor', 'id_produk']) {
            const value = filterForm.elements[name]?.value;
            if (value) params.set(name, value);
        }
        return params;
    };
    const fillSelect = (select, items, valueOf, labelOf, firstLabel) => {
        select.innerHTML = `<option value="">${esc(firstLabel)}</option>` + items.map((item) => `<option value="${esc(valueOf(item))}">${esc(labelOf(item))}</option>`).join('');
    };
    const setRows = (target, rows, emptyText, render) => {
        target.innerHTML = rows.length ? rows.map(render).join('') : `<tr><td colspan="9" class="p-8 text-center text-slate-500">${esc(emptyText)}</td></tr>`;
    };
    const renderSummary = (rows) => setRows(byId('summary-body'), rows, 'Belum ada data laporan untuk filter ini.', (row) => `<tr>
        <td class="px-4 py-4"><strong>${esc(row.distributor)}</strong><div class="mt-1 text-slate-500">${esc(row.sku)} · ${esc(row.produk)}</div></td>
        <td class="px-4 py-4">${number(row.sell_in)}</td><td class="px-4 py-4">${number(row.sell_out)}</td><td class="px-4 py-4">${number(row.stok_fisik)}</td><td class="px-4 py-4">${number(row.stok_layak_jual)}</td><td class="px-4 py-4">${esc(row.tanggal_stok || '—')}</td><td class="px-4 py-4">${number(row.ekspektasi_layak_jual)}</td><td class="px-4 py-4">${number(row.selisih)}</td><td class="px-4 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 font-semibold">${esc(row.status)}</span></td></tr>`);
    const renderSellOut = (rows) => setRows(byId('sell-out-body'), rows, 'Belum ada transaksi Sell-Out pada filter ini.', (row) => `<tr><td class="px-5 py-4">${esc(row.tanggal)}</td><td class="px-5 py-4">${esc(row.distributor.nama)}</td><td class="px-5 py-4">${esc(row.produk.nama_produk)} <span class="text-slate-500">(${esc(row.produk.kode_sku)})</span></td><td class="px-5 py-4">${esc(row.kanal)}</td><td class="px-5 py-4">${number(row.jumlah)}</td></tr>`);
    const renderStock = (rows) => setRows(byId('stock-body'), rows, 'Belum ada snapshot stok pada filter ini.', (row) => `<tr><td class="px-5 py-4">${esc(row.periode)}</td><td class="px-5 py-4">${esc(row.distributor.nama)}</td><td class="px-5 py-4">${esc(row.produk.nama_produk)} <span class="text-slate-500">(${esc(row.produk.kode_sku)})</span></td><td class="px-5 py-4">${number(row.stok_fisik)}</td><td class="px-5 py-4">${number(row.stok_layak_jual)}</td></tr>`);
    const renderPage = (prefix, meta, current, setPage, load) => {
        byId(`${prefix}-page`).textContent = `Halaman ${meta.current_page} dari ${meta.last_page} · ${meta.total} data`;
        byId(`${prefix}-prev`).disabled = meta.current_page <= 1;
        byId(`${prefix}-next`).disabled = meta.current_page >= meta.last_page;
        byId(`${prefix}-prev`).onclick = () => { setPage(current - 1); load(); };
        byId(`${prefix}-next`).onclick = () => { setPage(current + 1); load(); };
    };
    const makeUrl = (path, page = null) => {
        const params = filters();
        if (page) { params.set('page', page); params.set('per_page', 10); }
        return `${path}?${params.toString()}`;
    };
    const loadReports = async () => {
        notice.classList.add('hidden');
        const params = filters();
        const [dashboard, summary, sellOut, stock] = await Promise.all([
            api(`/api/v1/reports/dashboard?${params}`),
            api(`/api/v1/reports/summary?${params}`),
            api(makeUrl('/api/v1/reports/sell-out', state.sellOutPage)),
            api(makeUrl('/api/v1/reports/stock', state.stockPage)),
        ]);
        byId('total-sell-in').textContent = `${number(dashboard.data.totals.sell_in)} karton`;
        byId('total-sell-out').textContent = `${number(dashboard.data.totals.sell_out)} karton`;
        byId('total-coverage').textContent = `${dashboard.data.totals.products} · ${dashboard.data.totals.distributors}`;
        setRows(byId('trend-body'), dashboard.data.trend, 'Belum ada tren pada periode ini.', (item) => `<tr><td class="px-5 py-3">${esc(item.period)}</td><td class="px-5 py-3">${number(item.sell_in)}</td><td class="px-5 py-3">${number(item.sell_out)}</td></tr>`);
        renderSummary(summary.data);
        renderSellOut(sellOut.data);
        renderStock(stock.data);
        renderPage('sell-out', sellOut.meta, state.sellOutPage, (page) => state.sellOutPage = page, loadReports);
        renderPage('stock', stock.meta, state.stockPage, (page) => state.stockPage = page, loadReports);
        byId('export-link').href = `/api/v1/reports/export?${params}`;
        history.replaceState({}, '', `${location.pathname}?${params}`);
    };
    const submitJson = async (form, endpoint) => {
        const values = Object.fromEntries(new FormData(form).entries());
        for (const name of ['id_produk', 'jumlah', 'stok_fisik', 'stok_layak_jual']) if (values[name] !== undefined) values[name] = Number(values[name]);
        const csrf = await api('/api/v1/auth/csrf');
        await api(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf.csrf_token }, body: JSON.stringify(values) });
        showNotice('Data tersimpan melalui API Pelaporan.', true);
        await loadReports();
    };
    filterForm.addEventListener('submit', async (event) => {
        event.preventDefault(); state.sellOutPage = 1; state.stockPage = 1;
        try { await loadReports(); } catch (error) { showNotice(error.message); }
    });
    byId('fiskal').addEventListener('change', (event) => {
        const option = event.target.selectedOptions[0];
        if (option.dataset.start) byId('mulai').value = option.dataset.start;
        if (option.dataset.end) byId('selesai').value = option.dataset.end;
    });
    byId('sell-out-form').addEventListener('submit', async (event) => { event.preventDefault(); try { await submitJson(event.currentTarget, '/api/v1/reports/sell-out'); } catch (error) { showNotice(error.message); } });
    byId('stock-form').addEventListener('submit', async (event) => { event.preventDefault(); try { await submitJson(event.currentTarget, '/api/v1/reports/stock'); } catch (error) { showNotice(error.message); } });

    (async () => {
        try {
            const profile = (await api('/api/v1/auth/me')).data;
            state.role = profile.role;
            byId('actor-label').textContent = `Login: ${profile.name} · Peran: ${profile.role}${profile.distributor ? ` · ${profile.distributor.name}` : ''}`;
            const [productsPayload, yearsPayload] = await Promise.all([
                api('/api/v1/reference/products?per_page=100'), api('/api/v1/reference/fiscal-years'),
            ]);
            const products = productsPayload.data;
            const years = yearsPayload.data;
            fillSelect(byId('filter-product'), products, (item) => item.id, (item) => `${item.sku} — ${item.name}`, 'Semua produk');
            fillSelect(byId('sale-product'), products, (item) => item.id, (item) => `${item.sku} — ${item.name}`, 'Pilih produk');
            fillSelect(byId('stock-product'), products, (item) => item.id, (item) => `${item.sku} — ${item.name}`, 'Pilih produk');
            byId('fiskal').innerHTML = '<option value="">Rentang tanggal</option>' + years.map((year) => `<option value="${year.id_tahun_fiskal}" data-start="${esc(String(year.tanggal_mulai).slice(0,10))}" data-end="${esc(String(year.tanggal_selesai).slice(0,10))}">${esc(String(year.tanggal_mulai).slice(0,4))}/${esc(String(year.tanggal_selesai).slice(0,4))}</option>`).join('');
            if (['Admin', 'Logistik Pusat', 'SAM'].includes(profile.role)) {
                const distributors = await api('/api/v1/reference/distributors?per_page=100');
                fillSelect(byId('filter-distributor'), distributors.data, (item) => item.id, (item) => item.name, 'Semua distributor');
                byId('distributor-filter-wrap').classList.remove('hidden');
            }
            if (profile.role === 'Distributor') byId('entry-forms').classList.remove('hidden');
            content.classList.remove('hidden');
            const initial = new URLSearchParams(location.search);
            for (const name of ['mulai', 'selesai', 'id_tahun_fiskal', 'id_distributor', 'id_produk']) {
                if (initial.has(name) && filterForm.elements[name]) filterForm.elements[name].value = initial.get(name);
            }
            if (!initial.has('mulai')) byId('mulai').value = root.dataset.start;
            if (!initial.has('selesai')) byId('selesai').value = root.dataset.end;
            if (initial.has('page')) state.sellOutPage = Number(initial.get('page')) || 1;
            await loadReports();
        } catch (error) {
            if (error.status === 401) warning.classList.remove('hidden');
            else showNotice(error.message);
        }
    })();
});
</script>
@endpush
