<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Mail\AccountMail;
use App\Mail\NewUser;
use App\Mail\OtpCode as MailOtpCode;
use App\Models\Group;
use App\Models\Invitations;
use App\Models\Members;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function handle_register(RegisterRequest $request)
    {
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);

        Mail::to($request->email)->send(new AccountMail($request->name));

        if ($request->hasFile('avatar')) {
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

    public function handle_login(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {

            return response()->json([
                'message' => 'Login failed',
            ], 401);
        } else {

            $invitation = Invitations::where('email', $request->email)->first();
            if ($invitation) {
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
                    ->select('users.name', 'users.email',) // Sélectionner uniquement les colonnes nécessaires
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

    public function delete_user($id)
    {
        $user = User::find($id);
        if ($user) {
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

    public function List_user()
    {
        $users = User::all();
        return response()->json($users->map(function ($user) {
            // S'assurer que l'avatar existe
            if ($user->avatar) {
                $user->avatar = asset('uploads/' . $user->avatar);
            }
            return $user;
        }));
    }

    public function CurrentUser($id)
    {
        $user = User::find($id);
        if ($user) {
            return response()->json($user, 200);
        }
        return response()->json([
            'message' => 'User not found',
        ], 404);
    }
    
    public function update_user($id, Request $request)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }
        // Valider les données avant de les sauvegarder
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,  // Vérifie que l'email est unique mais autorise l'email actuel de l'utilisateur
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Valider le type d'image et sa taille
        ]);
        $user->name = $request->name;
        $user->email = $request->email;


        if ($request->hasFile('avatar')) {
            // Supprimer l'ancien avatar si nécessaire
            if ($user->avatar && file_exists(public_path('uploads/' . $user->avatar))) {
                unlink(public_path('uploads/' . $user->avatar));
            }

            // Enregistrer le nouvel avatar
            $file = $request->file('avatar');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $user->avatar = $filename;
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ], 200);
    }

    public function Forgotten_password(Request $request)
    {
        // // Validation de l'email
        // $validator = Validator::make($request->all(), [
        //     'email' => 'required|email',
        // ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'message' => 'Invalid email format',
        //         'errors' => $validator->errors(),
        //     ], 422); // Erreur de validation
        // }

        // Rechercher l'utilisateur avec l'email donné
        $user = User::where('email', $request->email)->first();

        // Si l'utilisateur n'existe pas
        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé!',
            ], 404);
        }

        try {
            // Générer un code OTP sécurisé
            $otpCode = [
                'email' => $request->email,
                'code' => rand(121111, 989898),
                'expires_at' => now()->addMinutes(10), // Expiration dans 10 minutes
            ];

            // Supprimer les anciens OTP pour cet utilisateur
            OtpCode::where('email', $request->email)->delete();

            // Enregistrer le nouveau OTP
            OtpCode::create($otpCode);

            // Envoyer le code OTP par email
            Mail::to($request->email)->send(new MailOtpCode($otpCode['code']));

            // Réponse JSON en cas de succès
            return response()->json([
                'message' => 'Le code OTP a été envoyé à votre adresse e-mail.',
                'otp_code' => $otpCode,
            ], 200);
        } catch (\Exception $e) {
            // Gestion des erreurs d'envoi d'email ou autres exceptions
            return response()->json([
                'message' => 'Erreur lors de l\'envoi du code OTP.',
                // 'error' => $e->getMessage(),
            ], 500); // Erreur interne du serveur
        }
    }

    public function Verify_otp(Request $request)
    {
        $otpCode = OtpCode::where('email', $request->email)->first();
        if (!$otpCode) {
            return response()->json([
                'message' => 'Please enter a valid email',
            ], 400);
        }
        if ($request->code == $otpCode->code) {
            return response()->json([
                'message' => 'OTP code verified successfully',
                // 'otp_code' => $otpCode,
            ], 200);
        }
        // OtpCode::where('email', $request->email)->delete();
        return response()->json([
            'message' => 'OTP code invalide',
        ], 400);
    }

    public function Reset_password(Request $request)
    {
        $code = OtpCode::where('email', $request->email)->first();

        if (!$code) {
            return response()->json([
                'message' => 'Please enter a valid email',
            ], 400);
        }

        if ($request->code != $code->code) {
            return response()->json([
                'message' => 'Code invalide',
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();
        // Supprimer le code OTP
        OtpCode::where('email', $request->email)->delete();
        return response()->json([
            'message' => 'Password reset successfully',
            'user' => $user,
        ], 200);
        // $validatedData = $request->validate([
        //     'password' => 'required|min:8|confirmed',
        // ]);
        // $user = User::where('email', $request->email)->first();
        // if (!$user) {
        //     return response()->json([
        //         'message' => 'User not found',
        //     ], 404);
        // }
        // $user->password = Hash::make($request->password);
        // $user->save();
        // return response()->json([
        //     'message' => 'Password reset successfully',
        //     'user' => $user,
        // ], 200);
    }
}
