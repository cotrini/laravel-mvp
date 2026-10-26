<?php

namespace App\Mail;

use App\Modules\Property\Models\Property;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PropertyMediaRequest extends Mailable
{
    public function __construct(
        public Property $property,
        public string $mediaLabel,
        public string $propertyUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Una persona vio tu propiedad y solicita más contenido',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.property-media-request',
        );
    }
}
