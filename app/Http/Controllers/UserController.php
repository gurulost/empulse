<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Services\EmailService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\Facades\Image;

class UserController extends Controller
{
    public function changePassword()
    {
        return view('change-password');
    }

    public function profile()
    {
        return view('profile.profile_edit');
    }

    public function addAvatar()
    {
        return view('profile.add_avatar');
    }

    public function storeAvatar(ProfileRequest $request)
    {
        $user = $request->user();
        $disk = Storage::disk((string) config('filesystems.avatar_disk', 'public'));
        $newPath = 'avatars/'.Str::uuid().'.jpg';

        try {
            $encoded = Image::make($request->file('image'))
                ->orientate()
                ->fit(250, 250)
                ->encode('jpg', 85);
            if (! $disk->put($newPath, (string) $encoded)) {
                throw new \RuntimeException('Avatar storage write failed.');
            }
            $oldPath = $user->image;
            $user->forceFill(['image' => $newPath])->save();
            if ($oldPath) {
                $disk->delete($oldPath);
            }
        } catch (\Throwable $exception) {
            $disk->delete($newPath);
            report($exception);

            return back()->withErrors(
                'Your avatar could not be processed safely. Try a smaller JPG or PNG image.'
            );
        }

        return redirect()->route('profile');
    }

    public function deleteAvatar()
    {
        $user = Auth::user();
        $image = $user->image;

        if ($image !== null) {
            Storage::disk((string) config('filesystems.avatar_disk', 'public'))
                ->delete($image);
        }

        $user->forceFill(['image' => null])->save();

        return redirect()->back();
    }

    public function editPassword(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => ['nullable', 'required_with:new_pass', 'string'],
            'new_pass' => ['nullable', 'string', 'min:12', 'same:conf_new_pass'],
            'conf_new_pass' => ['nullable', 'string'],
        ]);

        if (! empty($validated['new_pass'])
            && ! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'status' => 422,
                'message' => 'The current password is incorrect.',
            ], 422);
        }

        $oldEmail = $user->email;
        $name = trim($validated['name']);
        $email = trim($validated['email']);

        DB::transaction(function () use ($user, $oldEmail, $name, $email, $validated) {
            $user->name = $name;
            $user->email = $email;

            if (! empty($validated['new_pass'])) {
                $user->password = Hash::make($validated['new_pass']);
            }

            $user->save();

            DB::table('company_worker')
                ->where('company_id', $user->company_id)
                ->where('email', $oldEmail)
                ->update([
                    'name' => $name,
                    'email' => $email,
                ]);

            if ((int) $user->role === 1 && $user->company_id !== null) {
                DB::table('companies')
                    ->where('id', $user->company_id)
                    ->update([
                        'manager' => $name,
                        'manager_email' => $email,
                    ]);
            }
        });

        return response()->json([
            'status' => 200,
            'message' => 'Your profile has been updated.',
        ]);
    }

    public function sendLetter($email, $name, $token)
    {
        $emailService = app(EmailService::class);
        $result = $emailService->sendPasswordReset($email, $name, $token);

        return $result['status'] === 200 ? true : ($result['message'] ?? 'Email send failed');
    }

    // Removed custom password reset methods (using Laravel's Password Broker)

}
