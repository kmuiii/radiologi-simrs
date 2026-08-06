{{-- resources/views/ai-test/index.blade.php --}}
<x-app-layout>
<div class="max-w-4xl mx-auto p-6 space-y-10">

    <h1 class="text-xl font-bold">🧪 AI Testing Panel</h1>

    {{-- Feature 1: Impression Generator --}}
    <div class="border rounded p-4">
        <h2 class="font-semibold mb-2">1. AI Impression Generator</h2>
        <form method="POST" action="{{ route('ai-test.impression') }}" class="space-y-2">
            @csrf
            <input name="examination_type" placeholder="Jenis Pemeriksaan (misal: Thorax PA)"
                   class="w-full border p-2" value="{{ old('examination_type') }}">
            <input name="clinical_notes" placeholder="Keterangan Klinis"
                   class="w-full border p-2" value="{{ old('clinical_notes') }}">
            <textarea name="findings" placeholder="Findings" rows="4"
                      class="w-full border p-2">{{ old('findings') }}</textarea>
            <button class="bg-blue-600 text-white px-4 py-2 rounded">Generate Impression</button>
        </form>
        @if(session('result_impression'))
            <div class="mt-3 bg-gray-50 p-3 border-l-4 border-blue-500">
                {{ session('result_impression') }}
            </div>
        @endif
    </div>

    {{-- Feature 2: Quality Checker --}}
    <div class="border rounded p-4">
        <h2 class="font-semibold mb-2">2. AI Report Quality Checker</h2>
        <form method="POST" action="{{ route('ai-test.review') }}" class="space-y-2">
            @csrf
            <textarea name="findings" placeholder="Findings" rows="4"
                      class="w-full border p-2">{{ old('findings') }}</textarea>
            <textarea name="impression" placeholder="Impression" rows="2"
                      class="w-full border p-2">{{ old('impression') }}</textarea>
            <button class="bg-green-600 text-white px-4 py-2 rounded">Review Laporan</button>
        </form>
        @if(session('result_review'))
            <div class="mt-3 bg-gray-50 p-3 border-l-4 border-green-500 space-y-1">
                @foreach(session('result_review') as $key => $value)
                    <p><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</p>
                @endforeach
            </div>
        @endif
    </div>

</div>
</x-app-layout>