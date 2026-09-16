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
      <h1 style="font-size:22px; text-align:center; margin:12px 0 6px;">Penyetelan Ulang Kata Sandi</h1>

      <p style="font-size:16px; line-height:1.7;">Halo <strong>{{ $namaUser }}</strong>,</p>
      <p style="font-size:16px; line-height:1.7;">
        Kami menerima permintaan penyetelan ulang kata sandi untuk akun Anda. Tekan tombol di bawah ini
        untuk membuat kata sandi baru.
      </p>

      <div style="text-align:center; margin:28px 0;">
        <a href="{{ $tautan }}"
           style="display:inline-block; background:#C0392B; color:#ffffff; text-decoration:none; font-size:16px; font-weight:700; padding:15px 34px; border-radius:999px;">
          Setel Ulang Kata Sandi
        </a>
      </div>

      <p style="font-size:14px; line-height:1.7; color:#555;">
        Tombol tidak berfungsi? Salin dan tempel tautan berikut pada peramban Anda:
      </p>
      <p style="font-size:13px; line-height:1.6; word-break:break-all; background:#FBF6EF; border-radius:10px; padding:12px 16px;">
        <a href="{{ $tautan }}" style="color:#C0392B;">{{ $tautan }}</a>
      </p>

      <p style="font-size:16px; line-height:1.7;">
        Tautan ini berlaku <strong>{{ $berlakuMenit }} menit</strong> dan hanya dapat digunakan satu kali.
      </p>

      <div style="background:#FBF6EF; border-radius:12px; padding:16px 20px; margin:24px 0; font-size:14px; color:#555;">
        <strong>Bukan Anda yang meminta?</strong><br>
        Abaikan surel ini. Kata sandi Anda tidak akan berubah selama tautan di atas tidak dibuka.
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
