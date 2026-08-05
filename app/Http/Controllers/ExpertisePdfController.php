<?php

namespace App\Http\Controllers;

use App\Models\DetailTindakan;
use App\Models\Tindakan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ExpertisePdfController extends Controller
{
    /**
     * Download PDF expertise untuk SATU baris detail tindakan.
     */
    public function download(Tindakan $tindakan, DetailTindakan $detail): Response
    {
        ini_set('memory_limit', '512M');
        Gate::authorize('view', $tindakan);

        $detail->load(['examinationType', 'hasil', 'images']);
        $tindakan->load(['patient', 'dokterPembaca', 'operator1', 'pelayanan.masterPoli']);

        $pdf = Pdf::loadView('radiology.expertise-pdf', [
            'tindakan' => $tindakan,
            'detail'   => $detail,
            'hasil'    => $detail->hasil,
            'images'   => $detail->images,
        ])->setPaper('a4', 'portrait');

        $filename = 'expertise-' . $detail->no_order . '-' . str_replace(' ', '_', $detail->examinationType->name) . '.pdf';
        $filename = str_replace(['/', '\\'], '-', $filename);

        return $pdf->stream($filename); // stream = buka di tab baru, bukan download paksa
    }

    /**
     * Download PDF expertise untuk SEMUA baris detail tindakan dalam satu tindakan.
     * Setiap baris menjadi satu section/halaman dalam PDF yang sama.
     */
    public function all(Tindakan $tindakan): Response
    {
        ini_set('memory_limit', '512M');
        Gate::authorize('view', $tindakan);

        $tindakan->load([
            'patient',
            'dokterPembaca',
            'operator1',
            'pelayanan.masterPoli',
            'detailTindakan.examinationType',
            'detailTindakan.hasil',
            'detailTindakan.images',
        ]);

        $pdf = Pdf::loadView('radiology.expertise-all-pdf', [
            'tindakan'          => $tindakan,
            'detailTindakanList' => $tindakan->detailTindakan,
        ])->setPaper('a4', 'portrait');

        $filename = 'expertise-all-' . $tindakan->id . '-' . now()->format('Ymd_His') . '.pdf';
        $filename = str_replace(['/', '\\'], '-', $filename);

        return $pdf->stream($filename);
    }
}
