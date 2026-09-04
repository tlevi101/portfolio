{{-- Sidebar preview, rendered as a schema component beside the CV form. --}}
@php($livewire = $getLivewire())

<div class="cv-preview-pane" style="position: sticky; top: 1rem;">
    @include('filament.cv.preview-frame', [
        'url' => $livewire->getCvPreviewUrl(),
        'standalone' => false,
    ])
</div>
