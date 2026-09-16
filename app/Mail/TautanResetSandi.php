<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TautanResetSandi extends Mailable
{
    use Queueable, SerializesModels;

    public $namaUser;
    public $tautan;
    public $berlakuMenit;

    public function __construct($namaUser, $tautan, $berlakuMenit = 60)
    {
        $this->namaUser     = $namaUser;
        $this->tautan       = $tautan;
        $this->berlakuMenit = $berlakuMenit;
    }

    public function build()
    {
        return $this->subject('Penyetelan Ulang Kata Sandi — Karinakas')
                    ->view('emails.tautan-reset-sandi');
    }
}
