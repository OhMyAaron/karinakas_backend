<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0; background:#f5f5f5; font-family:'Segoe UI',Arial,sans-serif; color:#2b2b2b;">
  <div style="max-width:560px; margin:0 auto; padding:24px;">
    <div style="background:#C0392B; border-radius:16px 16px 0 0; padding:28px 32px; text-align:center;">
      <div style="color:#fff; font-weight:800; letter-spacing:2px; font-size:20px;">KARINAKAS</div>
      <div style="color:#ffd9d9; font-size:13px; margin-top:4px;">Yayasan Karinakas</div>
    </div>

    <div style="background:#ffffff; border-radius:0 0 16px 16px; padding:32px; box-shadow:0 4px 20px rgba(0,0,0,0.06);">
      <div style="text-align:center; margin-bottom:8px;">
        <div style="display:inline-block; width:64px; height:64px; line-height:64px; border-radius:50%; background:#E8F5E9; color:#1a6b3a; font-size:32px;">&#10003;</div>
      </div>
      <h1 style="font-size:22px; text-align:center; margin:12px 0 6px;">Donasi Terverifikasi</h1>

      <p style="font-size:16px; line-height:1.7;">Halo <strong>{{ $namaUser }}</strong>,</p>
      <p style="font-size:16px; line-height:1.7;">
        Donasi Anda sebesar <strong>{{ $jumlah }}</strong>
        @if($kegiatan) untuk kegiatan <strong>{{ $kegiatan }}</strong> @endif
        telah <strong style="color:#1a6b3a;">diverifikasi</strong> oleh tim Karinakas pada {{ $tanggal }}.
        Terima kasih atas kebaikan hati Anda.
      </p>

      <div style="background:#FBF6EF; border-radius:12px; padding:16px 20px; margin:24px 0; font-size:14px; color:#555;">
        <strong>Rincian donasi</strong>
        <table style="width:100%; margin-top:8px; font-size:14px; border-collapse:collapse;">
          @if($idTransaksi)
          <tr><td style="padding:4px 0; color:#777;">ID Transaksi</td><td style="padding:4px 0; text-align:right;">{{ $idTransaksi }}</td></tr>
          @endif
          <tr><td style="padding:4px 0; color:#777;">Jumlah</td><td style="padding:4px 0; text-align:right;">{{ $jumlah }}</td></tr>
          @if($metode)
          <tr><td style="padding:4px 0; color:#777;">Metode pembayaran</td><td style="padding:4px 0; text-align:right;">{{ $metode }}</td></tr>
          @endif
          @if($tujuan)
          <tr><td style="padding:4px 0; color:#777;">Tujuan</td><td style="padding:4px 0; text-align:right;">{{ $tujuan }}</td></tr>
          @endif
          <tr><td style="padding:4px 0; color:#777;">Kegiatan</td><td style="padding:4px 0; text-align:right;">{{ $kegiatan ?: 'Donasi umum' }}</td></tr>
        </table>
      </div>

      <p style="font-size:16px; line-height:1.7;">
        Donasi Anda akan digunakan untuk mendukung kegiatan sosial Yayasan Karinakas.
      </p>

      <div style="background:#FBF6EF; border-radius:12px; padding:16px 20px; margin:24px 0; font-size:14px; color:#555;">
        <strong>Berkah Dalem,</strong><br>Tim Yayasan Karinakas
      </div>

      <p style="font-size:12px; color:#999; text-align:center; margin-top:24px;">
        Email ini dikirim otomatis. Mohon tidak membalas email ini.
      </p>
    </div>
  </div>
</body>
</html>
