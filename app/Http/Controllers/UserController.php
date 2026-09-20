<?php

namespace App\Http\Controllers;

use App\Events\SecurityAuditEvent;
use App\Models\Realm;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Create the controller instance.
     */
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $realms = [];
        if (auth()->user()->is_admin) {
            $realms = Realm::all(['id', 'name']);
        }

        return view('users.edit', compact('user', 'realms'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|max:190',
            'email' => 'required|email|unique:users,email,'.$user->id.'|max:190',
            'realm_id' => 'nullable|integer|exists:realms,id',
        ]);
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        // if you will allow chaning the user type from here, check that only admins can make other admins.
        // Only Global Super Admins can reassign a user's realm
        if (auth()->user()->is_admin && isset($validated['realm_id'])) {
            $user->realm_id = $validated['realm_id'];
        }
        $dirtyFields = array_keys($user->getDirty());
        if ($user->save()) {
            event(new SecurityAuditEvent(
                action: 'user.profile_updated',
                description: "User profile updated for user ID: {$user->id} ({$user->email})",
                userId: auth()->id(),
                realmId: $user->realm_id,
                context: [
                    'target_user_id' => $user->id,
                    'updated_fields' => $dirtyFields,
                    'is_admin_action' => auth()->user()->is_admin,
                ]
            ));
            flash(__('Profile updated'))->success();
        } else {
            flash(__('Something went wrong, please retry.'))->error();
        }

        return redirect()->route('users.edit', $user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
