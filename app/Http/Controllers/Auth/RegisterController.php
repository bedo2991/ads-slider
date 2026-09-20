<?php

namespace App\Http\Controllers\Auth;

use App\Events\SecurityAuditEvent;
use App\Http\Controllers\Controller;
use App\Models\Realm;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $currentUser = auth()->user();
        $allowedRoles = ['member'];
        if ($currentUser?->is_admin) {
            $allowedRoles = ['member', 'realm_admin', 'admin'];
        } elseif ($currentUser?->is_realm_admin) {
            $allowedRoles = ['member'];
        }

        return Validator::make($data, [
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'string', 'email', 'max:190', 'unique:users'],
            'password' => ['required', Password::defaults()],
            'realm_id' => [
                $currentUser?->is_admin ? 'nullable' : 'prohibited',
                'integer',
                'exists:realms,id',
            ],
            'user_type' => ['required', Rule::in($allowedRoles)],
        ]);
    }

    public function showRegistrationForm()
    {
        $realms = [];
        if (auth()->user()->is_admin) {
            $realms = Realm::all(['id', 'name']);
        }

        return view('auth.register', compact('realms'));
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @return User
     */
    protected function create(array $data)
    {
        $currentUser = auth()->user();
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'user_type' => $data['user_type'],
            'realm_id' => $currentUser->is_admin ? ($data['realm_id'] ?? $currentUser->realm_id) : $currentUser->realm_id,
            'password' => Hash::make($data['password']),
        ]);

        event(new SecurityAuditEvent(
            action: 'user.registered',
            description: "New user registered: {$user->email} (Role: {$user->user_type}, Realm ID: {$user->realm_id}) by user ID: ".auth()->id(),
            userId: auth()->id(),
            realmId: $user->realm_id,
            context: [
                'registered_user_id' => $user->id,
                'registered_user_email' => $user->email,
                'role' => $user->user_type,
                'creator_role' => $currentUser?->user_type,
            ]
        ));

        return $user;
    }
}
