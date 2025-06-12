<?php

namespace App\Http\Controllers\Api;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TextToSpeech;
use App\Services\TextToSpeechService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TtsAudioController extends BaseController
{
    // public function generateAudio2(Request $request)
    // {
    //     // ==========================================================
    //     // validation
    //     // ==========================================================
    //     $rules = [
    //         'text' => "required|string"
    //     ];
    //     $this->validateRequest($request, $rules);
    //     // ==========================================================

    //     $user = Auth::guard('sanctum')->user()->with('configuration')->first();
    //     $file_name = $user->getTtsFileName();
    //     // $file_name = Auth::guard('sanctum')->user()->getTtsFileName();
    //     $text = $request->input('text');


    //     // ==========================================================
    //     // calculate the users credits
    //     // ==========================================================

    //     // ==========================================================


    //     // ==========================================================
    //     // call the Google API to generate audio
    //     // ==========================================================
    //     $tts_service = new TextToSpeechService($user);
    //     $path_to_audio_file = $tts_service->convertTextToSpeech($text, $file_name);
    //     // ==========================================================
    //     // ==========================================================


    //     // $path_to_audio_file = "dummy/path/to/$file_name.mp3";

    //     // ==========================================================
    //     // save the audio generate and assign it to the user
    //     // ==========================================================
    //     $user->textToSpeeches()->create([
    //         'text' => $text,
    //         'path_to_file' => $path_to_audio_file
    //     ]);
    //     // ==========================================================
    //     // ==========================================================


    //     // return the audio generated link
    //     return $this->sendSuccessResponse([
    //         'path_to_file' => $path_to_audio_file
    //     ]);
    // }





    /**
     * Handles the full lifecycle of generating a TTS audio file:
     * - Validates the input text.
     * - Deducts character credits and logs the request.
     * - Sends the text to the TTS API to generate audio.
     * - Saves the result or refunds credits if the API call fails.
     */
    public function generateAudio(Request $request)
    {
        // Validate the input request to ensure 'text' is present and within allowed limits
        $this->validateRequest($request, [
            'text' => 'required|string|min:5|max:10000',
        ]);

        $user = Auth::guard('sanctum')->user();
        $text = $request->input('text');
        $charCount = mb_strlen($text); // Calculate the number of characters in the input

        $ttsJob = null;
        $creditUsages = [];

        // Attempt to deduct credits and create a pending TTS job
        try {
            DB::beginTransaction();

            $creditUsages = $user->deductCharacters($charCount); // Deducts characters, returns usage log
            if ($creditUsages === false) {
                throw new \RuntimeException("Insufficient credits.");
            }

            // Create a new text-to-speech job with status 'pending'
            $ttsJob = $user->textToSpeeches()->create([
                'text' => $text,
                'characters_used' => $charCount,
                'credit_usages' => json_encode($creditUsages),
                'status' => 'pending',
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack(); // Revert changes if any exception occurs
            return $this->sendErrorResponse(422, "Error: {$e->getMessage()}");
        }

        // Attempt to generate the audio using the external Google TTS API
        try {
            $fileName = $user->getTtsFileName();
            $ttsService = new TextToSpeechService($user);

            $audioPath = $ttsService->convertTextToSpeech($text, $fileName); // Actual API call
            $voice = $ttsService->getUserVoiceSelectionParams(); // Fetch selected voice settings

            // Update the job status to 'completed' with the generated file info
            $ttsJob->update([
                'status' => 'completed',
                'path_to_file' => $audioPath,
                'voice_name' => $voice['name'] ?? null,
                'language_code' => $voice['language_code'] ?? null,
                'voice_gender' => $voice['ssml_gender'] ?? null,
            ]);

            return $this->sendSuccessResponse([
                'path_to_file' => $audioPath,
            ]);

        } catch (\Throwable $e) {
            // Log failure for diagnostics
            Log::error("TTS generation failed for user {$user->id}: {$e->getMessage()}");

            // Mark the job as failed and record the error
            $ttsJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // Refund the user's credits since the generation failed
            $user->recreditFromUsage($creditUsages);

            return $this->sendErrorResponse(500, "TTS generation failed. Credits were refunded.");
        }
    }
}
