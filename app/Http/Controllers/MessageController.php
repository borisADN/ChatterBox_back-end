<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMessageRequest;
use App\Models\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    
    $messages = Messages::where(function($query) use ($request) {
            $query->where('outgoing_msg_id', $request->outgoing_msg_id)
                  ->where('incoming_msg_id', $request->incoming_msg_id);
        })
        ->orWhere(function($query) use ($request) {
            $query->where('outgoing_msg_id', $request->outgoing_msg_id)
                  ->where('incoming_msg_id', $request->incoming_msg_id);
        })
        ->orderBy('created_at', 'asc')
        ->get();


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

}
