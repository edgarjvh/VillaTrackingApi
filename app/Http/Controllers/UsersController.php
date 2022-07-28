<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function getUsers(): JsonResponse
    {
        $USER = new User();

        $users = $USER->all();

        return response()->json(['result' => 'OK', 'users' => $users]);
    }

    public function getUser(Request $request): JsonResponse
    {
        $USER = new User();
        $id = $request->id ?? 0;

        $user = $USER->where('id', $id)->first();

        return response()->json(['result' => 'OK', 'user' => $user]);
    }

    public function registerUser(Request $request): JsonResponse
    {
        $USER = new User();
        $name = $request->name ?? '';
        $email = strtolower($request->email ?? '');
        $password = $request->password ?? '';

        $exist = $USER->where('email', $email)->first();

        if ($exist) {
            return response()->json(['result' => 'DUPLICATED']);
        } else {
            $validation_code = $this->getRandomCode();

            $user = $USER->updateOrCreate([
                'id' => 0
            ], [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'validation_code' => Hash::make($validation_code)
            ]);

            return response()->json(['result' => 'OK', 'user' => $user, 'validation_code' => $validation_code]);
        }
    }

    public function validateUser(Request $request): JsonResponse
    {
        $USER = new User();
        $email = strtolower($request->email ?? '');
        $validation_code = $request->validation_code ?? '';

        $user = $USER->where('email', $email)->first();

        if (!$user) {
            return response()->json(['result' => 'NO USER']);
        } elseif (!Hash::check($validation_code, $user->validation_code)) {
            return response()->json(['result' => 'WRONG CODE']);
        } else {
            $USER->updateOrCreate([
                'id' => $user->id
            ], [
                'validation_code' => null,
                'status' => 1
            ]);

            return response()->json(['result' => 'OK']);
        }
    }

    public function validateLogin(Request $request): JsonResponse
    {
        $USER = new User();
        $email = strtolower($request->email ?? '');
        $password = $request->password ?? '';

        $user = $USER->where('email', $email)->first();

        if (!$user) {
            return response()->json(['result' => 'NO USER']);
        }elseif (!Hash::check($password, $user->password)){
            return response()->json(['result' => 'NO USER']);
        }elseif ($user->validation_code){
            return response()->json(['result' => 'NOT VALIDATED']);
        }elseif ($user->status === 0){
            return response()->json(['result' => 'INACTIVE']);
        }else{
            return response()->json(['result' => 'OK', 'user' => $user]);
        }
    }

//    RECOVERY PASSOWRD


    function getRandomCode($length = 6)
    {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
