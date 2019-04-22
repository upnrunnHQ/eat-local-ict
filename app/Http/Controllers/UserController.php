<?php

namespace App\Http\Controllers;

use App\Http\Requests\SPALoginRequest;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Encryption\Encrypter;



class UserController extends Controller
{
    //


    function loginViaSpa(SPALoginRequest $request,Encrypter $enc)
    {


        if (Auth::attempt(['email' => $request['email'], 'password' => $request['password']])) {
            // Authentication passed...
            $data['user']=User::where('id',Auth::id())->first();
            $data['token']=csrf_token();
            return response()->json($data);

        }
        else
        {
            $data=['message'=>'Incorrect email or password'];
            return response($data,422);
        }
    }

    function currentUser()
    {
        $id = Auth::id();
        $user = User::where('id',$id)->select('name')->first();
        if($user)
        {
            return($user);

        }
        else
        {
            return response(null,204);
        }
    }
}