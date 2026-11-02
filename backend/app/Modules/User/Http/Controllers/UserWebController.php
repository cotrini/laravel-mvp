<?php

namespace App\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Property\Models\Property;
use App\Modules\User\Models\User;
use App\Modules\User\Rules\ColombianPhoneNumber;
use App\Modules\User\Support\ColombianPhoneNumber as ColombianPhoneNumberFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class UserWebController extends Controller
{
    private const EMAIL_VERIFICATION_RESEND_LIMIT_SECONDS = 60;

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
        $user->email_verification_required = true;
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();
        $emailSent = $this->sendVerificationNotification($user);

        return redirect()->route('verification.notice')
            ->with(
                $emailSent ? 'success' : 'error',
                $emailSent
                    ? 'Te enviamos un enlace para verificar tu correo electrónico.'
                    : 'Tu cuenta fue creada, pero no pudimos entregar el correo. Intenta reenviarlo cuando termine el contador.',
            );
    }

    public function showEmailVerificationNotice(Request $request): View
    {
        $resendCooldownSeconds = RateLimiter::availableIn(
            $this->emailVerificationRateLimitKey($request->user()),
        );

        return view('user::auth.verify-email', compact('resendCooldownSeconds'));
    }

    public function verifyEmail(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('dashboard.index')->with('success', 'Tu correo electrónico fue verificado.');
    }

    public function resendEmailVerification(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard.index'));
        }

        $rateLimitKey = $this->emailVerificationRateLimitKey($user);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $secondsUntilRetry = RateLimiter::availableIn($rateLimitKey);

            return back()
                ->with('error', "Ya enviamos una solicitud recientemente. Intenta de nuevo en {$secondsUntilRetry} segundos.");
        }

        $emailSent = $this->sendVerificationNotification($user);

        return back()->with(
            $emailSent ? 'success' : 'error',
            $emailSent
                ? 'Te enviamos un nuevo enlace de verificación.'
                : 'No pudimos entregar el correo. Revisa la configuración de SMTP/Brevo e intenta de nuevo cuando termine el contador.',
        );
    }

    public function showLoginForm(): View
    {
        return view('user::auth.login');
    }

    public function showCompletePhoneForm(): View
    {
        return view('user::auth.complete-phone');
    }

    public function completePhone(Request $request): RedirectResponse
    {
        $normalizedPhoneNumber = ColombianPhoneNumberFormatter::normalize($request->input('phone_number'));

        if (is_string($normalizedPhoneNumber)) {
            $request->merge(['phone_number' => $normalizedPhoneNumber]);
        }

        $validated = $request->validate([
            'phone_number' => [
                'required',
                'string',
                new ColombianPhoneNumber,
                Rule::unique('users', 'phone_number')->ignore($request->user()),
            ],
        ]);

        $request->user()->phone_number = $validated['phone_number'];
        $request->user()->save();
        $request->session()->forget('phone_completion_required');

        return redirect()->intended(route('dashboard.index'))
            ->with('success', 'Tu número de teléfono quedó guardado.');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if ($request->user()->is_banned) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Esta cuenta está baneada y no puede iniciar sesión.',
                ])->onlyInput('email');
            }

            $destination = $request->user()->isAdmin()
                || $request->user()->isFilmmaker()
                || $request->user()->isAppraiser()
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

    private function sendVerificationNotification(User $user): bool
    {
        RateLimiter::hit(
            $this->emailVerificationRateLimitKey($user),
            self::EMAIL_VERIFICATION_RESEND_LIMIT_SECONDS,
        );

        try {
            $user->sendEmailVerificationNotification();

            return true;
        } catch (TransportExceptionInterface $exception) {
            report($exception);
            Log::error('No fue posible enviar el correo de verificación.', [
                'user_id' => $user->getKey(),
                'transport_exception' => $exception::class,
            ]);

            return false;
        }
    }

    private function emailVerificationRateLimitKey(User $user): string
    {
        return "email-verification-resend:{$user->getAuthIdentifier()}";
    }

    public function adminIndex(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'assignment_user_id' => ['nullable', 'integer', 'exists:users,id', 'required_with:property_code'],
            'property_code' => ['nullable', 'string', 'max:32', 'required_with:assignment_user_id'],
        ]);
        $search = trim($validated['search'] ?? '');

        $users = User::query()
            ->with(['assignedProperties', 'assignedAppraisalProperties'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();
        $assignmentUserId = isset($validated['assignment_user_id'])
            ? (int) $validated['assignment_user_id']
            : null;
        $assignmentCode = strtoupper(trim($validated['property_code'] ?? ''));
        $assignmentLookupPerformed = $assignmentUserId !== null && $assignmentCode !== '';
        $assignmentProperty = null;

        if ($assignmentLookupPerformed) {
            $assignmentUser = User::query()->findOrFail($assignmentUserId);
            abort_unless($assignmentUser->isFilmmaker() || $assignmentUser->isAppraiser(), 404);
            $assignmentProperty = $this->findPropertyByCode($assignmentCode);
        }

        return view('user::admin.index', compact(
            'users',
            'search',
            'assignmentUserId',
            'assignmentCode',
            'assignmentLookupPerformed',
            'assignmentProperty',
        ));
    }

    public function adminUpdate(Request $request, User $managedUser): RedirectResponse
    {
        $normalizedPhoneNumber = ColombianPhoneNumberFormatter::normalize($request->input('phone_number'));

        if (is_string($normalizedPhoneNumber)) {
            $request->merge(['phone_number' => $normalizedPhoneNumber]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($managedUser)],
            'phone_number' => ['nullable', 'string', new ColombianPhoneNumber, Rule::unique('users', 'phone_number')->ignore($managedUser)],
            'role' => ['required', Rule::in([
                User::ROLE_USER,
                User::ROLE_ADMIN,
                User::ROLE_FILMMAKER,
                User::ROLE_APPRAISER,
            ])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        abort_if(
            $request->user()->is($managedUser) && $validated['role'] !== User::ROLE_ADMIN,
            403,
            'No puedes cambiar tu propio rol de administrador.',
        );

        $emailChanged = $managedUser->email !== $validated['email'];
        $roleChanged = $managedUser->role !== $validated['role'];
        $managedUser->fill(collect($validated)->only(['name', 'email', 'phone_number'])->all());
        $managedUser->role = $validated['role'];

        if (! empty($validated['password'])) {
            $managedUser->password = $validated['password'];
        }

        $requiresEmailVerification = $managedUser->requiresEmailVerification();

        if ($emailChanged && $requiresEmailVerification) {
            $managedUser->email_verified_at = null;
        }

        $managedUser->save();

        if ($roleChanged) {
            if (! $managedUser->isFilmmaker()) {
                $managedUser->assignedProperties()->detach();
            }

            if (! $managedUser->isAppraiser()) {
                $managedUser->assignedAppraisalProperties()->detach();
            }
        }

        if ($emailChanged && $requiresEmailVerification) {
            $managedUser->sendEmailVerificationNotification();
        }

        return redirect()->route('admin.users.index')->with('success', 'La cuenta se actualizó correctamente.');
    }

    public function toggleBan(Request $request, User $managedUser): RedirectResponse
    {
        abort_if($request->user()->is($managedUser), 403, 'No puedes modificar el estado de tu propia cuenta.');

        $managedUser->is_banned = ! $managedUser->is_banned;
        $managedUser->save();

        return redirect()->route('admin.users.index')->with(
            'success',
            $managedUser->is_banned ? 'La cuenta fue baneada.' : 'La cuenta fue desbloqueada.',
        );
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
                User::ROLE_APPRAISER,
            ])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create(collect($validated)->only(['name', 'email', 'password'])->all());
            $user->role = $validated['role'];
            $user->save();

            return $user;
        });

        return redirect()->route('admin.users.index')->with('success', 'La cuenta se creó correctamente.');
    }

    public function assignProperty(Request $request, User $managedUser): RedirectResponse
    {
        abort_unless($managedUser->isFilmmaker() || $managedUser->isAppraiser(), 404);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'property_code' => ['required', 'string', 'max:32', 'regex:/\ACSC-\d{8,}\z/i'],
        ]);
        $propertyCode = strtoupper(trim($validated['property_code']));
        $property = $this->findPropertyByCode($propertyCode);

        if ($property === null) {
            return back()
                ->withErrors(['property_code' => 'No encontramos un inmueble con ese código.'])
                ->withInput();
        }

        $assignedProperties = $managedUser->isAppraiser()
            ? $managedUser->assignedAppraisalProperties()
            : $managedUser->assignedProperties();
        $wasAlreadyAssigned = $assignedProperties->whereKey($property->getKey())->exists();

        if (! $wasAlreadyAssigned) {
            DB::transaction(function () use ($assignedProperties, $property): void {
                $assignedProperties->syncWithoutDetaching([$property->getKey()]);
            });
        }

        return redirect()->route('admin.users.index', array_filter([
            'search' => $validated['search'] ?? null,
            'page' => $validated['page'] ?? null,
            'assignment_user_id' => $managedUser->id,
            'property_code' => $propertyCode,
        ], fn ($value) => $value !== null && $value !== ''))->with(
            'success',
            $wasAlreadyAssigned
                ? 'Este inmueble ya estaba asignado a la cuenta.'
                : 'El inmueble fue asignado correctamente.',
        );
    }

    public function removeAssignedProperty(
        User $managedUser,
        Property $property,
    ): RedirectResponse {
        abort_unless($managedUser->isFilmmaker() || $managedUser->isAppraiser(), 404);

        $assignedProperties = $managedUser->isAppraiser()
            ? $managedUser->assignedAppraisalProperties()
            : $managedUser->assignedProperties();

        abort_unless($assignedProperties->whereKey($property->getKey())->exists(), 404);

        $assignedProperties->detach($property->getKey());

        return back()->with('success', 'Se retiró el inmueble de las asignaciones de la cuenta.');
    }

    private function findPropertyByCode(string $propertyCode): ?Property
    {
        if (preg_match('/\ACSC-(\d{8,})\z/i', $propertyCode, $matches) !== 1) {
            return null;
        }

        $property = Property::query()
            ->with('user:id,name')
            ->find((int) $matches[1]);

        if ($property === null || ! hash_equals($property->identification_code, strtoupper($propertyCode))) {
            return null;
        }

        return $property;
    }
}
