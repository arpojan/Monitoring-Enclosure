<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Controllers\SelectEnclosureController;
use App\Models\Enclosure;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// ─── AUTH ROUTES ──────────────────────────────────────────────
Route::get('/', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->route('enclosure.select');
    }

    return back()->withErrors([
        'email' => 'Email atau kata sandi salah.',
    ])->onlyInput('email');
})->name('login.post');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/register', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:6|confirmed',
    ], [
        'name.required' => 'Nama wajib diisi.',
        'email.required' => 'Email wajib diisi.',
        'email.email' => 'Format email tidak valid.',
        'email.unique' => 'Email sudah terdaftar.',
        'password.required' => 'Kata sandi wajib diisi.',
        'password.min' => 'Kata sandi minimal 6 karakter.',
        'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    Auth::login($user);

    return redirect()->route('enclosure.select');
})->name('register.post');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

// ─── ENCLOSURE ROUTES ─────────────────────────────────────────
// GET: Tampilkan halaman pilih kandang (data dari DB via Controller)
Route::get('/select-enclosure', [SelectEnclosureController::class, 'index'])->name('enclosure.select');

// POST: Simpan pilihan kandang aktif ke session → redirect ke dashboard
Route::post('/select-enclosure/post', [SelectEnclosureController::class, 'store'])->name('enclosure.select.post');

// POST: Buat kandang baru
Route::post('/select-enclosure/create', [SelectEnclosureController::class, 'create'])->name('enclosure.select.create');

// POST: Regenerate device key kandang (dilindungi auth)
Route::post('/select-enclosure/{id}/regenerate-key', [SelectEnclosureController::class, 'regenerateKey'])
    ->middleware('auth')
    ->name('enclosure.regenerate-key');

// ─── DASHBOARD ROUTES ─────────────────────────────────────────
Route::get('/dashboard/{id?}', function ($id = null) {
    $enclosureName = 'Dasbor';
    if ($id) {
        $enclosure = Enclosure::find($id);
        if ($enclosure) {
            $enclosureName = $enclosure->name;
        }
    }
    return view('dashboard.index', compact('enclosureName'));
})->name('dashboard');

// ─── USER PROFILE ROUTES ────────────────────────────────────────
Route::put('/user/profile', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users,email,' . Auth::id(),
        'password' => 'nullable|string|min:6',
        'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    $user = Auth::user();
    $user->name = $request->name;
    $user->email = $request->email;
    if ($request->filled('password')) {
        $user->password = Hash::make($request->password);
    }
    $user->save();

    if ($request->hasFile('avatar')) {
        $file = $request->file('avatar');
        $filename = 'user_' . $user->id . '.jpg';
        // Menyimpan ke public/avatars
        $destinationPath = public_path('/avatars');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        // Menggunakan library Image Intervention jika ada, atau sekadar memindahkan file
        // Untuk amannya, kita move file upload tersebut dan jadikan ekstensi .jpg (meski format asal beda)
        $file->move($destinationPath, $filename);
    }

    return redirect()->back()->with('success', 'Profil berhasil diperbarui.');
})->middleware('auth')->name('user.profile.update');

// ─── DEVELOPMENT / MISC ROUTES ────────────────────────────────
Route::get('/ojan', function () {
    return view('welcome');
})->name('ojan');