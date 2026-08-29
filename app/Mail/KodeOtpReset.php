<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class KodeOtpReset extends Mailable
{
    use Queueable, SerializesModels;

    public $namaUser;
    public $kode;
    public $berlakuMenit;

    public function __construct($namaUser, $kode, $berlakuMenit = 10)
    {
        $this->namaUser     = $namaUser;
        $this->kode         = $kode;
        $this->berlakuMenit = $berlakuMenit;
    }

    public function build()
    {
        return $this->subject('Kode Verifikasi Pemulihan Kata Sandi — Karinakas')
                    ->view('emails.kode-otp-reset');
    }
}
