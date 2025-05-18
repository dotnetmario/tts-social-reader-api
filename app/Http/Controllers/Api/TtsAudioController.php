<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class TtsAudioController extends BaseController
{
    public function generateAudio(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        // calculate the users credits

        // call the Google API to generate audio

        // save the audio generate and assign it to the user
        
        // return the audio generated link

        // return response()->json(['hehe'=> "From the server", 'user' => $user, 'request' => $request->all()]);
    }
}
