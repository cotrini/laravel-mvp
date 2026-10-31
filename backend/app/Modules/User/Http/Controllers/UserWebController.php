<?php

namespace App\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Property\Models\Property;
use App\Modules\User\Models\User;
use App\Modules\User\Rules\ColombianPhoneNumber;
use App\Modules\User\Support\ColombianPhoneNumber as ColombianPhoneNumberFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserWebController extends Controller
{
    public function showRegisterForm(): View
    {
        return view('user::auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $normalizedPhoneNumber = ColombianPhoneNumberFormatter::normalize($request->input('phone_number'));

        if (is_string($normalizedPhoneNumber)) {
            $request->merge(['phone_number' => $normalizedPhoneNumber]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_number' => ['required', 'string', new ColombianPhoneNumber, 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create(collect($validated)->only(['name', 'email', 'phone_number', 'password'])->all());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', '¡Cuenta creada con éxito!');
    }

    public function showLoginForm(): View
    {
        return view('user::auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $destination = $request->user()->isAdmin() || $request->user()->isFilmmaker()
                ? 'dashboard.index'
                : 'properties.index';

            return redirect()->route($destination)->with('success', '¡Bienvenido de nuevo!');
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function adminIndex(): View
    {
        $users = User::query()
            ->with('assignedProperties')
            ->orderBy('name')
            ->paginate(15);
        $properties = Property::query()
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('user::admin.index', compact('users', 'properties'));
    }

    public function adminStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'role' => ['required', Rule::in([
                User::ROLE_USER,
                User::ROLE_ADMIN,
                User::ROLE_FILMMAKER,
            ])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($validated): void {
            $user = User::create(collect($validated)->only(['name', 'email', 'password'])->all());
            $user->role = $validated['role'];
            $user->save();
        });

        return redirect()->route('admin.users.index')->with('success', 'La cuenta se creó correctamente.');
    }

    public function assignProperties(Request $request, User $managedUser): RedirectResponse
    {
        abort_unless($managedUser->isFilmmaker(), 404);

        $validated = $request->validate([
            'property_ids' => ['nullable', 'array'],
            'property_ids.*' => ['required', 'integer', 'distinct', 'exists:properties,id'],
        ]);

        DB::transaction(function () use ($managedUser, $validated): void {
            $managedUser->assignedProperties()->sync($validated['property_ids'] ?? []);
        });

        return redirect()->route('admin.users.index')->with('success', 'Las propiedades asignadas se actualizaron.');
    }
}
