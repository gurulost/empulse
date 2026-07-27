<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\User;
use App\Services\EmailService;
use Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        $id = Auth::user()->id;
        if ($request->hasFile('image')) {
            $file = $request->file('image');

            $maxFileSize = 2 * 1024 * 1024;
            if ($file->getSize() > $maxFileSize) {
                $session = \Session::put('error-upload-avatar', 'The image size should not exceed 2MB.');

                return back()->with($session);
            }

            $allowedFormats = ['jpg', 'jpeg', 'png', 'gif'];
            if (! in_array(strtolower($file->getClientOriginalExtension()), $allowedFormats)) {
                $session = \Session::put('error-upload-avatar', 'The image format should be JPG, JPEG, PNG, or GIF.');

                return back()->with($session);
            }

            try {
                $avatarImage = User::findOrFail($id);
            } catch (ModelNotFoundException $ex) {
                $session = \Session::put('error-upload-avatar', $ex);

                return back()->with($session);
            }

            $img = $avatarImage->image;
            if ($img !== null) {
                $path = public_path("/upload/$img");
                if (file_exists($path)) {
                    @unlink($path);
                }
            }

            $image = $request->file('image');
            $name_gen = hexdec(uniqid()).'.'.$image->getClientOriginalExtension();
            Image::make($image)->resize(250, 250)->save(public_path('upload/'.$name_gen));
            User::where('id', $id)->update(
                ['image' => $name_gen]
            );

            return redirect()->route('profile');
        }
    }

    public function deleteAvatar()
    {
        $user = Auth::user();
        $image = $user->image;

        if ($image !== null) {
            $path = public_path('upload/'.basename($image));
            if (is_file($path)) {
                @unlink($path);
            }
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
