<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@php
    $routeName = request()->route()?->getName();
    $mappedTitle = match($routeName) {
        'admin.dashboard' => 'Dashboard',
        'admin.users.index' => 'Users',
        'admin.master.examination-types.index' => 'Jenis Pemeriksaan',
        'admin.master.report-master-templates.index' => 'Master Template',
        'admin.master.master-poli.index' => 'Master Poli',
        'admin.pendaftaran.registrasi.index' => 'Registrasi',
        'admin.pendaftaran.tindakan.index' => 'Pelayanan',
        'admin.configuration.index' => 'Configuration',
        'radiology.dashboard' => 'Dashboard Radiologi',
        'radiology.pemeriksaan.show' => 'Pemeriksaan',
        default => null
    };
    $displayTitle = $title ?? $mappedTitle;
@endphp

<title>
    {{ filled($displayTitle) ? config('app.name', 'Radiologi').' - '.$displayTitle : config('app.name', 'Radiologi') }}
</title>

<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=2">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=2">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
