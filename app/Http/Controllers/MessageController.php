<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMessageRequest;
use App\Mail\NewFile;
use App\Models\Group;
use App\Models\GroupMessage;
use App\Models\GroupsMessages;
use App\Models\Messages;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class MessageController extends Controller
{
public function sendMessage (SendMessageRequest $request) {
    // Validate incoming request
    $message = new Messages();
    $message->outgoing_msg_id = $request->outgoing_msg_id;
    $message->incoming_msg_id = $request->incoming_msg_id;
    $message->message = $request->message;
    $message->file = $request->file;
    $message->save();
    return response()->json(['message' => 'Message sent successfully.']);
}

public function sendFile (Request $request) {
    $message = new Messages();
    $message->outgoing_msg_id = $request->outgoing_msg_id;
    $message->incoming_msg_id = $request->incoming_msg_id;
    $message->message = $request->message;
    // store file
    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $file->move(public_path('uploads/sendFile'), $filename);
        $message->file = $filename;
    }

  

    $message->save();
    return response()->json(['message' => 'File sent successfully.']);
}

public function getMessages(Request $request) {
 
    $request->validate([
        'user_id' => 'required', 
        'outgoing_msg_id' => 'required', 
    ]);
    return response()->json(['messages' => $request]);

}

// Affiche tous les messages entre les deux utilisateurs
public function displayMessages (Request $request) {
    $messages = DB::table('messages')
    ->where(function ($query) use ($request) {
        $query->where('outgoing_msg_id', $request->outgoing_msg_id)
              ->where('incoming_msg_id', $request->incoming_msg_id);
    })
    ->orWhere(function ($query) use ($request) {
        $query->where('outgoing_msg_id', $request->incoming_msg_id)
              ->where('incoming_msg_id', $request->outgoing_msg_id);
    })
    ->orderBy('created_at', 'asc') // Tri par date pour avoir les plus anciens en premier
    ->get();

    // return response()->json(['messages' => $request]);
    return response()->json(['messages' => $messages]);
}

public function getAllMessages () {
    $messages = Messages::all();
    return response()->json(['messages' => $messages]);
}

public function SendGroupMessage (Request $request) {
    // Validate incoming request
      $request->validate([
          'group_id' =>'required|integer',
          'sender_id' =>'required|integer',
          'message' =>'nullable|string|max:255',
          'file' => 'nullable|file|max:10240', // 10MB maximum
      ]);

    //   return response()->json(['messages' => $request->all()]);
    $message = new GroupMessage ();
    $message->group_id = $request->group_id;
    $message->sender_id = $request->sender_id;
    $message->message = $request->message;
    $message->file = $request->file;
    
    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $file->move(public_path('uploads/sendGroupFile'), $filename);
        $message->file = $filename;
        $sender = User::find($request->sender_id);

    $Crew = Group::find($request->group_id);

        $Gmembers = DB::table('members')
        ->join('users', 'members.member_id', '=', 'users.id')
        ->where('members.group_id', $request->group_id)
        ->where('users.email', '!=', $sender->email)
        ->select('users.name', 'users.email', ) // Sélectionner uniquement les colonnes nécessaires
        ->get();
        foreach ($Gmembers as $Gmember) {
            
            Mail::to($Gmember->email)->send(new NewFile($request->email, $Crew->name));
        }        
    }
    $message->save();

    return response()->json([
        'message' => 'Message envoyé avec succès.',
        'data' => [
            'id' => $message->id,
            'group_id' => $message->group_id,
            'sender_id' => $message->sender_id,
            'sender_name' => $sender->name, // Assurez-vous que le modèle User a un champ 'name'
            'message' => $message->message,
            'file' => $message->file,
            'created_at' => $message->created_at, // Ajout de la date de création si nécessaire
        ]
    ]);

}

public function getGroupMessages(Request $request)
{
    // Valider l'ID du groupe
    $request->validate([
        'group_id' => 'required|integer', // ID du groupe à valider
    ]);

    // Récupérer les messages du groupe
    $groupMessages = GroupMessage::where('group_id', $request->group_id)
                        ->orderBy('created_at', 'asc') // Trier par ordre chronologique
                        ->get();

    // Vérifier si des messages ont été trouvés pour le groupe donné
    if ($groupMessages->isEmpty()) {
        return response()->json([
            'message' => 'No messages found for this group.',
        ], 404);
    }
    // Retourner les messages en réponse JSON
    return response()->json([
        'messages' => $groupMessages,
    ], 200);
}
























public function SendGroupMessag(Request $request)
{
    // Valider les données reçues
    $request->validate([
        'group_id' => 'required|integer', // ID du groupe
        'sender_id' => 'required|integer', // ID de l'utilisateur qui envoie le message
        'message' => 'nullable|string', // Le contenu du message, peut être nul s'il y a un fichier
        'file' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,txt|max:2048' // Validation du fichier
    ]);

    // Créer une nouvelle instance de message de groupe
    $message = new GroupMessage();
    $message->group_id = $request->group_id;
    $message->sender_id = $request->sender_id;
    $message->message = $request->message;

    // Gestion du fichier (s'il y en a un)
    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('uploads/group_files'), $filename);
        $message->file = $filename; // Sauvegarder le nom du fichier dans la base de données
    }

    // Sauvegarder le message dans la base de données
    $message->save();

    // Retourner une réponse JSON pour indiquer le succès de l'opération
    return response()->json([
        'message' => 'Message sent successfully.',
        'data' => $message
    ], 200);
}



// public function searchMessage ($search) {
//     $messages = Messages::where('message', 'LIKE', '%'.$search.'%')->get();
//     return response()->json(['messages' => $messages]);
// }








}
