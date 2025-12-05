<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Auth;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;
use App\Http\Requests\ProfileRequest;
use Illuminate\Support\Facades\Http;
use App\Imports\ManagerImport;
use App\Imports\ChiefImport;
use App\Imports\TeamleadImport;
use App\Exports\ManagerTableTemplate;
use App\Exports\ChiefTableTemplate;
use App\Exports\TeamleadTableTemplate;

class UserController extends Controller
{

    public function changePassword()
    {
        return view('change-password');
    }

    public function updatePassword(Request $request)
    {
        User::whereId(auth()->user()->id)->update([
            'password' => Hash::make($request->new_password)
        ]);

        return back()->with("status", "Password create success!");
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
            if (!in_array(strtolower($file->getClientOriginalExtension()), $allowedFormats)) {
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
                if (file_exists($path))  {
                    @unlink($path);
                }
            }

            $image = $request->file('image');
            $name_gen = hexdec(uniqid()) . '.' . $image->getClientOriginalExtension();
            Image::make($image)->resize(250, 250)->save(public_path('upload/' . $name_gen));
            User::where('id', $id)->update(
                ['image' => $name_gen]
            );

            return redirect()->route('profile');
        }
    }

    public function deleteAvatar($id)
    {
        $model = new User();
        $deleteAvatar = $model->deleteAvatarFunc($id);

        if($deleteAvatar['status'] === 500) {
            $session = \Session::put('delete_avatar_error', $deleteAvatar['message']);
            return back()->with($session);
        }

        return redirect()->back();
    }

    public function editPassword(Request $request) {
        try {
            if (!Auth::check()) {
                return response()->json(["status" => 401, 'message' => "Unauthorized"]);
            }

            $userEmail = Auth::user()->email;

            $name = trim($request->name);
            $email = trim($request->email);
            $new_pass = trim($request->new_pass);
            $conf_new_pass = trim($request->conf_new_pass);
            $company_title = null;

            if($request->company_title) {
                $company_title = $request->company_title;
            }

            $model = new User();
            $editPassword = $model->editPasswordFunc($userEmail, $name, $email, $new_pass, $conf_new_pass, $company_title);

            return response()->json($editPassword);
        } catch (\Exception $e) {
            return response()->json(["status" => 400, 'message' => $e->getMessage()]);
        }
    }

    public function sendLetter($email, $name, $token) {
        $emailService = app(\App\Services\EmailService::class);
        $result = $emailService->sendPasswordReset($email, $name, $token);
        return $result['status'] === 200 ? true : ($result['message'] ?? 'Email send failed');
    }

    // Removed custom password reset methods (using Laravel's Password Broker)

    public function importUsers(Request $request) {
        try {
            if($request->hasFile('file')) {
                $role = \Auth::user()->role;
                $file = $request->file('file');
                $file_extension = $file->getClientOriginalExtension();
                $allowed_extension = ['xlsx'];
                if(in_array($file_extension, $allowed_extension)) {
                    $roles = [1,2,3];
                    if(in_array($role, $roles)) {
                        if($role == 1) {
                            \Excel::import(new ManagerImport, $file);
                        } elseif ($role == 2) {
                            \Excel::import(new ChiefImport, $file);
                        } elseif ($role == 3) {
                            \Excel::import(new TeamleadImport, $file);
                        }

                        return response()->json(['status' => 200, 'message' => 'Users will be uploaded!']);
                    } else {
                        return response()->json(['status' => 500, 'message' => 'You can not import users!']);
                    }
                } else {
                    return response()->json(['status' => 500, 'message' => 'You chosen incorrect file!']);
                }
            } else {
                return response()->json(['status' => 500, 'message' => 'File not chosen!']);
            }
        } catch(\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function exportTable($role){
        try {
            $roles = [1,2,3];
            if(in_array($role, $roles)) {
                if($role == 1) {
                    return \Excel::download(new ManagerTableTemplate, 'template.xlsx');
                } elseif($role == 2) {
                    return \Excel::download(new ChiefTableTemplate, 'template.xlsx');
                } elseif($role == 3) {
                    return \Excel::download(new TeamleadTableTemplate, 'template.xlsx');
                }
            } else {
                $session = \Session::put('import_error', 'You can not download table template!');
                return back()->with($session);
            }
        } catch(\Exception $e) {
            $session = \Session::put('import_error', $e->getMessage());
            return back()->with($session);
        }
    }
}
