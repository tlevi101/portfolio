{{-- Same preview, opened from the header's eye action. This is the fallback on
     screens too narrow for the side-by-side layout. --}}
@include('filament.cv.preview-frame', [
    'url' => $url,
    'standalone' => true,
])
