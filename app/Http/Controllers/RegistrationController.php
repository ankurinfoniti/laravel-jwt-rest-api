<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Meeting;
use App\User;

class RegistrationController extends Controller
{   
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {   
        $validator = \Validator::make($request->json()->all(), [
            'meeting_id' => 'required',
            'user_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $meeting_id = $request->input('meeting_id');
        $user_id = $request->input('user_id');

        $meeting = Meeting::findOrFail($meeting_id);
        $user = User::findOrFail($user_id);

        $message = [
            'msg' => 'User is already registered for meeting',
            'user' => $user,
            'meeting' => $meeting,
            'unregister' => [
                'href' => 'api/v1/meeting/registration/' . $meeting->id,
                'method' => 'DELETE'
            ]
        ];

        if($meeting->users()->where('users.id', $user->id)->first()) {
            return response()->json($message, 404);
        }

        $user->meetings()->attach($meeting);

        $response = [
            'msg' => 'User registered for meeting',
            'meeting' => $meeting,
            'user' => $user,
            'unregister' => [
                'href' => 'api/v1/meeting/registration/' . $meeting->id,
                'method' => 'DELETE'
            ]
        ];

        return response()->json($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {   
        $meeting = Meeting::findOrFail($id);

        if(!$user = auth()->user()) {
            return response()->json(['msg' => 'User not found'], 404);
        }

        if(!$meeting->users()->where('users.id', $user->id)->first()) {
            return response()->json(['msg' => 'user not registered for meeting, delete operation not successful'], 401);
        }

        $meeting->users()->detach($user->id);

        $response = [
            'msg' => 'User unregistered for meeting',
            'meeting' => $meeting,
            'user' => $user,
            'register' => [
                'href' => 'api/v1/meeting/registration/',
                'method' => 'POST',
                'params' => 'user_id, meeting_id'
            ]
        ];

        return response()->json($response, 200);    
    }
}
