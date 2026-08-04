<?php

use App\Models\AiPromptSetting;
use App\Models\ReportTemplate;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $activeTab = 'completeness';

    // Search and Pagination
    public string $search = '';
    public int $perPage = 10;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    // ===== Tab: Checklist Completeness =====
    public ?int $editingTemplateId = null;
    public string $templateExamName = '';
    public array $sections = [];
    public bool $isDefault = true;

    public function openEditTemplate(int $id): void
    {
        $template = ReportTemplate::with('examinationType')->findOrFail($id);

        $this->editingTemplateId = $template->id;
        $this->templateExamName = $template->examinationType->name;
        $this->sections = $template->template_content['sections'] ?? [];
        $this->isDefault = $template->is_default;

        Flux::modal('completeness-form')->show();
    }

    public function addSectionField(): void
    {
        $this->sections[] = '';
    }

    public function removeSectionField(int $index): void
    {
        unset($this->sections[$index]);
        $this->sections = array_values($this->sections);
    }

    public function saveCompleteness(): void
    {
        $cleanSections = collect($this->sections)->filter(fn ($s) => filled(trim($s)))->values()->toArray();

        if (empty($cleanSections)) {
            Flux::toast(heading: 'Gagal', text: 'Minimal harus ada 1 bagian checklist.', variant: 'danger');
            return;
        }

        try {
            $template = ReportTemplate::findOrFail($this->editingTemplateId);
            $template->update([
                'template_content' => [
                    'sections' => $cleanSections,
                    'default_findings_structure' => implode("\n", array_map(fn ($s) => "{$s}: ", $cleanSections)),
                ],
                'is_default' => $this->isDefault,
            ]);

            Flux::toast(heading: 'Berhasil', text: "Checklist untuk \"{$this->templateExamName}\" berhasil diperbarui.", variant: 'success');
            Flux::modal('completeness-form')->close();
        } catch (\Throwable $e) {
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat menyimpan.', variant: 'danger');
        }
    }

    // ===== Tab: Prompt Engineering =====
    public ?int $editingPromptId = null;
    public string $promptLabel = '';
    public string $promptText = '';
    public array $promptRequiredTokens = [];

    public function openEditPrompt(int $id): void
    {
        $setting = AiPromptSetting::findOrFail($id);

        $this->editingPromptId = $setting->id;
        $this->promptLabel = $setting->label;
        $this->promptText = $setting->prompt_text;
        $this->promptRequiredTokens = $setting->required_tokens;

        Flux::modal('prompt-form')->show();
    }

    public function savePrompt(): void
    {
        $setting = AiPromptSetting::findOrFail($this->editingPromptId);

        $missing = $setting->findMissingTokens($this->promptText);

        if (! empty($missing)) {
            Flux::toast(
                heading: 'Gagal',
                text: 'Token wajib berikut tidak ditemukan di prompt: ' . implode(', ', $missing),
                variant: 'danger',
            );
            return;
        }

        try {
            $setting->update([
                'prompt_text' => $this->promptText,
                'updated_by' => Auth::id(),
            ]);

            Flux::toast(heading: 'Berhasil', text: "Prompt \"{$setting->label}\" berhasil diperbarui.", variant: 'success');
            Flux::modal('prompt-form')->close();
        } catch (\Throwable $e) {
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat menyimpan.', variant: 'danger');
        }
    }

    public function resetPromptToDefault(): void
    {
        $this->dispatch('confirm-reset');
    }

    public function with(): array
    {
        $query = ReportTemplate::with('examinationType');

        if (filled($this->search)) {
            $query->where(function ($q) {
                $q->where('template_name', 'like', '%' . $this->search . '%')
                  ->orWhereHas('examinationType', function ($q2) {
                      $q2->where('name', 'like', '%' . $this->search . '%')
                         ->orWhere('code', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return [
            'templates' => $query->orderBy('id')->paginate($this->perPage),
            'promptSettings' => AiPromptSetting::with('updatedBy')->orderBy('id')->get(),
        ];
    }
} ?>

<div class="space-y-6">

    <flux:heading size="xl">Configuration</flux:heading>

    {{-- Tab Nav --}}
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="flex gap-1">
            @foreach(['completeness' => 'Checklist Completeness', 'prompt' => 'Prompt Engineering'] as $tab => $label)
                <button
                    wire:click="$set('activeTab', '{{ $tab }}')"
                    class="px-4 py-2.5 border-b-2 font-medium text-sm transition-colors
                        {{ $activeTab === $tab
                            ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                            : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- TAB: CHECKLIST COMPLETENESS --}}
    @if($activeTab === 'completeness')
        <flux:card>
            <flux:heading size="lg" class="mb-4">Checklist Completeness per Jenis Pemeriksaan</flux:heading>
            <flux:text class="mb-4 text-zinc-500">
                Digunakan oleh AI Report Quality Checker untuk mengecek kelengkapan Findings secara Rule-Based.
            </flux:text>

            <div class="flex flex-wrap items-end gap-4 mb-4">
                <flux:field class="flex-1 max-w-sm">
                    <flux:input wire:model.live.debounce.400ms="search" placeholder="Cari nama atau kode..." icon="magnifying-glass" />
                </flux:field>
            </div>

            <div class="flex items-center gap-2 text-sm">
                <span>Tampilkan</span>
                <flux:select wire:model.live="perPage" size="sm" class="w-20">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="30">30</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                </flux:select>
                <span>entri</span>
            </div>

            <flux:table :paginate="$templates">
                <flux:table.columns>
                    <flux:table.column>Jenis Pemeriksaan</flux:table.column>
                    <flux:table.column>Nama Template</flux:table.column>
                    <flux:table.column>Jumlah Bagian</flux:table.column>
                    <flux:table.column>Default?</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($templates as $t)
                        <flux:table.row :key="$t->id">
                            <flux:table.cell>{{ $t->examinationType->name }}</flux:table.cell>
                            <flux:table.cell>{{ $t->template_name }}</flux:table.cell>
                            <flux:table.cell>{{ count($t->template_content['sections'] ?? []) }} bagian</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$t->is_default ? 'green' : 'zinc'">
                                    {{ $t->is_default ? 'Ya' : 'Tidak' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="sm" variant="ghost" wire:click="openEditTemplate({{ $t->id }})">
                                    <x-heroicon-o-pencil class="size-4" />
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif

    {{-- TAB: PROMPT ENGINEERING --}}
    @if($activeTab === 'prompt')
        <flux:card>
            <flux:heading size="lg" class="mb-4">Prompt Engineering</flux:heading>
            <flux:text class="mb-4 text-zinc-500">
                Mengatur instruksi (prompt) yang dikirim ke Gemini untuk masing-masing fitur AI.
            </flux:text>

            <div class="space-y-4">
                @foreach($promptSettings as $setting)
                    <flux:card class="bg-zinc-50 dark:bg-zinc-800/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <flux:heading size="sm">{{ $setting->label }}</flux:heading>
                                <flux:text size="sm" class="text-zinc-500">
                                    Terakhir diubah:
                                    {{ $setting->updated_at->format('d-m-Y H:i') }}
                                    @if($setting->updatedBy)
                                        oleh {{ $setting->updatedBy->name }}
                                    @endif
                                </flux:text>
                            </div>
                            <flux:button size="sm" wire:click="openEditPrompt({{ $setting->id }})">
                                Edit Prompt
                            </flux:button>
                        </div>
                    </flux:card>
                @endforeach
            </div>
        </flux:card>
    @endif

    {{-- MODAL: Edit Checklist Completeness --}}
    <flux:modal name="completeness-form" class="md:w-[36rem]">
        <form wire:submit="saveCompleteness" class="space-y-4">
            <div>
                <flux:heading size="lg">Edit Checklist — {{ $templateExamName }}</flux:heading>
                <flux:subheading>Bagian yang wajib disebutkan dalam Findings.</flux:subheading>
            </div>

            <div class="space-y-2">
                @foreach($sections as $index => $section)
                    <div class="flex gap-2">
                        <flux:input wire:model="sections.{{ $index }}" placeholder="misal: Cor" />
                        <flux:button type="button" size="sm" variant="ghost" wire:click="removeSectionField({{ $index }})">
                            <x-heroicon-o-trash class="size-4" />
                        </flux:button>
                    </div>
                @endforeach

                <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addSectionField">
                    Tambah Bagian
                </flux:button>
            </div>

            <flux:accent color="blue">
                <flux:field variant="inline">
                        <flux:label>Jadikan Template Default</flux:label>
                        <flux:switch wire:model="isDefault" />
                </flux:field>
            </flux:accent>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" color="blue">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- MODAL: Edit Prompt --}}
    <flux:modal name="prompt-form" class="md:w-[42rem]">
        <form wire:submit="savePrompt" class="space-y-4">
            <div>
                <flux:heading size="lg">Edit Prompt — {{ $promptLabel }}</flux:heading>
                <flux:subheading>Token berikut WAJIB ada di dalam prompt:</flux:subheading>
                <div class="flex flex-wrap gap-1 mt-2">
                    @foreach($promptRequiredTokens as $token)
                        <flux:badge size="sm" color="blue">{{ $token }}</flux:badge>
                    @endforeach
                </div>
            </div>

            <flux:field>
                <flux:label>Isi Prompt</flux:label>
                <flux:textarea wire:model="promptText" rows="16" class="font-mono text-xs" />
                <flux:error name="promptText" />
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" color="blue">Simpan Prompt</flux:button>
            </div>
        </form>
    </flux:modal>

</div>