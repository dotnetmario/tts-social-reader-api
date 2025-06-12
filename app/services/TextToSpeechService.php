<?php

namespace App\Services;

use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\Client\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\SynthesizeSpeechRequest;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Google\Cloud\TextToSpeech\V1\SsmlVoiceGender;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

/**
 * TextToSpeechService
 *
 * Handles integration with Google Cloud Text-to-Speech API using the
 * authenticated user's preferences for voice and language.
 */
class TextToSpeechService
{
    protected TextToSpeechClient $client;
    protected User $user;

    /**
     * Constructor
     *
     * Initializes the service with the authenticated user and
     * sets up the Google TTS client with credentials.
     */
    public function __construct(User $user)
    {
        // Authenticated user
        $this->user = $user;

        // Initialize Google TTS client with credentials
        $this->client = new TextToSpeechClient([
            'credentials' => storage_path('app/secrets/google-credentials.json'),
        ]);
    }

    /**
     * Returns voice configuration parameters based on the user's preferences,
     * falling back to defaults if not set.
     *
     * @return array
     */
    public function getUserVoiceSelectionParams(): array
    {
        // Default voice parameters
        $params = [
            'language_code' => 'en-US',
            'ssml_gender' => SsmlVoiceGender::FEMALE
        ];

        // Override with user preferences if available
        if (!empty($this->user->configuration)) {
            $params['language_code'] = $this->user->configuration['language_code'] ?? $params['language_code'];
            $params['ssml_gender'] = $this->user->configuration['voice_gender'] ?? $params['ssml_gender'];
        }

        return $params;
    }


    /**
     * Converts a given text string into speech using Google TTS API,
     * saves the audio file to local storage, and returns the file path.
     *
     * @param string $text     The text to synthesize
     * @param string $filename The name of the output audio file
     * @return string          Path to the generated audio file
     *
     * @throws \RuntimeException if TTS API call fails
     */
    public function convertTextToSpeech(string $text, string $filename = 'output.mp3')
    {
        $inputText = new SynthesisInput([
            'text' => $text
        ]);

        $voice_params = $this->getUserVoiceSelectionParams();

        $voice = new VoiceSelectionParams($voice_params);

        $audioConfig = new AudioConfig([
            'audio_encoding' => AudioEncoding::MP3
        ]);

        $request = new SynthesizeSpeechRequest([
            'input' => $inputText,
            'voice' => $voice,
            'audio_config' => $audioConfig,
        ]);

        try {
            $response = $this->client->synthesizeSpeech($request);
        } catch (Exception $e) {
            // Log and throw a user-friendly error
            Log::error('Google TTS API error: ' . $e->getMessage());
            throw new \RuntimeException("TTS generation failed. Please try again later.");
        }

        $response = $this->client->synthesizeSpeech($request);

        $audioContent = $response->getAudioContent();

        // Save the audio file to local storage
        $path = "tts/$filename";
        Storage::disk('local')->put($path, $audioContent);

        return $path;
    }
}