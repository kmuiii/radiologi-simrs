@extends('errors.layout', [
    'code' => $exception->getStatusCode() ?? 'Error',
    'title' => $exception->getMessage() ?: 'Terjadi kesalahan pada sistem.',
    'description' => 'Silakan coba kembali atau hubungi administrator sistem jika masalah berlanjut.',
])
