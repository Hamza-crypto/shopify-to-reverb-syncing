<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AlertMail extends Mailable
{
    public function build()
    {
        return $this->view('emails.alert')
            ->with([
                'name' => 'Your Name',
            ])
            ->to('your-email@example.com')
            ->subject('Welcome to Laravel');
    }
}
