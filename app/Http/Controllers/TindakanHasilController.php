<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DetailTindakan;
use App\Models\TindakanHasil;
use App\Services\RadiologyAIService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; 

class TindakanHasilController extends Controller
{
    public function __construct(protected RadiologyAIService $aiService) {}

    use AuthorizesRequests; 
    /**
     * Tampilkan halaman pemeriksaan (Findings, Impression, tombol AI)
     */
    public function show(DetailTindakan $detailTindakan)
    {
        $this->authorize('view', $detailTindakan);

        $detailTindakan->load([
            'examinationType.reportTemplates',
            'tindakan',
            'hasil',
        ]);

        return view('radiology.report', compact('detailTindakan'));
    }

    /**
     * Simpan Findings (manual, sebelum AI dipanggil)
     */
    public function saveFindings(Request $request, DetailTindakan $detailTindakan)
    {
        $this->authorize('view', $detailTindakan);

        $validated = $request->validate(['findings' => 'required|string']);

        TindakanHasil::updateOrCreate(
            ['detail_tindakan_id' => $detailTindakan->id],
            [
                'findings' => $validated['findings'],
                'impression' => $detailTindakan->hasil->impression ?? '',
            ]
        );

        return back()->with('status', 'Findings tersimpan.');
    }

    /**
     * Feature 1: Generate Impression dari Findings yang sudah tersimpan
     */
    public function generateImpression(DetailTindakan $detailTindakan)
    {
        $this->authorize('view', $detailTindakan);

        $hasil = $detailTindakan->hasil;

        if (! $hasil || empty($hasil->findings)) {
            return back()->withErrors(['ai' => 'Findings harus diisi terlebih dahulu.']);
        }

        $result = $this->aiService->generateImpression(
            $detailTindakan->examinationType->name,
            $detailTindakan->tindakan->clinical_notes ?? '-',
            $hasil->findings
        );

        if (! $result) {
            return back()->withErrors(['ai' => 'AI gagal menghasilkan Impression. Cek storage/logs/laravel.log.']);
        }

        $hasil->update([
            'impression' => $result,
            'impression_source' => 'ai_generated',
        ]);

        return back()->with('status', 'Impression berhasil digenerate AI.');
    }

    /**
     * Simpan Impression (manual atau hasil edit dokter atas draft AI)
     */
    public function saveImpression(Request $request, DetailTindakan $detailTindakan)
    {
        $this->authorize('view', $detailTindakan);

        $validated = $request->validate(['impression' => 'required|string']);
        $hasil = $detailTindakan->hasil;

        $source = ($hasil->impression_source === 'ai_generated' && $hasil->impression !== $validated['impression'])
            ? 'ai_edited'
            : ($hasil->impression_source ?? 'manual');

        $hasil->update([
            'impression' => $validated['impression'],
            'impression_source' => $source,
        ]);

        return back()->with('status', 'Impression tersimpan.');
    }

    /**
     * Feature 2: AI Report Quality Checker — Rule-Based Completeness + AI Review
     * Hasil TIDAK disimpan ke database.
     */
    public function reviewReport(DetailTindakan $detailTindakan)
    {
        $this->authorize('view', $detailTindakan);

        $hasil = $detailTindakan->hasil;

        if (! $hasil || empty($hasil->findings) || empty($hasil->impression)) {
            return back()->withErrors(['ai' => 'Findings dan Impression harus terisi sebelum review.']);
        }

        $template = $hasil->reportTemplate
            ?? $detailTindakan->examinationType->reportTemplates()->where('is_default', true)->first();

        $sections = $template->template_content['sections'] ?? [];
        $completeness = $this->checkCompleteness($hasil->findings, $sections);

        $aiReview = $this->aiService->reviewReport($hasil->findings, $hasil->impression);

        if (! $aiReview) {
            return back()->withErrors(['ai' => 'AI Review gagal diproses. Cek storage/logs/laravel.log.']);
        }

        return back()->with('review_result', [
            'completeness' => $completeness,
            'ai_review' => $aiReview,
        ]);
    }

    /**
     * Rule-Based Completeness: cek apakah tiap section template disebut di Findings
     */
    protected function checkCompleteness(string $findings, array $sections): array
    {
        $missing = [];

        foreach ($sections as $section) {
            if (stripos($findings, $section) === false) {
                $missing[] = $section;
            }
        }

        return [
            'passed' => empty($missing),
            'missing_sections' => $missing,
            'checked_sections' => $sections,
        ];
    }
}
