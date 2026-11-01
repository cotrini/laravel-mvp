<?php

namespace App\Mail;

use App\Modules\Property\Models\Property;
use App\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppraisalCompanyRequest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Property $property,
        public User $owner,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de avalúo comercial: '.$this->property->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.appraisal-company-request',
        );
    }
}
