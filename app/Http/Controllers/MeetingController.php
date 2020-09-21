<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Meeting;
use Carbon\Carbon;

class MeetingController extends Controller
{   

    public function __construct()
    {
        $this->middleware('auth:api', ['only' => ['store', 'update', 'destroy']]);
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $meetings = Meeting::all();

        foreach ($meetings as $meeting) {
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting/' . $meeting->id,
                'method' => 'GET'
            ];
        }

        $response = [
            'msg' => 'List All meetings',
            'meetings' => [
                $meetings
            ]
        ];

        return response()->json($response, 200);
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
            'title' => 'required',
            'description' => 'required',
            'time' => 'required|date_format:YmdHie',
            //'user_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        if(!$user = auth()->user()) {
            return response()->json(['msg' => 'User not found'], 404);
        }

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $user->id;

        $meeting = new Meeting([
            'title' => $title,
            'description' => $description,
            'time' => Carbon::createFromFormat('YmdHie', $time)
        ]);

        if($meeting->save()) {
            $meeting->users()->attach($user_id);
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting/' . $meeting->id,
                'method' => 'GET'
            ];

            $response = [
                'msg' => 'Meeting created',
                'meeting' => $meeting
            ];

            return response()->json($response, 201);
        }

        $response = [
            'msg' => 'An error occured'
        ];

        return response()->json($response, 404);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $meeting = Meeting::with('users')->where('id', $id)->firstOrFail();
        $meeting->view_meeting = [
            'href' => 'api/v1/meeting',
            'method' => 'GET'
        ];

        $response = [
            'msg' => 'Meeting information',
            'meetings' => $meeting
        ];

        return response()->json($response, 200);    
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = \Validator::make($request->json()->all(), [
            'title' => 'required',
            'description' => 'required',
            'time' => 'required|date_format:YmdHie',
            //'user_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        if(!$user = auth()->user()) {
            return response()->json(['msg' => 'User not found'], 404);
        }

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $user->id;

        $meeting = [
            'title' => $title,
            'description' => $description,
            'time' => $time,
            'user_id' => $user_id,
            'view_meeting' => [
                'href' => 'api/v1/meeting/' . $id,
                'method' => 'GET'
            ]
        ];

        $meeting = Meeting::with('users')->findOrFail($id);

        if(!$meeting->users()->where('users.id', $user_id)->first()) {
            return response()->json(['msg' => 'user not registered for meeting, update not successful'], 401);
        }

        $meeting->time = Carbon::createFromFormat('YmdHie', $time);
        $meeting->title = $title;
        $meeting->description = $description;

        if(!$meeting->update()) {
            return response()->json();
        }

        $response = [
            'msg' => 'Meeting updated',
            'meeting' => $meeting
        ];

        return response()->json($response, 200);
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
            return response()->json(['msg' => 'user not registered for meeting, delete not successful'], 401);
        }

        $users = $meeting->users;
        $meeting->users()->detach();
        if(!$meeting->delete()) {
            foreach ($users as $user) {
                $meeting->users()->attach($user);
            }

            return response()->json(['msg' => 'deletion failed'], 404);
        }

        $response = [
            'msg' => 'Meeting deleted',
            'create' => [
                'href' => 'api/v1/meeting',
                'method' => 'POST',
                'params' => 'title, description, time'
            ]
        ];

        return response()->json($response, 200);
    }
}
