<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Mail\AccountMail;
use App\Mail\NewUser;
use App\Models\Group;
use App\Models\Invitations;
use App\Models\Members;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function handle_register (RegisterRequest $request)
    {
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);

        Mail::to($request->email)->send(new AccountMail($request->name));

        if($request->hasFile('avatar')){
            $file = $request->file('avatar');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $user->avatar = $filename;
        }

        $user->save();

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
        
    }
    public function handle_login(Request $request){              
                $user = User::where('email', $request->email)->first();
                if (!$user || !Hash::check($request->password, $user->password)) {
                    
                    return response()->json([
                        'message' => 'Login failed',
                    ], 401);
                }else{
                    
                    $invitation = Invitations::where('email', $request->email)->first();
                    if($invitation){
                        // ajouter au groupe
                        $group = Group::find($invitation->group_id);
                        $memberSearch = User::where('email', $request->email)->first();
                        $member = new Members();
                        $member->group_id = $group->id;
                        $member->member_id = $memberSearch->id;
                        $member->save();

                        //Supprimer l'invitation
                        Invitations::where('email', $request->email)->delete();
                
                $Gmembers = DB::table('members')
                ->join('users', 'members.member_id', '=', 'users.id')
                ->where('members.group_id', $group->id)
                ->where('users.email', '!=', $request->email)
                ->select('users.name', 'users.email', ) // Sélectionner uniquement les colonnes nécessaires
                ->get();
                foreach ($Gmembers as $Gmember) {
                    
                    Mail::to($Gmember->email)->send(new NewUser($request->email, $group->name));
                }
                return response()->json($Gmembers, 200);
            }
            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
            ]);
    }
}
    public function delete_user($id){
        $user = User::find($id);
        if($user){
            $user->delete();
            return response()->json([
               'message' => 'User deleted successfully',
                'user' => $user,
            ], 200);
        }
        return response()->json([
           'message' => 'User not found',
        ], 404);
    }

    public function List_user(){
        $users = User::all();
    return response()->json($users->map(function ($user) {
        // S'assurer que l'avatar existe
        if ($user->avatar) {
            $user->avatar = asset('uploads/' . $user->avatar);
        }
        return $user;
    }));
    }
  
}
