<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $asunto;
    public $password;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($asunto,$password)
    {
        //
        $this->asunto = $asunto;
        $this->password = $password;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->asunto)->view('PasswordEmail');
    }
}
