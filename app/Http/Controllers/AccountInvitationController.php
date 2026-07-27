<?php

namespace App\Http\Controllers;

use App\Services\AccountInvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountInvitationController extends Controller
{
    public function __construct(protected AccountInvitationService $invitations) {}

    public function show(string $token)
    {
        $invitation = $this->invitations->resolve($token);

        return view('auth.accept-invitation', [
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }

    public function accept(Request $request, string $token)
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $invitation = $this->invitations->resolve($token);
        $user = $this->invitations->accept($invitation, $validated['password']);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(
            $user->hasCapability('employee.dashboard')
                ? route('employee.dashboard')
                : route('home')
        );
    }
}
