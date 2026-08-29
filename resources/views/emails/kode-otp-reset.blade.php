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
        <div style="display:inline-block; width:64px; height:64px; line-height:64px; border-radius:50%; background:#FFF3E6; color:#d97000; font-size:30px;">&#128273;</div>
      </div>
      <h1 style="font-size:22px; text-align:center; margin:12px 0 6px;">Pemulihan Kata Sandi</h1>

      <p style="font-size:16px; line-height:1.7;">Halo <strong>{{ $namaUser }}</strong>,</p>
      <p style="font-size:16px; line-height:1.7;">
        Kami menerima permintaan pemulihan kata sandi untuk akun Anda. Masukkan kode verifikasi
        berikut pada halaman pemulihan kata sandi:
      </p>

      <div style="text-align:center; margin:24px 0;">
        <div style="display:inline-block; background:#FFF5F5; border:2px dashed #C0392B; border-radius:14px; padding:18px 32px;">
          <div style="font-size:34px; font-weight:800; letter-spacing:10px; color:#C0392B;">{{ $kode }}</div>
        </div>
      </div>

      <p style="font-size:16px; line-height:1.7;">
        Kode ini hanya berlaku <strong>{{ $berlakuMenit }} menit</strong> dan hanya dapat digunakan satu kali.
      </p>

      <div style="background:#FBF6EF; border-radius:12px; padding:16px 20px; margin:24px 0; font-size:14px; color:#555;">
        <strong>Bukan Anda yang meminta?</strong><br>
        Abaikan surel ini. Kata sandi Anda tidak akan berubah selama kode di atas tidak dimasukkan.
      </div>

      <div style="background:#FBF6EF; border-radius:12px; padding:16px 20px; margin:24px 0; font-size:14px; color:#555;">
        <strong>Berkah Dalem,</strong><br>Tim Yayasan Karinakas
      </div>
    </div>

    <p style="text-align:center; font-size:12px; color:#888; margin-top:16px;">
      Surel ini dikirim otomatis oleh sistem, mohon tidak membalas.
    </p>
  </div>
</body>
</html>
