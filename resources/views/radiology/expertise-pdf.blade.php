<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Hasil Pemeriksaan Radiologi — {{ $detail->examinationType->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #111; padding: 20px 30px; }
    </style>
</head>
<body>

    {{-- ===== HEADER INSTITUSI ===== --}}
    <table style="width: 100%; border-bottom: 2px solid #333; padding-bottom: 8px; border-collapse: collapse;">
        <tr>
            <td style="width: 65px; text-align: center; vertical-align: middle;">
                @if(file_exists(public_path('images/logo-rs.png')))
                    <img src="{{ public_path('images/logo-rs.png') }}" style="width: 52px; height: 52px; object-fit: contain;" />
                @else
                    <div style="width: 52px; height: 52px; border: 1px solid #ccc; font-size: 8px; color: #999; text-align: center; line-height: 52px;">LOGO RS</div>
                @endif
            </td>
            <td style="text-align: center; vertical-align: middle; padding-right: 65px;">
                <div style="font-size: 12px; font-weight: bold; line-height: 1.3;">PT. Indo Techno Medic</div>
                <div style="font-size: 11px; font-weight: bold; line-height: 1.3;">RS Techno Medic</div>
                <div style="font-size: 9px; line-height: 1.3; color: #333;">Jl. Ringroad Selatan, Menayu Lor, Tirtonirmolo, Kec. Kasihan, Kabupaten Bantul, Daerah Istimewa Yogyakarta 55184</div>
                <div style="font-size: 9px; line-height: 1.3; color: #333;">Fax: - | Email: -</div>
            </td>
        </tr>
    </table>

    {{-- ===== JUDUL DOKUMEN ===== --}}
    <div style="text-align: center; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; padding: 8px 0; border-bottom: 1px solid #333; margin-bottom: 12px;">
        Hasil Pemeriksaan Radiologi
    </div>

    {{-- ===== INFO PASIEN 2 KOLOM ===== --}}
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 9.5px; line-height: 1.3;">
        <tr>
            <td style="width: 110px; padding: 2px 0; vertical-align: top; color: #333;">No RM</td>
            <td style="width: 10px; padding: 2px 0; text-align: center; vertical-align: top;">:</td>
            <td style="width: 210px; padding: 2px 0; vertical-align: top; font-weight: bold;">{{ $tindakan->patient->mrn }}</td>
            
            <td style="width: 110px; padding: 2px 0; vertical-align: top; color: #333;">Alamat</td>
            <td style="width: 10px; padding: 2px 0; text-align: center; vertical-align: top;">:</td>
            <td style="padding: 2px 0; vertical-align: top;">{{ $tindakan->patient->address ?? '-' }}</td>
        </tr>
        <tr>
            <td style="padding: 2px 0; vertical-align: top; color: #333;">Nama Pasien</td>
            <td style="padding: 2px 0; text-align: center; vertical-align: top;">:</td>
            <td style="padding: 2px 0; vertical-align: top; font-weight: bold;">{{ strtoupper($tindakan->patient->name) }}</td>
            
            <td style="padding: 2px 0; vertical-align: top; color: #333;">Cara Bayar</td>
            <td style="padding: 2px 0; text-align: center; vertical-align: top;">:</td>
            <td style="padding: 2px 0; vertical-align: top;">{{ $tindakan->patient->cara_bayar ?? '-' }}</td>
        </tr>
        <tr>
            <td style="padding: 2px 0; vertical-align: top; color: #333;">Kelas Pasien</td>
            <td style="padding: 2px 0; text-align: center; vertical-align: top;">:</td>
            <td style="padding: 2px 0; vertical-align: top;">{{ $tindakan->patient->kelas ?? '-' }}</td>
            
            <td style="padding: 2px 0; vertical-align: top; color: #333;">Asal Pasien</td>
            <td style="padding: 2px 0; text-align: center; vertical-align: top;">:</td>
            <td style="padding: 2px 0; vertical-align: top;">{{ $tindakan->pelayanan?->masterPoli?->name ?? 'RADIOLOGI' }}</td>
        </tr>
        <tr>
            <td style="padding: 2px 0; vertical-align: top; color: #333;">Tgl Lahir / JK</td>
            <td style="padding: 2px 0; text-align: center; vertical-align: top;">:</td>
            <td style="padding: 2px 0; vertical-align: top;">
                {{ $tindakan->patient->date_of_birth?->format('d-m-Y') ?? '-' }} / 
                {{ $tindakan->patient->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}
            </td>
            
            <td style="padding: 2px 0; vertical-align: top; color: #333;">Tgl Periksa</td>
            <td style="padding: 2px 0; text-align: center; vertical-align: top;">:</td>
            <td style="padding: 2px 0; vertical-align: top;">{{ $tindakan->tgl_periksa?->format('d-m-Y H:i:s') ?? '-' }}</td>
        </tr>
        <tr>
            <td style="padding: 2px 0; vertical-align: top; color: #333;">Jenis Pemeriksaan</td>
            <td style="padding: 2px 0; text-align: center; vertical-align: top;">:</td>
            <td style="padding: 2px 0; vertical-align: top; font-weight: bold; color: #1e40af;">{{ $detail->examinationType->name }}</td>
            
            <td style="padding: 2px 0; vertical-align: top; color: #333;">Pengirim</td>
            <td style="padding: 2px 0; text-align: center; vertical-align: top;">:</td>
            <td style="padding: 2px 0; vertical-align: top;">{{ $tindakan->referring_doctor_name ?? '-' }}</td>
        </tr>
    </table>

    <div style="border-top: 1px solid #ccc; margin: 8px 0;"></div>

    {{-- ===== HASIL PEMERIKSAAN (KLINIS, EXPERTISE, KESAN) ===== --}}
    <table style="width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 20px;">
        <tr>
            <td style="width: 90px; font-weight: bold; text-transform: uppercase; vertical-align: top; padding: 6px 0;">Klinis</td>
            <td style="width: 15px; text-align: center; vertical-align: top; padding: 6px 0;">:</td>
            <td style="vertical-align: top; padding: 6px 0; line-height: 1.5; white-space: pre-wrap;">{{ $tindakan->clinical_notes ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; text-transform: uppercase; vertical-align: top; padding: 6px 0;">Expertise</td>
            <td style="text-align: center; vertical-align: top; padding: 6px 0;">:</td>
            <td style="vertical-align: top; padding: 6px 0; line-height: 1.5; white-space: pre-wrap;">{{ $hasil->findings ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; text-transform: uppercase; vertical-align: top; padding: 6px 0;">Kesan</td>
            <td style="text-align: center; vertical-align: top; padding: 6px 0;">:</td>
            <td style="vertical-align: top; padding: 6px 0; line-height: 1.5; white-space: pre-wrap; font-weight: bold;">{{ $hasil->impression ?? '-' }}</td>
        </tr>
    </table>

    {{-- ===== GAMBAR RADIOLOGI (opsional) ===== --}}
    @if(isset($images) && $images->isNotEmpty())
        <div style="margin-top: 15px; margin-bottom: 20px;">
            <div style="font-weight: bold; text-transform: uppercase; font-size: 10px; margin-bottom: 6px; border-bottom: 1px solid #eee; padding-bottom: 2px;">Gambar Radiologi</div>
            <div>
                @foreach($images as $img)
                    <img src="{{ storage_path('app/public/' . $img->file_path) }}"
                         style="max-width:180px; max-height:140px; margin-right:8px; margin-bottom:8px; border:1px solid #ddd; border-radius: 4px; padding: 2px;" />
                @endforeach
            </div>
        </div>
    @endif

    {{-- ===== TANDA TANGAN ===== --}}
    <table style="width: 100%; margin-top: 30px; border-collapse: collapse;">
        <tr>
            <td style="width: 60%;"></td>
            <td style="text-align: center; width: 40%; font-size: 10px;">
                <div style="margin-bottom: 65px;">Spesialis Radiologi</div>
                <div style="font-weight: bold; text-decoration: underline;">{{ $tindakan->dokterPembaca->name ?? '( ________________________ )' }}</div>
            </td>
        </tr>
    </table>

</body>
</html>