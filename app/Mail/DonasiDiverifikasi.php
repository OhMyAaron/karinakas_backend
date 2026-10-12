<?php

namespace App\Mail;

use App\Models\Donasi;
use App\Support\TemplateNotifikasi;
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

    /** Susun surel dari satu donasi (relasi user & kegiatan dimuat bila belum). */
    public static function dari(Donasi $donasi): self
    {
        $donasi->loadMissing(['user', 'kegiatan']);

        return new self(
            $donasi->user?->nama_lengkap,
            $donasi->formatted_jumlah,
            $donasi->tanggal_donasi?->locale('id')->translatedFormat('d F Y'),
            $donasi->metode_pembayaran,
            $donasi->tujuan_donasi,
            $donasi->kegiatan?->judul,
            $donasi->id
        );
    }

    /** Judul & isi surel dari templat "donatur" yang disunting admin. */
    public function tersusun(): array
    {
        return TemplateNotifikasi::render('donatur', [
            'nama'          => $this->namaUser,
            'nominal'       => $this->jumlah,
            'tanggal'       => $this->tanggal,
            'metode'        => $this->metode ?: '-',
            'tujuan'        => $this->tujuan ?: '-',
            'kegiatan'      => $this->kegiatan ?: '-',
            'id_transaksi'  => $this->idTransaksi ?: '-',
        ]);
    }

    public function judulSurel(): string
    {
        return $this->tersusun()['judul'];
    }

    public function build()
    {
        $surel = $this->tersusun();

        return $this->subject($surel['judul'])
                    ->view('emails.donasi-diverifikasi')
                    ->with(['judulSurel' => $surel['judul'], 'isiSurel' => $surel['isi']]);
    }
}
