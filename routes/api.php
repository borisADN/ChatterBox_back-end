<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/trial', function (Request $request) {
    $groupId = $request->group_id;

    $members = DB::table('members')
    ->join('users', 'members.member_id', '=', 'users.id')
    ->where('members.group_id', $groupId)
    ->select('users.name', 'users.email', ) // Sélectionner uniquement les colonnes nécessaires
    ->get();

    return response()->json($members, 200);
    return response()->json(['message' => 'Hello World!'], 200);
});

Route::post('/register',[AuthController::class, 'handle_register']);
Route::post('/login', [AuthController::class, 'handle_login']);
route::get('/delete/{id}', [AuthController::class, 'delete_user']);
route::get('/all_users', [AuthController::class, 'List_user']);

route::post('/sendMessage', [MessageController::class, 'sendMessage']);
route::post('/sendFile', [MessageController::class, 'sendFile']);
route::post('/getMessage', [MessageController::class, 'displayMessages']);
route::get('/getAllMessages', [MessageController::class, 'getAllMessages']);

Route::post('/CreateGroup', [GroupController::class, 'CreateGroup']);
Route::post('/AddMember', [GroupController::class, 'AddMember']);
Route::post('/SelectGroups', [GroupController::class, 'SelectGroupOfaMember']);
route::post('/ListGroups', [GroupController::class, 'memberListForAGroup']);
route::post('/InviteMember', [GroupController::class, 'inviteMember']);
route::post('/countMembers', [GroupController::class, 'CountMembersOfAGroup']);

Route::post('/SendMessageGroup', [MessageController::class, 'SendGroupMessage']);
Route::post('/getGroupMessages', [MessageController::class, 'getGroupMessages']);

// Route::post('/RemoveMember', [GroupController::class, 'RemoveMember']);







