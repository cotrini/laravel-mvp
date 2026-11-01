<?php

namespace App\Mail;

use App\Modules\Property\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OwnerMediaRequest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Property $property,
        public string $mediaType,
        public string $requesterName,
        public ?string $requesterEmail,
        public ?string $requesterPhone,
        public ?string $requestedMedia = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mediaType === 'media_package'
            ? 'Hay personas interesadas en tu inmueble'
            : 'Solicitud de contenido multimedia para tu inmueble');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.owner-media-request');
    }
}
