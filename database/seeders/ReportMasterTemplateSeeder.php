<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ReportMasterTemplate;

class ReportMasterTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'template_name' => 'THORAX NORMAL',
                'uraian' => "X-FOTO THORAX PA :\n\nCor : CTR < 50 %.\n  Bentuk dan letak jantung normal\nPulmo : Corakan vaskuler normal\n  Tak tampak bercak pada kedua lapangan paru\nSinus kostofrenikus kanan kiri lancip.",
            ],
            [
                'template_name' => 'THORAX NORMAL MCU',
                'uraian' => "X-FOTO THORAX PA :\n\nCor : CTR < 50 %.\n  Bentuk dan letak jantung normal\nPulmo : Corakan vaskuler normal\n  Tak tampak bercak pada kedua lapangan paru\nDiafragma kanan setinggi costa 10-11 posterior\nSinus kostofrenikus kanan kiri lancip.",
            ],
            [
                'template_name' => 'THORAX NORMAL KLL',
                'uraian' => "X-FOTO THORAX AP : Cor : CTR < 50 %. Bentuk dan letak normal \nPulmo : Corakan vaskuler normal. \n Tak tampak bercak pada kedua lapangan paru\nTak tampak diskontinuitas pada os costae, os scapula dan os clavicula yang tervisualisasi\nDiafragma dan sinus : normal.",
            ],
            [
                'template_name' => 'THORAX CARDIOMEGALI LENGKAP',
                'uraian' => "X-FOTO THORAX PA :\n\nCor : CTR > 50 %. Apeks bergeser ke laterocaudal\nPinggang jantung mendatar, elevasi main bronkus kiri\nElongatio aorta\nPulmo : Corakan vaskuler normal\nTak tampak bercak pada kedua lapangan paru\nDiafragma dan sinus : normal.",
            ],
            [
                'template_name' => 'TB PARU PERBANDINGAN BERTAMBAH',
                'uraian' => "X-FOTO THORAX PA (dibandingkan foto lama tgl 00-00-2024) :\n\nCor : CTR < 50 %.\nBentuk dan letak jantung normal\nPulmo : Corakan vaskuler masih tampak meningkat\nMasih tampak bercak pada lapangan atas paru kiri yang relatif bertambah dibanding sebelumnya\nDiafragma dan sinus : normal.",
            ],
            [
                'template_name' => 'TB PARU PERBANDINGAN PERBAIKAN',
                'uraian' => "X-FOTO THORAX PA (dibandingkan foto lama tgl 00-00-2024) :\n\nCor : CTR < 50 %.\nBentuk dan letak jantung normal\nPulmo : Corakan vaskuler masih tampak meningkat\nMasih tampak bercak pada lapangan atas dan tengah paru kanan kiri yang berkurang dibanding sebelumnya\nDiafragma dan sinus : normal.",
            ],
            [
                'template_name' => 'TB PARU PERBANDINGAN STQA',
                'uraian' => "X-FOTO THORAX PA (dibandingkan foto lama tgl 00-00-2024) :\n\nCor : CTR < 50 %.\nBentuk dan letak jantung normal\nPulmo : Corakan vaskuler masih tampak meningkat\nMasih tampak bercak pada lapangan atas paru kanan kiri yang relatif sama dibanding sebelumnya\nDiafragma dan sinus : normal.",
            ],
            [
                'template_name' => 'TB PARU PERBANDINGAN SPESIFIK',
                'uraian' => "X-FOTO THORAX AP (dibandingkan foto lama) :\n\nCor : CTR < 48 %.\nBentuk dan letak jantung normal\nRetrokardiac dan retrosternal space tak menyempit\nPulmo : Corakan vaskuler relatif berkurang\nTak tampak bercak pada lapangan paru kanan kiri\nTak tampak penebalan hilus\nDiafragma dan sinus : normal.",
            ],
            [
                'template_name' => 'THORAX EDEMA PULMONUM',
                'uraian' => "X-FOTO THORAX AP :\n\nCor : CTR > 50 %.\nApeks bergeser ke laterocaudal\nPinggang jantung mendatar\nElevasi main bronkus kiri\nPulmo : Corakan vaskuler meningkat disertai blurring\nTampak bercak pada perifer kanan kiri dan parakardial kanan\nDiafragma kanan setinggi costa 9 posterior\nSinus kostofrenikus kanan kiri lancip.",
            ],
            [
                'template_name' => 'THORAX DOUBLE LUMEN',
                'uraian' => "X-FOTO THORAX AP :\n\nTampak terpasang double lumen dari arah kanan dengan ujung pada paravertebra kanan setinggi corpus V Th 6\nCor : CTR > 50 %\nApeks bergeser ke laterocaudal\nElongatio aorta\nPulmo : Corakan vaskuler normal\nTak tampak bercak pada kedua lapangan paru\nDiafragma kanan setinggi costa 9 posterior\nSinus kostofrenikus kanan kiri lancip.",
            ],
            [
                'template_name' => 'THORAX DOUBLE LUMEN EDEMA PULMONUM',
                'uraian' => "X-FOTO THORAX AP :\n\nTampak terpasang double lumen dari arah kiri dengan ujung pada paravertebral kanan setinggi corpus V Th 6\nCor : CTR > 50 %\nApeks bergeser ke laterocaudal\nPinggang jantung mendatar\nElevasi main bronkus kiri\nPulmo : Corakan vaskuler meningkat disertai blurring\nTampak bercak pada perifer kanan kiri dan parakardial kanan\nDiafragma kanan setinggi costa 9 posterior\nSinus kostofrenikus kanan kiri lancip.",
            ],
            [
                'template_name' => 'THORAX DEXTROCARDIO',
                'uraian' => "X-FOTO THORAX PA :\n\nCor : CTR < 50 %.\nApeks berada di hemithoraks kanan\nPulmo : Corakan vaskuler normal\nTak tampak bercak pada kedua lapangan paru\nSinus kostofrenikus kanan kiri lancip.",
            ],
            [
                'template_name' => 'THORAX POST WSD',
                'uraian' => "X-FOTO THORAX PA (dibandingkan foto tgl 00-00-2025) :\n\nTampak terpasang chest tube pada hemithoraks kanan dengan ujung superposisi os costa 9 posterior kanan\nCor : Batas kanan tertutup perselubungan\nBatas kiri baik\nPulmo : Corakan vaskuler meningkat\nTampak bercak pada lapangan paru kiri\nTampak perselubungan pada hemithoraks kanan yang berkurang dibanding sebelumnya\nSinus kostofrenikus kanan tertutup perselubungan\nSinus kostofrenikus kiri lancip.",
            ],
            [
                'template_name' => 'THORAX EFUSI MASSIF SINISTRA',
                'uraian' => "X-FOTO THORAX PA :\n\nCor : Batas kanan baik\nBatas kiri tertutup perselubungan\nPulmo : Corakan vaskuler paru kanan meningkat\nTak tampak bercak pada lapangan paru yang tervisualisasi\nTampak perselubungan homogen pada laterobasal hemithoraks kiri\nSinus kostofrenikus kanan lancip\nSinus kostofrenikus kiri tertutup perselubungan.",
            ],
            [
                'template_name' => 'THORAX PNEUMOTHORAX PERBANDINGAN',
                'uraian' => "X-FOTO THORAX PA (dibandingkan foto tgl 00-00-2025) :\n\nTampak terpasang chest tube pada hemithoraks kanan dengan ujung setinggi ICS 9\nTampak lusensi soft tissue regio aspek lateral hemithoraks kanan\nCor : CTR < 50 %.\nBentuk dan letak normal\nPulmo : Corakan vaskuler meningkat\nParu kiri tampak mengembang\nTak tampak lusensi avaskuler pada hemitoraks kiri\nDiafragma kanan setinggi costa 10 posterior\nSinus kostofrenikus kanan lancip\nKiri tumpul.",
            ],
            [
                'template_name' => 'THORAX ABSES PARU',
                'uraian' => "X-FOTO THORAX PA :\n\nCor : CTR < 50 %.\nBentuk dan letak normal\nPulmo : Corakan vaskuler meningkat\nTampak kavitas berdinding tebal dengan air fluid level di lapangan parakardial kiri\nDiafragma kanan setinggi costa 10 posterior\nSinus kostofrenikus kanan kiri lancip.",
            ],
            [
                'template_name' => 'THORAX PNEUMONIA',
                'uraian' => "X-FOTO THORAX PA :\n\nCor : CTR < 50 %.\nBentuk dan letak normal\nPulmo : Corakan vaskuler meningkat\nTampak konsolidasi disertai air bronkogram pada lapangan paru kanan\nDiafragma dan sinus : normal.",
            ],
            [
                'template_name' => 'PELVIS NORMAL',
                'uraian' => "X-FOTO PELVIS AP :\n\nStruktur tulang normal.\nTak tampak lesi litik maupun sklerotik.\nTak tampak destruksi tulang.\nSacroiliaca joint kanan kiri baik.\nCoxae joint kanan kiri baik.\nTak tampak penyempitan sela sendi.\nTak tampak diskontinuitas tulang.",
            ],
            [
                'template_name' => 'PELVIS NORMAL KLL',
                'uraian' => "X-FOTO PELVIS AP :\n\nStruktur tulang normal.\nTak tampak lesi litik maupun sklerotik.\nTak tampak destruksi tulang.\nSacroiliaca joint kanan kiri baik.\nCoxae joint kanan kiri baik.\nTak tampak penyempitan sela sendi.\nTak tampak diskontinuitas tulang.\nTak tampak subluksasi maupun dislokasi.",
            ],
            [
                'template_name' => 'THORAX CLAVICULA ORIF',
                'uraian' => "X-FOTO THORAX PA :\n\nTampak terpasang pin rush, plate dan screw pada os clavicula sinistra.\nPosisi dan kedudukan baik.\nTak tampak celah fraktur pada os clavicula sinistra.\nCor : CTR < 50 %.\nBentuk dan letak jantung normal.\nPulmo : Corakan vaskuler normal.\nTak tampak bercak pada kedua lapangan paru.\nDiafragma dan sinus : normal.",
            ],
            [
                'template_name' => 'GENU SINISTRA NORMAL',
                'uraian' => "X-FOTO ART GENU SINISTRA AP DAN LATERAL :\n\nStruktur tulang normal.\nEminentia intercondylaris tidak meruncing.\nSela sendi femoro-tibial tidak menyempit.\nTak tampak osteofit.\nTak tampak sklerosis maupun kista subkondral.\nTak tampak diskontinuitas tulang.\nTak tampak subluksasi maupun dislokasi.",
            ],
            [
                'template_name' => 'GENU DEXTRA NORMAL',
                'uraian' => "X-FOTO ART GENU DEXTRA AP DAN LATERAL :\n\nStruktur tulang normal.\nEminentia intercondylaris tidak meruncing.\nSela sendi femoro-tibial tidak menyempit.\nTak tampak osteofit.\nTak tampak sklerosis maupun kista subkondral.\nTak tampak diskontinuitas tulang.\nTak tampak subluksasi maupun dislokasi.",
            ],
            [
                'template_name' => 'GENU DS OA',
                'uraian' => "X-FOTO ART GENU DEXTRA DAN SINISTRA AP DAN LATERAL :\n\nStruktur tulang normal.\nEminentia intercondylaris dextra dan sinistra meruncing.\nSela sendi femoro-tibial menyempit.\nTampak osteofit pada condylus medial-lateral os femur dan tibia dextra sinistra.\nAspek superior os patella dextra sinistra.\nTak tampak sklerosis maupun kista subkondral.",
            ],
            [
                'template_name' => 'HUMERUS DEXTRA NORMAL',
                'uraian' => "X-FOTO HUMERUS DEXTRA AP :\n\nTak tampak diskontinuitas pada os humerus dextra.\nTak tampak subluksasi maupun dislokasi.\nTak tampak lesi litik maupun sklerotik.\nTak tampak destruksi tulang.\nTak tampak soft tissue swelling.\nTak tampak lusensi soft tissue.",
            ],
            [
                'template_name' => 'HUMERUS SINISTRA NORMAL',
                'uraian' => "X-FOTO HUMERUS SINISTRA AP :\n\nTak tampak diskontinuitas pada os humerus sinistra.\nTak tampak subluksasi maupun dislokasi.\nTak tampak lesi litik maupun sklerotik.\nTak tampak destruksi tulang.\nTak tampak soft tissue swelling.\nTak tampak lusensi soft tissue.",
            ],
            [
                'template_name' => 'FEMUR DEXTRA NORMAL',
                'uraian' => "X-FOTO FEMUR DEXTRA AP :\n\nTak tampak diskontinuitas pada os femur dextra.\nTak tampak subluksasi maupun dislokasi.\nTak tampak lesi litik maupun sklerotik.\nTak tampak destruksi tulang.\nTak tampak soft tissue swelling.\nTak tampak lusensi soft tissue.",
            ],
            [
                'template_name' => 'FEMUR SINISTRA NORMAL',
                'uraian' => "X-FOTO FEMUR SINISTRA AP :\n\nTak tampak diskontinuitas pada os femur sinistra.\nTak tampak subluksasi maupun dislokasi.\nTak tampak lesi litik maupun sklerotik.\nTak tampak destruksi tulang.\nTak tampak soft tissue swelling.\nTak tampak lusensi soft tissue.",
            ],
            [
                'template_name' => 'FEMUR DEXTRA ORIF',
                'uraian' => "X-FOTO FEMUR DEXTRA AP :\n\nTampak terpasang plate and screw pada 1/3 tengah os femur dextra.\nPosisi dan kedudukan baik.\nTampak celah fraktur pada 1/3 tengah os femur dextra.\nSudah tampak callus.\nTak tampak subluksasi maupun dislokasi.\nTak tampak lesi litik maupun destruksi tulang.\nTak tampak soft tissue swelling.\nTak tampak lusensi soft tissue.",
            ],
            [
                'template_name' => 'FEMUR SINISTRA ORIF',
                'uraian' => "X-FOTO FEMUR SINISTRA AP :\n\nTampak terpasang plate and screw pada 1/3 tengah os femur sinistra.\nPosisi dan kedudukan baik.\nTampak celah fraktur pada 1/3 tengah os femur sinistra.\nBelum tampak callus.\nTak tampak subluksasi maupun dislokasi.\nTak tampak lesi litik maupun destruksi tulang.\nTak tampak soft tissue swelling.\nTak tampak lusensi soft tissue.",
            ],
            [
                'template_name' => 'HUMERUS DEXTRA ORIF',
                'uraian' => "X-FOTO HUMERUS DEXTRA AP DAN LATERAL :\n\nTampak terpasang plate and screw pada 1/3 proksimal os humerus dextra.\nPosisi dan kedudukan baik.\nTampak celah fraktur pada 1/3 proksimal os humerus dextra.\nBelum tampak callus.\nTak tampak dislokasi maupun subluksasi sendi.\nTak tampak soft tissue swelling.\nTak tampak lusensi soft tissue.",
            ],
            [
                'template_name' => 'HUMERUS SINISTRA ORIF',
                'uraian' => "X-FOTO HUMERUS SINISTRA AP DAN LATERAL :\n\nTampak terpasang plate and screw pada 1/3 tengah os humerus sinistra.\nPosisi dan kedudukan baik.\nTampak celah fraktur pada 1/3 tengah os humerus sinistra.\nSudah tampak callus.\nTak tampak dislokasi maupun subluksasi sendi.\nTak tampak soft tissue swelling.\nTak tampak lusensi soft tissue.",
            ],
            [
                'template_name' => 'THORAKOLUMBAL NORMAL',
                'uraian' => "X-FOTO VERTEBRAE THORAKOLUMBAL AP DAN LATERAL :\n\nAlignment baik.\nTak tampak listesis.\nCurvatura tampak kifolordotik.\nStruktur tulang normal.\nTak tampak osteofit.\nTak tampak pemipihan corpus vertebrae thorakal dan lumbal.\nTak tampak penyempitan discus intervertebralis thorakal dan lumbal.",
            ],
        ];

        foreach ($templates as $t) {
            ReportMasterTemplate::create($t);
        }
    }
}
