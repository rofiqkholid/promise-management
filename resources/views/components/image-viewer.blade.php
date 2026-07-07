@props([
    'id' => 'image-viewer',
    'src' => '',
    'alt' => 'Image',
    'class' => '',
    'imgClass' => '',
    'placeholderId' => 'viewer-placeholder',
    'placeholderText' => 'No image available',
    'placeholderSubtext' => '',
])

@once
    <!-- Viewer.js CSS and JS CDN (Fully client-side, compatible with PHP 8.2+) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.js"></script>
    <style>
        .viewer-container {
            font-family: inherit;
        }
        .viewer-button {
            background-color: rgba(15, 23, 42, 0.6) !important;
        }
        .viewer-button:hover {
            background-color: rgba(15, 23, 42, 0.8) !important;
        }
    </style>
@endonce

<div id="{{ $id }}-container" class="relative w-full h-full flex flex-col items-center justify-center {{ $class }}">
    <img id="{{ $id }}" 
         src="{{ $src }}" 
         alt="{{ $alt }}" 
         class="hidden cursor-zoom-in max-w-full max-h-60 object-contain rounded-xs {{ $imgClass }}">
    
    <div id="{{ $placeholderId }}" class="text-slate-400 dark:text-slate-600 flex flex-col items-center justify-center text-center">
        <i class="fa-regular fa-image text-4xl mb-2.5"></i>
        <p class="text-xs font-semibold">{{ $placeholderText }}</p>
        @if($placeholderSubtext)
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 max-w-[180px]">{{ $placeholderSubtext }}</p>
        @endif
    </div>
</div>

<script>
    (function() {
        let viewerInstance = null;

        window.initializeImageViewer = function (srcVal) {
            const imgElement = document.getElementById('{{ $id }}');
            const placeholder = document.getElementById('{{ $placeholderId }}');
            if (!imgElement) return;

            if (viewerInstance) {
                viewerInstance.destroy();
                viewerInstance = null;
            }

            if (srcVal && srcVal.trim() !== '') {
                imgElement.src = srcVal;
                imgElement.classList.remove('hidden');
                if (placeholder) placeholder.classList.add('hidden');
                
                viewerInstance = new Viewer(imgElement, {
                    inline: false,
                    button: true,
                    navbar: false,
                    title: false,
                    toolbar: {
                        zoomIn: 1,
                        zoomOut: 1,
                        oneToOne: 1,
                        reset: 1,
                        prev: 0,
                        play: 0,
                        next: 0,
                        rotateLeft: 1,
                        rotateRight: 1,
                        flipHorizontal: 1,
                        flipVertical: 1,
                    },
                    tooltip: true,
                    transition: true,
                    fullscreen: true,
                    keyboard: true
                });
            } else {
                imgElement.src = '';
                imgElement.classList.add('hidden');
                if (placeholder) placeholder.classList.remove('hidden');
            }
        };
    })();
</script>
