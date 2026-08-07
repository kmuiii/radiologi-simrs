<?php

use App\Models\AiPromptSetting;
use App\Models\ReportTemplate;
use App\Models\ExaminationType;
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

    public function resetFilter(): void
    {
        $this->reset('search');
        $this->resetPage();
    }

    // ===== Tab: Checklist Completeness =====
    public ?int $editingTemplateId = null;
    public string $templateExamName = '';
    public array $sections = [];
    public bool $isDefault = true;
    public string $newTemplateExaminationTypeId = '';
    public array $newSections = [''];

    public function openCreateCompletenessModal(): void
    {
        $this->newTemplateExaminationTypeId = '';
        $this->newSections = [''];
        Flux::modal('create-completeness-form')->show();
    }

    public function addNewSectionField(): void
    {
        $this->newSections[] = '';
    }

    public function removeNewSectionField(int $index): void
    {
        unset($this->newSections[$index]);
        $this->newSections = array_values($this->newSections);
    }

    public function createCompleteness(): void
    {
        $validated = $this->validate([
            'newTemplateExaminationTypeId' => 'required|exists:examination_types,id',
        ]);

        $cleanSections = collect($this->newSections)->filter(fn ($s) => filled(trim($s)))->values()->toArray();

        if (empty($cleanSections)) {
            Flux::toast(heading: 'Gagal', text: 'Minimal harus ada 1 bagian checklist.', variant: 'danger');
            return;
        }

        try {
            $examType = ExaminationType::findOrFail($validated['newTemplateExaminationTypeId']);

            ReportTemplate::create([
                'examination_type_id' => $examType->id,
                'template_name'       => $examType->name . ' (Default)',
                'template_content'    => [
                    'sections' => $cleanSections,
                    'default_findings_structure' => implode("\n", array_map(fn ($s) => "{$s}: ", $cleanSections)),
                ],
                'is_default' => true,
            ]);

            Flux::toast(heading: 'Berhasil', text: "Checklist untuk \"{$examType->name}\" berhasil dibuat.", variant: 'success');
            Flux::modal('create-completeness-form')->close();
        } catch (\Throwable $e) {
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat membuat checklist.', variant: 'danger');
        }
    }

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
        $existingExamTypeIds = ReportTemplate::pluck('examination_type_id');

        if (filled($this->search)) {
            $query->where(function ($q) {
                $q->where('template_name', 'ilike', '%' . $this->search . '%')
                  ->orWhereHas('examinationType', function ($q2) {
                      $q2->where('name', 'ilike', '%' . $this->search . '%')
                         ->orWhere('code', 'ilike', '%' . $this->search . '%');
                  });
            });
        }

        return [
            'templates' => $query->orderBy('id')->paginate($this->perPage),
            'promptSettings' => AiPromptSetting::with('updatedBy')->orderBy('id')->get(),
            'examinationTypesWithoutTemplate' => ExaminationType::whereNotIn('id', $existingExamTypeIds)->orderBy('name')->get(),
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
            <div class="flex items-center justify-between mb-4">
                <div>
                    <flux:heading size="lg">Checklist Completeness per Jenis Pemeriksaan</flux:heading>
                    <flux:text class="text-zinc-500">
                        Digunakan oleh AI Report Quality Checker untuk mengecek kelengkapan Findings secara Rule-Based.
                    </flux:text>
                </div>
                <flux:button variant="primary" color="blue" icon="plus" wire:click="openCreateCompletenessModal">
                    Buat Checklist Baru
                </flux:button>
            </div>

            <div class="flex items-end gap-2 mb-4">
                <flux:field class="flex-1 max-w-sm">
                    <flux:input wire:model.live.debounce.400ms="search" placeholder="Cari nama atau kode..." icon="magnifying-glass" />
                </flux:field>
                <flux:field>
                    <flux:button variant="ghost" icon="x-mark" wire:click="resetFilter">Batal</flux:button>
                </flux:field>
            </div>

            <div class="flex items-center gap-2 mb-3 text-sm">
                <span>Tampilkan</span>
                <flux:select wire:model.live="perPage" size="sm" class="w-20">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="30">30</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                </flux:select>
                <span>entri</span>
            </div>

            @if($examinationTypesWithoutTemplate->isNotEmpty())
                <div class="mb-4 p-3 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900 text-sm text-amber-700 dark:text-amber-400">
                    ⚠ {{ $examinationTypesWithoutTemplate->count() }} jenis pemeriksaan belum punya checklist:
                    {{ $examinationTypesWithoutTemplate->pluck('name')->implode(', ') }}
                </div>
            @endif

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

    {{-- MODAL: Buat Checklist Baru --}}
    <flux:modal name="create-completeness-form" class="md:w-[36rem]">
        <form wire:submit="createCompleteness" class="space-y-4">
            <div>
                <flux:heading size="lg">Buat Checklist Baru</flux:heading>
                <flux:subheading>Pilih jenis pemeriksaan yang belum punya checklist.</flux:subheading>
            </div>

            <flux:field>
                <flux:label>Jenis Pemeriksaan</flux:label>
                <flux:select wire:model="newTemplateExaminationTypeId">
                    <flux:select.option value="">- Pilih -</flux:select.option>
                    @foreach($examinationTypesWithoutTemplate as $et)
                        <flux:select.option value="{{ $et->id }}">{{ $et->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="newTemplateExaminationTypeId" />
                @if($examinationTypesWithoutTemplate->isEmpty())
                    <p class="text-xs text-zinc-400 mt-1">Semua jenis pemeriksaan sudah punya checklist.</p>
                @endif
            </flux:field>

            <div class="space-y-2">
                <flux:label>Bagian Checklist</flux:label>
                @foreach($newSections as $index => $section)
                    <div class="flex gap-2">
                        <flux:input wire:model="newSections.{{ $index }}" placeholder="misal: Cor" />
                        <flux:button type="button" size="sm" variant="ghost" wire:click="removeNewSectionField({{ $index }})">
                            <x-heroicon-o-trash class="size-4" />
                        </flux:button>
                    </div>
                @endforeach

                <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addNewSectionField">
                    Tambah Bagian
                </flux:button>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" color="blue" :disabled="$examinationTypesWithoutTemplate->isEmpty()">
                    Buat Checklist
                </flux:button>
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