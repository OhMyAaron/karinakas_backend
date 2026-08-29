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
      <h1 style="font-size:22px; text-align:center; margin:12px 0 6px;">Pendaftaran Diterima!</h1>

      <p style="font-size:16px; line-height:1.7;">Halo <strong>{{ $namaUser }}</strong>,</p>
      <p style="font-size:16px; line-height:1.7;">
        Selamat! Pendaftaran Anda sebagai <strong>relawan{{ $jenis ? ' ' . $jenis : '' }}</strong>
        @if($kegiatan) untuk kegiatan <strong>{{ $kegiatan }}</strong> @endif
        telah <strong style="color:#1a6b3a;">diterima</strong> oleh tim Karinakas.
      </p>
      <p style="font-size:16px; line-height:1.7;">
        Terima kasih atas kesediaan Anda berbagi waktu dan tenaga. Kami akan menghubungi Anda
        untuk informasi kegiatan selanjutnya. Sampai jumpa dalam pelayanan bersama! 🙏
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
