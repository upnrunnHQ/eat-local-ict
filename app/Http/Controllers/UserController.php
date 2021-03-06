<?php

namespace App\Http\Controllers;

use App\Http\Requests\SPALoginRequest;
use App\Http\Requests\UserAccountDeletionRequest;
use App\Http\Requests\UserPasswordSelfServiceChangeRequest;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
    //

    /**
     * @param SPALoginRequest $request
     * @param Encrypter $enc
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
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

    /**
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
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


    /**
     * Allows user to change their own password
     * @param UserPasswordSelfServiceChangeRequest $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function changePassword(UserPasswordSelfServiceChangeRequest $request)
    {
        $user = User::find(Auth::id());

        if(Hash::check($request->old_password,$user->password))
        {
            //change password
            $user->password = Hash::make($request->password);
            $user->save();
            return response(null,204);
        }
        return response()->json(['message'=>'The given data was invalid', 'errors'=>['old_password'=>['Existing password is incorrect.']]],422);
    }

    /**
     * Allows the user to securely delete their account
     *
     * @param UserAccountDeletionRequest $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteAccount(UserAccountDeletionRequest $request)
    {
        $user = User::find(Auth::id());

        if(Hash::check($request->delete_password,$user->password))
        {

            //first, since we use soft deletes, purge the user data.
            //we'll do that by first deleting any references to their email in the password_resets table
            DB::table('password_resets')
                ->where('email',$user->email)
                ->delete();
            //next, we'll delete their PII on the user model
            $user->name = md5(time());//just to prevent non-null errors
            $user->email = md5(time());//just to prevent non-null errors
            $user->remember_token =null;
            $user->password ='DELETED PASSWORD';
            $user->save();
            //
            //next, let's log the user out
            Auth::logout();
            //
            //and finally, let's delete the user's model
            $user->delete();
            return response(null,204);
        }
        return response()->json(['message'=>'The given data was invalid', 'errors'=>['delete_password'=>['Password is incorrect.']]],422);
    }

}
