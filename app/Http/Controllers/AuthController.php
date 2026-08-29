<?php

namespace App\Http\Controllers;

use App\Mail\KodeOtpReset;
use App\Models\NotifikasiLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register - F9: Melakukan registrasi
     * Pengguna mengisi form registrasi
     */
    public function register(Request $request)
    {
        // Disinkron dengan form frontend: hanya nama, email, password yang wajib.
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            // min:8 disamakan dengan jalur ganti & reset sandi (NFR 3.4.4.1)
            'password' => 'required|string|min:8',
            'username' => 'nullable|string|max:50|unique:users,username',
            'jenis_kelamin' => 'nullable|string|in:Laki-laki,Perempuan',
            'tanggal_lahir' => 'nullable|date',
            'usia' => 'nullable|integer|min:0|max:150',
            'no_telepon' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'kota' => 'nullable|string|max:100',
            'tentang' => 'nullable|string',
            'asal_paroki' => 'nullable|string|max:100',
        ]);

        // Username opsional: auto-generate dari email bila tidak diisi
        $username = $validated['username']
            ?? \Illuminate\Support\Str::slug(explode('@', $validated['email'])[0], '') . rand(100, 999);

        $user = User::create([
            'nama_lengkap' => $validated['nama_lengkap'],
            'email' => $validated['email'],
            'username' => $username,
            'password' => Hash::make($validated['password']),
            'role' => 'user', // Default role
            'jenis_kelamin' => $validated['jenis_kelamin'] ?? null,
            'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
            'usia' => $validated['usia'] ?? null,
            'no_telepon' => $validated['no_telepon'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
            'kota' => $validated['kota'] ?? null,
            'tentang' => $validated['tentang'] ?? null,
            'asal_paroki' => $validated['asal_paroki'] ?? null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Login - F5: Melakukan login
     * Admin dan pengguna terdaftar
     */
    public function login(Request $request)
    {
        // Sinkron frontend: login pakai email. (username masih didukung sebagai alternatif)
        $request->validate([
            'email' => 'required_without:username|email',
            'username' => 'required_without:email|string',
            'password' => 'required|string',
        ]);

        $user = $request->filled('email')
            ? User::where('email', $request->email)->first()
            : User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'user' => $user,
            'token' => $token,
            'role' => $user->role,
        ]);
    }

    /**
     * Logout - F7: Melakukan logout
     * Admin dan pengguna terdaftar
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);
    }

    /**
     * Get authenticated user profile - F11: Menampilkan profil diri sendiri
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    }

    /**
     * Update profile - F35: Mengubah data diri di profile pengguna
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'nama_lengkap' => 'sometimes|string|max:100',
            'jenis_kelamin' => 'sometimes|string|in:Laki-laki,Perempuan',
            'tanggal_lahir' => 'sometimes|date',
            'usia' => 'sometimes|integer|min:0|max:150',
            'no_telepon' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'alamat' => 'sometimes|string',
            'kota' => 'nullable|string|max:100',
            'tentang' => 'nullable|string',
            'asal_paroki' => 'nullable|string|max:100',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Change password - F8: Melakukan perubahan password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password lama tidak sesuai.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah',
        ]);
    }

    /**
     * Forgot password - kirim OTP - F10: Mengubah password (kirim OTP ke email)
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $email = strtolower(trim($request->email));
        $user  = User::whereRaw('LOWER(email) = ?', [$email])->firstOrFail();

        // Kode 6 digit, disimpan di cache (CACHE_STORE=database) selama 10 menit.
        // Memakai cache agar tidak perlu tabel baru; kode hilang sendiri saat kedaluwarsa.
        $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put(self::otpKey($email), $kode, now()->addMinutes(10));

        // Kegagalan kirim surel tidak boleh menggagalkan permintaan (bandingkan NFR 3.4.2.2)
        $status = 'terkirim';
        try {
            Mail::to($user->email)->send(new KodeOtpReset($user->nama_lengkap, $kode, 10));
        } catch (\Throwable $e) {
            $status = 'gagal';
            Log::error('Gagal mengirim OTP pemulihan kata sandi: ' . $e->getMessage());
        }

        NotifikasiLog::record([
            'user_id'  => $user->id,
            'tipe'     => 'email',
            'penerima' => $user->email,
            'judul'    => 'Kode verifikasi pemulihan kata sandi',
            'isi'      => 'Kode OTP dikirim, berlaku 10 menit.',
            'trigger'  => 'password_reset_requested',
            'status'   => $status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kode verifikasi telah dikirim ke email Anda. Kode berlaku 10 menit.',
        ]);
    }

    /** Kunci cache penyimpan OTP pemulihan kata sandi. */
    private static function otpKey(string $email): string
    {
        return 'otp_reset:' . $email;
    }

    /**
     * Reset password with OTP
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $email    = strtolower(trim($request->email));
        $tersimpan = Cache::get(self::otpKey($email));

        if (!$tersimpan) {
            throw ValidationException::withMessages([
                'otp' => ['Kode verifikasi sudah kedaluwarsa. Silakan minta kode baru.'],
            ]);
        }

        if (!hash_equals((string) $tersimpan, (string) $request->otp)) {
            throw ValidationException::withMessages([
                'otp' => ['Kode verifikasi salah.'],
            ]);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->firstOrFail();
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Kode hanya berlaku sekali pakai
        Cache::forget(self::otpKey($email));

        // Cabut seluruh token aktif agar sesi lama tidak dapat dipakai lagi
        $user->tokens()->delete();

        NotifikasiLog::record([
            'user_id'  => $user->id,
            'tipe'     => 'system',
            'penerima' => $user->email,
            'judul'    => 'Kata sandi berhasil diubah',
            'isi'      => 'Kata sandi diperbarui melalui pemulihan kata sandi.',
            'trigger'  => 'password_reset_done',
            'status'   => 'terkirim',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi berhasil diperbarui. Silakan masuk dengan kata sandi baru.',
        ]);
    }
}