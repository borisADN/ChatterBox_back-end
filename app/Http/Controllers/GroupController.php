<?php

namespace App\Http\Controllers;

use App\Mail\InvitationMail;
use App\Models\Group;
use App\Models\Members;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    public function CreateGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            // 'description' => 'string|max:255',
        ]);
        // return response()->json(['messages' => $request->all()]);
        

        $group = new Group;
        $group->name = $request->name;
        $group->admin_id = $request->admin_id;
       
        //move group avatar to public folder
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $avatarName = time(). '.'. $avatar->getClientOriginalExtension();
            $avatar->move(public_path('uploads'), $avatarName);
            $group->avatar = $avatarName;
        }


        // Créer le groupe
        $group->save();

        return response()->json([
            'group' => $group,
           'message' => 'Group created successfully',
        ], 201);

        $group->save();

        return response()->json([
            'group' => $group,
           'message' => 'Group created successfully',
        ], 201);
    }

    public function AddMember(Request $request) {
        // return response()->json(['member_id' => $member->id], 200);
        //retourner  l'id en json 
        // Mail::to($request->email)->send(new InvitationMail());
        // return;
        $request->validate([
            'group_id' =>'required|integer',
            'email' =>'required|string',
        ]);
        
        $memberSearch = User::where('email', $request->email)->first();
        if (!$memberSearch) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        // trouver l'id de l'utilisateur selon son email

  
$member = new Members();
$member->group_id = $request->group_id;
$member->member_id = $memberSearch->id;
// $member->member_id = $request->member_id;
//veriier si cette ligne existe deja dans la base de donnee
        if($member->where('group_id', $request->group_id)->where('member_id', $memberSearch->id)->exists()){
            return response()->json(['message' => 'Line already exists in the database'], 400);
        }
        // si pas deja dans la base de donnee, on l'ajoute
        Mail::to($request->email)->send(new InvitationMail());

$member->save();

return response()->json([
   'message' => 'Member added successfully',
], 200);



        $group_id = $request->group_id;
        $member_id = $request->member_id;

        // Vérifier si le groupe existe et si l'admin est admin du groupe
        $group = Group::find($group_id);
        if (!$group ||!$group->is_admin($request->admin_id)) {
            return response()->json(['message' => 'Invalid group or admin'], 403);
        }
//rechercher l'id de l'utilisateur  selon son email 
        $member = User::where('email', $request->email)->first();
        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        // Vérifier si le membre est déjà dans le groupe
      

        // Vérifier si le membre est déjà dans le groupe
        $member = User::find($member_id);
        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        // Vérifier si le membre est déjà dans le groupe
        if ($group->members()->where('user_id', $member_id)->exists()) {
            return response()->json(['message' => 'Member already in the group'], 400);
        }

        // Ajouter le membre au groupe
        $group->members()->attach($member_id);

        return response()->json([
           'message' => 'Member added successfully',
        ], 200);

        // Vérifier si le groupe existe
        // $group = Group::find($group_id);
        // if (!$group) {
        //     return response()->json(['message' => 'Group not found'], 404);
        // }
    
        // Vérifier si le membre existe
        // $member = User::find($member_id);
        // if (!$member) {
        //     return response()->json(['message' => 'Member not found'], 404);
        // }
    
        // Vérifier si le membre est déjà dans le groupe
        // if ($group->members()->where('user_id', $member_id)->exists()) {
        //     return response()->json(['message' => 'Member already in the group'], 400);
        // }
    
        // Ajouter le membre au groupe
        // $group->members()->attach($member_id);
    
        // return response()->json([
        //    'message' => 'Member added successfully',
        // ], 200);
    }

    public function SelectGroupOfaMember(Request $request){
        //   return response()->json(['messages' => $request->all()]);
        $userId = $request->member_id;

        if (!$userId) {
            return response()->json(['message' => 'Invalid member id'], 400);
        }

        // Récupère tous les groupes dans lesquels le membre est membre
        $groups = Group::join('members', 'groups.id', '=', 'members.group_id')
               ->where('members.member_id', $userId)
               ->get(['groups.*']); // Sélectionne toutes les colonnes de la table 'groups'

                return response()->json([
                    'groups' => $groups,
                ], 200);
    } 
    public function DeleteMember($group_id, $member_id) {
        // Vérifier si le groupe existe
        $group = Group::find($group_id);
        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        // Vérifier si le membre est déjà dans le groupe
        if (!$group->members()->where('user_id', $member_id)->exists()) {
            return response()->json(['message' => 'Member not found in the group'], 404);
        }

        // Supprimer le membre du groupe
        $group->members()->detach($member_id);

        return response()->json([
           'message' => 'Member deleted successfully',
        ], 200);
    }

    public function memberListForAGroup(Request $request)
    {
        //  return response()->json(['messages' => $request->all()]);

        $request->validate([
            'group_id' => 'required|integer',
        ]);
    
        $groupId = $request->group_id;
    
        // Vérifier si le groupe existe
        $group = DB::table('groups')->where('id', $groupId)->first();
        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }
    
        // Récupérer les membres du groupe via une requête SQL simple
        $members = DB::table('members')
            ->join('users', 'members.member_id', '=', 'users.id')
            ->where('members.group_id', $groupId)
            ->select('users.id', 'users.name', 'users.email', 'users.avatar') // Sélectionner uniquement les colonnes nécessaires
            ->get();
    
        return response()->json([
            'group' => $group,   // Informations sur le groupe
            'members' => $members,  // Liste des membres du groupe
        ], 200);
    }
    
    
}
