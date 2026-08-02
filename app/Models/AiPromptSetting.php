<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;


#[Fillable(['key', 'label', 'prompt_text', 'required_tokens', 'updated_by'])]
class AiPromptSetting extends Model
{
    protected $table = 'ai_prompt_settings';

    protected $casts = ['required_tokens' => 'array'];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Ganti token {{xxx}} di prompt_text dengan nilai sungguhan.
     * $values contoh: ['findings' => '...', 'examination_type' => 'Thorax PA']
     */
    public function render(array $values): string
    {
        $prompt = $this->prompt_text;

        foreach ($values as $key => $value) {
            $prompt = str_replace('{{' . $key . '}}', $value, $prompt);
        }

        return $prompt;
    }

    /**
     * Cek apakah semua required_tokens ada di dalam teks prompt yang diberikan.
     * Return array token yang HILANG (kosong berarti valid).
     */
    public function findMissingTokens(string $promptText): array
    {
        return collect($this->required_tokens)
            ->reject(fn ($token) => str_contains($promptText, $token))
            ->values()
            ->toArray();
    }
}
