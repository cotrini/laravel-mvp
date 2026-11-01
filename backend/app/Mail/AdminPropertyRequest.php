<?php

namespace App\Mail;

use App\Modules\Property\Models\PropertyRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AdminPropertyRequest extends Mailable
{
    public function __construct(public PropertyRequest $propertyRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva solicitud para una propiedad',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.admin-property-request',
        );
    }
}
