@extends('layouts.app')
@section('title', 'Book Flights — Where Is My Flight')

@section('content')
{{-- Full-width OTA container --}}
<div class="max-w-5xl mx-auto space-y-8 fade-in-up">

    {{-- ═══ HEADER ═══ --}}
    <div class="text-center space-y-2 py-4">
        <div class="inline-flex items-center gap-3 mb-1">
            <span class="text-4xl float-y">✈️</span>
        </div>
        <h1 class="text-4xl font-extrabold gradient-text tracking-tight">Search & Book Flights</h1>
        <p class="text-sm" style="color: var(--text-muted)">
            Enter any airport codes or city names — we show you what's available and where to book
        </p>
    </div>

    {{-- ═══ SEARCH BAR ═══ --}}
    <div class="ota-search-bar p-6 rounded-3xl">
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_auto_1fr_260px_auto] gap-3 items-end">

            {{-- Origin --}}
            <div>
                <label class="block text-xs font-semibold mb-1.5 uppercase tracking-widest" style="color:var(--text-muted)">
                    🛫 From
                </label>
                <input id="ota-from" type="text"
                       placeholder="DEL, New York, London…"
                       class="wimf-input w-full rounded-xl px-4 py-3 text-center font-mono text-xl uppercase font-bold tracking-widest"
                       value="{{ request('from') }}">
                <p class="text-xs mt-1 text-center" id="from-label" style="color:var(--text-muted)">Airport code or City</p>
            </div>

            {{-- Swap button --}}
            <div class="pb-6 flex justify-center">
                <button id="swap-btn" class="swap-btn" title="Swap">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12M8 7l4-4M8 7l4 4M16 17H4m12 0l-4-4m4 4l-4 4"/>
                    </svg>
                </button>
            </div>

            {{-- Destination --}}
            <div>
                <label class="block text-xs font-semibold mb-1.5 uppercase tracking-widest" style="color:var(--text-muted)">
                    🛬 To
                </label>
                <input id="ota-to" type="text"
                       placeholder="BOM, Los Angeles, Paris…"
                       class="wimf-input w-full rounded-xl px-4 py-3 text-center font-mono text-xl uppercase font-bold tracking-widest"
                       value="{{ request('to') }}">
                <p class="text-xs mt-1 text-center" id="to-label" style="color:var(--text-muted)">Airport code or City</p>
            </div>

            {{-- Date + Class --}}
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-semibold mb-1.5 uppercase tracking-widest" style="color:var(--text-muted)">
                        📅 Date
                    </label>
                    <input type="text" id="ota-date"
                           class="wimf-input w-full rounded-xl px-3 py-3 text-sm"
                           placeholder="DD/MM/YYYY">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1.5 uppercase tracking-widest" style="color:var(--text-muted)">
                        💺 Class
                    </label>
                    <select id="ota-class" class="wimf-input w-full rounded-xl px-3 py-3 text-sm">
                        <option value="economy"  {{ request('class','economy') === 'economy'  ? 'selected' : '' }}>Economy</option>
                        <option value="business" {{ request('class','economy') === 'business' ? 'selected' : '' }}>Business</option>
                        <option value="first"    {{ request('class','economy') === 'first'    ? 'selected' : '' }}>First Class</option>
                    </select>
                </div>
            </div>

            {{-- Search button --}}
            <div class="pb-0">
                <button id="ota-search-btn"
                        class="ota-book-btn w-full py-3 rounded-xl text-white font-bold text-base flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                    </svg>
                    Search
                </button>
            </div>
        </div>

        {{-- Popular routes quick-select --}}
        <div class="mt-4 pt-4 border-t" style="border-color: var(--border)">
            <p class="text-xs font-semibold mb-2 uppercase tracking-widest" style="color:var(--text-muted)">Popular Routes</p>
            <div class="flex flex-wrap gap-2">
                @foreach([['DEL','BOM'],['DEL','BLR'],['BOM','DEL'],['BLR','HYD'],['JFK','LAX'],['LHR','JFK'],['DXB','DEL'],['SIN','BKK']] as [$f,$t])
                    <button onclick="quickRoute('{{ $f }}','{{ $t }}')"
                            class="ota-platform-chip text-sm" style="color:var(--text-secondary)">
                        {{ $f }} ✈ {{ $t }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══ SORT BAR (visible when results loaded) ═══ --}}
    <div id="sort-bar" class="hidden flex items-center justify-between">
        <p id="results-headline" class="text-sm font-semibold" style="color:var(--text-secondary)">Found <span id="results-count" class="text-wimf-400 font-bold">—</span> flights</p>
        <div class="flex items-center gap-2">
            <span class="text-xs" style="color:var(--text-muted)">Sort by:</span>
            @foreach(['price','departure','duration'] as $s)
                <button onclick="sortResults('{{ $s }}')"
                        id="sort-{{ $s }}"
                        class="px-3 py-1 rounded-full text-xs font-semibold transition-all border"
                        style="border-color:var(--border); color:var(--text-muted)">
                    {{ ucfirst($s) }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- ═══ RESULTS LIST ═══ --}}
    <div id="ota-results" class="space-y-4"></div>

    {{-- ═══ EMPTY STATE ═══ --}}
    <div id="ota-empty" class="hidden glass-card rounded-2xl py-16 text-center">
        <p class="text-5xl mb-4">🔍</p>
        <h2 class="text-xl font-bold" style="color:var(--text-primary)">Enter a route above to search flights</h2>
        <p class="text-sm mt-2" style="color:var(--text-muted)">We compare prices across 5 major booking platforms</p>
    </div>

    {{-- ═══ NO RESULTS ═══ --}}
    <div id="ota-no-results" class="hidden glass-card rounded-2xl py-12 text-center">
        <p class="text-4xl mb-4">😔</p>
        <h2 class="text-lg font-bold" style="color:var(--text-primary)">No flights found for this route</h2>
        <p class="text-sm mt-2" style="color:var(--text-muted)">Try different airport codes</p>
    </div>

    {{-- ═══ LOADING STATE ═══ --}}
    <div id="ota-loading" class="hidden space-y-4">
        @for($i = 0; $i < 4; $i++)
        <div class="glass-card rounded-2xl p-5 animate-pulse">
            <div class="flex items-center gap-4">
                <div class="skeleton w-14 h-14 rounded-xl"></div>
                <div class="flex-1 space-y-2">
                    <div class="skeleton h-4 w-1/3 rounded"></div>
                    <div class="skeleton h-3 w-1/2 rounded"></div>
                </div>
                <div class="skeleton h-8 w-24 rounded-xl"></div>
            </div>
        </div>
        @endfor
    </div>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* Customize Flatpickr for dark/glass theme */
    .flatpickr-calendar { bg-color: var(--bg-card); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
    .flatpickr-day.selected { background: var(--color-wimf-500) !important; border-color: var(--color-wimf-500) !important; }
    
    .large-platform-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.6rem 1rem;
        border-radius: 0.75rem;
        font-weight: 700;
        font-size: 0.85rem;
        color: #fff;
        transition: transform 0.2s, opacity 0.2s, box-shadow 0.2s;
    }
    .large-platform-btn:hover {
        transform: translateY(-2px);
        opacity: 0.95;
        box-shadow: 0 6px 15px rgba(0,0,0,0.25);
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
// ── Initialize Flatpickr ──
const dateInput = flatpickr("#ota-date", {
    dateFormat: "d/m/Y",
    minDate: "today",
    defaultDate: new Date().fp_incr(7) // default 7 days from now
});

// ── State ──
let currentResults = [];
let currentSort = 'price';

// ── Lookup airport name (blur event) ──
async function lookupAirport(code, labelId) {
    const label = document.getElementById(labelId);
    const airports = {
        DEL:'New Delhi', BOM:'Mumbai', BLR:'Bangalore', HYD:'Hyderabad',
        CCU:'Kolkata', MAA:'Chennai', AMD:'Ahmedabad', COK:'Kochi',
        GOI:'Goa', PNQ:'Pune', JFK:'New York (JFK)', LAX:'Los Angeles',
        ORD:'Chicago', SFO:'San Francisco', LHR:'London Heathrow',
        CDG:'Paris CDG', FRA:'Frankfurt', AMS:'Amsterdam', DXB:'Dubai',
        DOH:'Doha', AUH:'Abu Dhabi', SIN:'Singapore', KUL:'Kuala Lumpur',
        BKK:'Bangkok', HND:'Tokyo Haneda', NRT:'Tokyo Narita',
        ICN:'Seoul Incheon', SYD:'Sydney', MEL:'Melbourne',
    };
    const c = code.toUpperCase().trim();
    if (c.length === 3 && airports[c]) {
        label.textContent = airports[c];
        label.style.color = '#59b2ff';
    } else {
        label.textContent = 'Airport code or City';
        label.style.color = '';
    }
}

document.getElementById('ota-from').addEventListener('input', function() {
    lookupAirport(this.value, 'from-label');
    this.value = this.value.toUpperCase();
});
document.getElementById('ota-to').addEventListener('input', function() {
    lookupAirport(this.value, 'to-label');
    this.value = this.value.toUpperCase();
});

// Swap button
document.getElementById('swap-btn').addEventListener('click', () => {
    const fromEl = document.getElementById('ota-from');
    const toEl   = document.getElementById('ota-to');
    [fromEl.value, toEl.value] = [toEl.value, fromEl.value];
    lookupAirport(fromEl.value, 'from-label');
    lookupAirport(toEl.value,   'to-label');
});

function quickRoute(from, to) {
    document.getElementById('ota-from').value = from;
    document.getElementById('ota-to').value   = to;
    lookupAirport(from, 'from-label');
    lookupAirport(to,   'to-label');
    doSearch();
}

// ── Search ──
document.getElementById('ota-search-btn').addEventListener('click', doSearch);
document.addEventListener('keydown', e => { if (e.key === 'Enter') doSearch(); });

function doSearch() {
    const from = document.getElementById('ota-from').value.trim().toUpperCase();
    const to   = document.getElementById('ota-to').value.trim().toUpperCase();
    
    // Convert flatpickr internal date to YYYY-MM-DD for backend API
    const selectedDates = dateInput.selectedDates;
    const dateStr = selectedDates.length > 0 
        ? flatpickr.formatDate(selectedDates[0], "Y-m-d") 
        : flatpickr.formatDate(new Date().fp_incr(7), "Y-m-d");

    const cls  = document.getElementById('ota-class').value;

    if (from.length < 3 || to.length < 3) {
        alert('Please enter a valid airport code or city name.');
        return;
    }

    showLoading();

    fetch(`/api/ota-search?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&date=${encodeURIComponent(dateStr)}&class=${encodeURIComponent(cls)}&sort=${currentSort}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            showNoResults();
            return;
        }
        currentResults = data.flights || [];
        if (currentResults.length === 0) { showNoResults(); return; }
        renderResults(currentResults, data);
    })
    .catch(() => showNoResults());
}

function sortResults(by) {
    currentSort = by;
    // update active button
    ['price','departure','duration'].forEach(s => {
        const btn = document.getElementById(`sort-${s}`);
        if (s === by) {
            btn.style.borderColor = '#3391ff';
            btn.style.color = '#3391ff';
        } else {
            btn.style.borderColor = '';
            btn.style.color = '';
        }
    });

    const sorted = [...currentResults];
    if (by === 'price')     sorted.sort((a, b) => a.base_price - b.base_price);
    if (by === 'departure') sorted.sort((a, b) => a.departure.localeCompare(b.departure));
    if (by === 'duration')  sorted.sort((a, b) => a.duration.localeCompare(b.duration));
    renderResults(sorted, null);
}

function renderResults(flights, meta) {
    document.getElementById('ota-loading').classList.add('hidden');
    document.getElementById('ota-empty').classList.add('hidden');
    document.getElementById('ota-no-results').classList.add('hidden');
    document.getElementById('ota-results').classList.remove('hidden');
    document.getElementById('sort-bar').classList.remove('hidden');

    if (meta) {
        const countEl = document.getElementById('results-count');
        countEl.textContent = `${flights.length} flights`;
        // Headline
        document.getElementById('results-headline').innerHTML =
            `<span style="color:var(--text-secondary)">
                ${meta.from_info?.city} <span class="font-mono text-wimf-400">(${meta.from})</span>
                &rarr;
                ${meta.to_info?.city} <span class="font-mono text-wimf-400">(${meta.to})</span>
                &nbsp;&bull;&nbsp; ${formatDate(meta.date)}
            </span>`;
    }

    const grid = document.getElementById('ota-results');
    grid.innerHTML = flights.map((f, i) => {
        const cheapest = f.platforms[0];
        const stopLabel = f.stops === 0
            ? '<span style="color:#10b981" class="text-xs font-semibold">✦ Non-stop</span>'
            : `<span style="color:#f59e0b" class="text-xs font-semibold">${f.stops} Stop</span>`;

        const platformBtns = f.platforms.map(p => `
            <a href="${p.url}" target="_blank" rel="noopener"
               class="large-platform-btn flex-1 min-w-[120px]"
               style="background: ${p.color}; border: 1px solid rgba(255,255,255,0.1);">
                <span style="font-size:0.8rem; letter-spacing:0.02em">${p.name}</span>
                <span class="font-mono font-black border-l pl-2 border-white/20">₹${p.price.toLocaleString('en-IN')}</span>
            </a>
        `).join('');

        const typeLabel = f.type === 'lcc'
            ? `<span class="px-2 py-0.5 rounded-full text-xs" style="background:#059669/20;color:#10b981">Low-cost</span>`
            : `<span class="px-2 py-0.5 rounded-full text-xs" style="background:#3391ff/20;color:#3391ff">Full-service</span>`;

        return `
        <div class="ota-flight-card p-5" style="animation-delay:${i * 60}ms; opacity:0; animation: fadeInUp 0.45s ease ${i * 60}ms forwards">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">

                {{-- Airline logo badge --}}
                <div class="airline-logo w-14 h-14 shrink-0 rounded-xl text-base"
                     style="background: ${f.color}; min-width:3.5rem">
                    ${f.abbr}
                </div>

                {{-- Flight info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <span class="font-bold" style="color:var(--text-primary)">${f.airline}</span>
                        <span class="font-mono text-xs px-2 py-0.5 rounded-full glass-card" style="color:var(--text-muted)">${f.flight_num}</span>
                        ${typeLabel}
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <div class="text-center">
                            <p class="font-mono font-bold text-xl" style="color:var(--text-primary)">${f.departure}</p>
                            <p class="text-xs font-mono" style="color:var(--text-muted)">${f.from_city}</p>
                        </div>
                        <div class="flex-1 text-center px-2 min-w-[80px]">
                            <p class="text-xs mb-1" style="color:var(--text-muted)">${f.duration}</p>
                            <div class="relative flex items-center">
                                <div class="flex-1 h-px" style="background:var(--border)"></div>
                                <span class="mx-2 text-wimf-400 text-sm">✈</span>
                                <div class="flex-1 h-px" style="background:var(--border)"></div>
                            </div>
                            <div class="mt-1">${stopLabel}</div>
                        </div>
                        <div class="text-center">
                            <p class="font-mono font-bold text-xl" style="color:var(--text-primary)">${f.arrival}</p>
                            <p class="text-xs font-mono" style="color:var(--text-muted)">${f.to_city}</p>
                        </div>
                    </div>
                </div>

                {{-- Price Info --}}
                <div class="sm:text-right shrink-0 flex sm:flex-col items-center sm:items-end gap-3 sm:gap-1 w-full sm:w-auto justify-between sm:justify-start">
                    <div>
                        <p class="text-xs" style="color:var(--text-muted)">Starting from</p>
                        <p class="ota-price-badge">₹${cheapest.price.toLocaleString('en-IN')}</p>
                        <p class="text-xs font-bold px-2 py-0.5 rounded-full mt-1" style="background:var(--color-wimf-500); color:#fff; display:inline-block">
                            ${f.class === 'economy' ? 'Economy' : f.class === 'business' ? 'Business' : 'First Class'}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Platform comparison row (Big Colored Buttons) --}}
            <div class="mt-5 pt-5 border-t" style="border-color:var(--border)">
                <p class="text-xs font-bold mb-3 uppercase tracking-wider" style="color:var(--text-muted)">Available Booking Platforms</p>
                <div class="flex flex-wrap gap-3">
                    ${platformBtns}
                </div>
            </div>
        </div>
        `;
    }).join('');
}

function showLoading() {
    document.getElementById('ota-empty').classList.add('hidden');
    document.getElementById('ota-no-results').classList.add('hidden');
    document.getElementById('ota-results').classList.add('hidden');
    document.getElementById('sort-bar').classList.add('hidden');
    document.getElementById('ota-loading').classList.remove('hidden');
}

function showNoResults() {
    document.getElementById('ota-loading').classList.add('hidden');
    document.getElementById('ota-results').classList.add('hidden');
    document.getElementById('sort-bar').classList.add('hidden');
    document.getElementById('ota-no-results').classList.remove('hidden');
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-IN', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
}

// Auto-trigger if query params present
@if(request('from') && request('to'))
document.addEventListener('DOMContentLoaded', () => {
    lookupAirport('{{ request('from') }}', 'from-label');
    lookupAirport('{{ request('to') }}', 'to-label');
    doSearch();
});
@else
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('ota-empty').classList.remove('hidden');
});
@endif
</script>
@endpush
