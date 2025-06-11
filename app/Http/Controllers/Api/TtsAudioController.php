<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TextToSpeech;
use App\Services\TextToSpeechService;

class TtsAudioController extends BaseController
{
    public function generateAudio(Request $request)
    {
        // ==========================================================
        // validation
        // ==========================================================
        $rules = [
            'text' => "required|string"
        ];
        $this->validateRequest($request,$rules);
        // ==========================================================

        $user = Auth::guard('sanctum')->user()->with('configuration')->first();
        $file_name = $user->getTtsFileName();
        // $file_name = Auth::guard('sanctum')->user()->getTtsFileName();
        $text = $request->input('text');
        

        // ==========================================================
        // calculate the users credits
        // ==========================================================
        
        // ==========================================================
        

        // ==========================================================
        // call the Google API to generate audio
        // ==========================================================
        // $tts_service = new TextToSpeechService($user);
        // $path_to_audio_file = $tts_service->convertTextToSpeech($text, $file_name);
        // ==========================================================
        // ==========================================================


        $path_to_audio_file = "dummy/path/to/$file_name.mp3";

        // ==========================================================
        // save the audio generate and assign it to the user
        // ==========================================================
        $user->textToSpeeches()->create([
            'text' => $text,
            'path_to_file' => $path_to_audio_file
        ]);
        // ==========================================================
        // ==========================================================




        





        // return the audio generated link
        return $this->sendSuccessResponse([
            'path_to_file' => $path_to_audio_file
        ]);

        // return response()->json(['hehe'=> "From the server", 'user' => $user, 'request' => $request->all()]);
    }
}
