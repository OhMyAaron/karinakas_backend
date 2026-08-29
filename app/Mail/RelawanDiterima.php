<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RelawanDiterima extends Mailable
{
    use Queueable, SerializesModels;

    public $namaUser;
    public $jenis;
    public $kegiatan;

    public function __construct($namaUser, $jenis = null, $kegiatan = null)
    {
        $this->namaUser = $namaUser;
        $this->jenis    = $jenis;
        $this->kegiatan = $kegiatan;
    }

    public function build()
    {
        return $this->subject('Pendaftaran Relawan Anda Diterima — Karinakas')
                    ->view('emails.relawan-diterima');
    }
}
