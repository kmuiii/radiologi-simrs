<?php

namespace App\Services;

use App\Models\AiPromptSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RadiologyAIService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        $this->model = config('services.gemini.model');
    }

    protected function callGemini(string $prompt): ?string
    {
        $response = Http::withHeaders(['Content-Type' => 'application/json'])->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
            ['contents' => [['parts' => [['text' => $prompt]]]]]
        );

        if ($response->failed()) {
            Log::error('Gemini API error', ['body' => $response->body()]);
            return null;
        }

        return $response->json('candidates.0.content.parts.0.text');
    }

    public function generateImpression(string $examinationType, string $clinicalNotes, string $findings): ?string
    {
        $setting = AiPromptSetting::where('key', 'impression_generator')->first();

        if (! $setting) {
            Log::error('AI Prompt Setting "impression_generator" tidak ditemukan di database.');
            return null;
        }

        $prompt = $setting->render([
            'examination_type' => $examinationType,
            'clinical_notes' => $clinicalNotes,
            'findings' => $findings,
        ]);

        return $this->callGemini($prompt);
    }

    public function reviewReport(string $findings, string $impression): ?array
    {
        $setting = AiPromptSetting::where('key', 'ai_review')->first();

        if (! $setting) {
            Log::error('AI Prompt Setting "ai_review" tidak ditemukan di database.');
            return null;
        }

        $prompt = $setting->render(['findings' => $findings, 'impression' => $impression]);

        $result = $this->callGemini($prompt);

        if (! $result) {
            return null;
        }

        $cleaned = trim(preg_replace('/```json|```/', '', $result));

        return json_decode($cleaned, true);
    }
}