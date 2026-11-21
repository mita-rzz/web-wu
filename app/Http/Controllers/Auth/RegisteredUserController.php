<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Closure;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => [
                'bail',
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[A-Za-z0-9_.-]+$/',
                'unique:'.User::class,
                // Unik tanpa memperhatikan huruf besar/kecil (SQLite membedakan, MySQL tidak).
                function (string $attribute, mixed $value, Closure $fail): void {
                    $sudahAda = User::query()
                        ->whereRaw('lower(username) = ?', [mb_strtolower($value)])
                        ->exists();

                    if ($sudahAda) {
                        $fail('Username sudah dipakai.');
                    }
                },
            ],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'username' => $request->username,
            'display_name' => $request->username,
            'email' => $request->email,
            'password_hash' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
