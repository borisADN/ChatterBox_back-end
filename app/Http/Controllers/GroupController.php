<?php

namespace App\Http\Controllers;

use App\Mail\Invitation2;
use App\Mail\InvitationMail;
use App\Models\Group;
use App\Models\Invitations;
use App\Models\Members;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    public function CreateGroup(Request $request)
    {
        // return response()->json(['messages' => $request->all()]);
        $request->validate([
            'name' => 'required|string|max:255',
            // 'description' => 'string|max:255',
        ]);

        $group = new Group;
        $group->name = $request->name;
        // $group->description = $request->description;
        $group->admin_id = $request->admin_id;

        //move group avatar to public folder
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $avatarName = time() . '.' . $avatar->getClientOriginalExtension();
            $avatar->move(public_path('uploads'), $avatarName);
            $group->avatar = $avatarName;
        }
        // Créer le groupe
        $group->save();

        // Ajouter l'admin au groupe 
        $member = new Members();
        $member->group_id = $group->id;
        $member->member_id = $request->admin_id;
        $member->save();

        // Retourner les informations du groupe et du membre
        return response()->json([
            'group' => $group,
            'member' => $member,
            'message' => 'Group created successfully, and admin added as a member',
        ], 201);
    }

    public function AddMember(Request $request)
    {
        $request->validate([
            'group_id' => 'required|integer',
            'email' => 'required|string',
            'sender_id' => 'required|integer',
        ]);

        $memberSearch = User::where('email', $request->email)->first();
        if (!$memberSearch) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        $member = new Members();
        $member->group_id = $request->group_id;
        $member->member_id = $memberSearch->id;
        //verifier si cette ligne existe deja dans la base de donnée
        if ($member->where('group_id', $request->group_id)->where('member_id', $memberSearch->id)->exists()) {
            return response()->json(['message' => 'Line already exists in the database'], 400);
        }
        $Crew=Group::find($request->group_id);
        $Sender = User::find($request->sender_id);

        Mail::to($request->email)->send(new InvitationMail( $Sender->name, $Crew->name));

        //message pour les autres membres

        $member->save();

        return response()->json([
            'message' => 'Member added successfully',
        ], 200);
    }

    public function SelectGroupOfaMember(Request $request)
    {
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
    public function DeleteMember($group_id, $member_id)
    {
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

    public function InviteMember(Request $request){

        $request->validate([
            'group_id' => 'required|integer',
            'email' => 'required|email',
            'id' => 'required|string'
        ]);

        $groupId = $request->group_id;
        $email = $request->email;
        $adderId = $request->id;

        
        $group = Group::find($groupId);
        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        // Vérifier si l'utilisateur existe
        $user = DB::table('users')->where('email', $email)->first();
        if ($user) {
            return response()->json(['message' => 'Cet utilisateur est déjà inscrit'], 404);
        }else{
            //avoir les infos sur ce groupe 
            $groupInfo = DB::table('groups')->where('id', $groupId)->first();
            $adder = DB::table('users')->where('id', $adderId)->first();

            $adderEmail = $adder->email;

            $groupName = $groupInfo->name;
$invite = new Invitations();
$invite->group_id = $groupId;
$invite->email = $email;

Mail::to($email)->send(new Invitation2(  $adderEmail,$groupName));

$invite->save();           
        }
        return response()->json([
            'message' => 'Invitation envoyée',
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
