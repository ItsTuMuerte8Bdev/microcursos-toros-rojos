<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Usuario;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $usuario;
    public $token;

    /**
     * Create a new message instance.
     */
    public function __construct(Usuario $usuario, $token)
    {
        $this->usuario = $usuario;
        $this->token = $token;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Verifica tu correo - Microcursos')
                    ->view('emails.verify')
                    ->with([
                        'nombre' => $this->usuario->nombre,
                        'token' => $this->token,
                    ]);
    }
}
