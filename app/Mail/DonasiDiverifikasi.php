<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DonasiDiverifikasi extends Mailable
{
    use Queueable, SerializesModels;

    public $namaUser;
    public $jumlah;
    public $tanggal;
    public $metode;
    public $tujuan;
    public $kegiatan;
    public $idTransaksi;

    public function __construct($namaUser, $jumlah, $tanggal, $metode = null, $tujuan = null, $kegiatan = null, $idTransaksi = null)
    {
        $this->namaUser    = $namaUser;
        $this->jumlah      = $jumlah;
        $this->tanggal     = $tanggal;
        $this->metode      = $metode;
        $this->tujuan      = $tujuan;
        $this->kegiatan    = $kegiatan;
        $this->idTransaksi = $idTransaksi;
    }

    public function build()
    {
        return $this->subject('Donasi Anda Telah Diverifikasi — Karinakas')
                    ->view('emails.donasi-diverifikasi');
    }
}
