{{-- resources/views/radiology/report.blade.php --}}
<x-app-layout>
<div class="max-w-4xl mx-auto p-6 space-y-8">

    <div>
        <h1 class="text-xl font-bold">{{ $detailTindakan->examinationType->name }}</h1>
        <p class="text-sm text-gray-500">
            Status: {{ $detailTindakan->status }} |
            Dokter Pengirim: {{ $detailTindakan->tindakan->referring_doctor_name }}
        </p>
        <p class="text-sm text-gray-500">
            Keterangan Klinis: {{ $detailTindakan->tindakan->clinical_notes }}
        </p>
    </div>

    @if(session('status'))
        <div class="bg-green-100 p-2 rounded text-green-800">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-100 p-2 rounded text-red-800">{{ $errors->first() }}</div>
    @endif

    {{-- Findings --}}
    <div class="border rounded p-4">
        <form method="POST" action="{{ route('radiology.findings.save', $detailTindakan) }}">
            @csrf
            <label class="font-semibold">Findings</label>
            <textarea name="findings" rows="6" class="w-full border p-2 mt-1">{{ $detailTindakan->hasil->findings ?? '' }}</textarea>
            <button class="bg-gray-700 text-white px-3 py-1 rounded mt-2">Simpan Findings</button>
        </form>
    </div>

    {{-- Impression + AI Impression Generator --}}
    <div class="border rounded p-4">
        <label class="font-semibold">Impression</label>
        <form method="POST" action="{{ route('radiology.impression.save', $detailTindakan) }}">
            @csrf
            <textarea name="impression" rows="3" class="w-full border p-2 mt-1">{{ $detailTindakan->hasil->impression ?? '' }}</textarea>
            <button class="bg-gray-700 text-white px-3 py-1 rounded mt-2">Simpan Impression</button>
        </form>

        <form method="POST" action="{{ route('radiology.impression.generate', $detailTindakan) }}"
              class="inline-block mt-2"
              onsubmit="return {{ ($detailTindakan->hasil->impression ?? '') !== '' ? 'confirm(\'Impression sudah berisi data. Generate ulang akan menimpa isi saat ini. Lanjutkan?\')' : 'true' }}">
            @csrf
            <button class="bg-blue-600 text-white px-3 py-1 rounded">🤖 Generate Impression</button>
        </form>

        @if($detailTindakan->hasil?->impression_source)
            <span class="text-xs text-gray-500 ml-2">
                Sumber: {{ $detailTindakan->hasil->impression_source }}
            </span>
        @endif
    </div>

    {{-- AI Report Quality Checker --}}
    <div class="border rounded p-4">
        <form method="POST" action="{{ route('radiology.review', $detailTindakan) }}">
            @csrf
            <button class="bg-green-600 text-white px-3 py-1 rounded">🔍 Review Laporan</button>
        </form>

        @if(session('review_result'))
            @php($r = session('review_result'))
            <div class="mt-4 border-l-4 border-green-500 bg-gray-50 p-4 space-y-2">
                <h3 class="font-semibold">🤖 AI Review Assistant</h3>

                <div>
                    <p class="text-sm font-medium">Rule Validation</p>
                    <p class="text-sm">
                        {{ $r['completeness']['passed'] ? '✅ Completeness: Seluruh bagian wajib telah diisi.' : '⚠ Completeness: Bagian belum dijelaskan — ' . implode(', ', $r['completeness']['missing_sections']) }}
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium">AI Review</p>
                    <p class="text-sm">⚠ Consistency: {{ $r['ai_review']['consistency'] }}</p>
                    <p class="text-sm">⚠ Terminology: {{ $r['ai_review']['terminology'] }}</p>
                    <p class="text-sm">{{ str_contains(strtolower($r['ai_review']['typo']), 'tidak') ? '✅' : '⚠' }} Typo: {{ $r['ai_review']['typo'] }}</p>
                    <p class="text-sm italic mt-1">Rekomendasi: {{ $r['ai_review']['recommendation'] }}</p>
                </div>
            </div>
        @endif
    </div>

</div>
</x-app-layout>