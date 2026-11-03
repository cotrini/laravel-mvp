@php
    $shareUrl = $url;
    $shareText = 'Mira esta propiedad: '.$title;
    $whatsappUrl = 'https://wa.me/?text='.rawurlencode($shareText.' '.$shareUrl);
    $facebookUrl = 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($shareUrl);
    $twitterUrl = 'https://twitter.com/intent/tweet?text='.rawurlencode($shareText).'&url='.rawurlencode($shareUrl);
@endphp

<div class="my-4 flex flex-wrap items-center gap-2" data-share-root data-share-url="{{ $shareUrl }}" data-share-title="{{ $title }}" data-share-text="{{ $shareText }}">
    <span class="text-sm font-semibold text-slate-700">Compartir:</span>

    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-full bg-green-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-600">WhatsApp</a>
    <a href="{{ $facebookUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-full bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700">Facebook</a>
    <a href="{{ $twitterUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-black">X / Twitter</a>
    <button type="button" data-share-copy class="inline-flex items-center rounded-full border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Copiar enlace</button>
    <button type="button" data-share-native hidden class="inline-flex items-center rounded-full border border-blue-600 bg-white px-3 py-1.5 text-sm font-medium text-blue-700 hover:bg-blue-50">Compartir…</button>

    <span data-share-feedback role="status" aria-live="polite" class="hidden rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-800">¡Enlace copiado!</span>
</div>

<script>
    (() => {
        const root = document.currentScript.previousElementSibling;
        if (!root) {
            return;
        }

        const { shareUrl, shareTitle, shareText } = root.dataset;
        const feedback = root.querySelector('[data-share-feedback]');
        const showFeedback = (message) => {
            feedback.textContent = message;
            feedback.classList.remove('hidden');
            clearTimeout(feedback.hideTimer);
            feedback.hideTimer = setTimeout(() => feedback.classList.add('hidden'), 2500);
        };

        const fallbackCopy = () => {
            const input = document.createElement('textarea');
            input.value = shareUrl;
            input.setAttribute('readonly', '');
            input.style.position = 'fixed';
            input.style.opacity = '0';
            document.body.appendChild(input);
            input.select();
            const copied = document.execCommand('copy');
            document.body.removeChild(input);
            return copied;
        };

        root.querySelector('[data-share-copy]').addEventListener('click', async () => {
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(shareUrl);
                    showFeedback('¡Enlace copiado!');
                    return;
                }
            } catch (error) {
                // Se intenta el método alternativo.
            }

            showFeedback(fallbackCopy() ? '¡Enlace copiado!' : 'No se pudo copiar el enlace');
        });

        if (navigator.share) {
            const nativeButton = root.querySelector('[data-share-native]');
            nativeButton.hidden = false;
            nativeButton.addEventListener('click', async () => {
                try {
                    await navigator.share({ title: shareTitle, text: shareText, url: shareUrl });
                } catch (error) {
                    // El usuario canceló el diálogo nativo.
                }
            });
        }
    })();
</script>
