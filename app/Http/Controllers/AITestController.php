<?php
namespace App\Http\Controllers;

use App\Services\RadiologyAIService;
use Illuminate\Http\Request;

class AITestController extends Controller
{
    public function __construct(protected RadiologyAIService $aiService) {}

    public function index()
    {
        return view('ai-test.index');
    }

    public function testImpression(Request $request)
    {
        $validated = $request->validate([
            'examination_type' => 'required|string',
            'clinical_notes' => 'nullable|string',
            'findings' => 'required|string',
        ]);

        $result = $this->aiService->generateImpression(
            $validated['examination_type'],
            $validated['clinical_notes'] ?? '-',
            $validated['findings']
        );

        return back()->with('result_impression', $result)->withInput();
    }

    public function testReview(Request $request)
    {
        $validated = $request->validate([
            'findings' => 'required|string',
            'impression' => 'required|string',
        ]);

        $result = $this->aiService->reviewReport($validated['findings'], $validated['impression']);

        return back()->with('result_review', $result)->withInput();
    }
}