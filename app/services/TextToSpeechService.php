<?php

namespace App\Services;

use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\Client\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\SynthesizeSpeechRequest;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Google\Cloud\TextToSpeech\V1\SsmlVoiceGender;

use Illuminate\Support\Facades\Storage;
use App\Models\User;


class TextToSpeechService
{
    protected TextToSpeechClient $client;
    protected User $user;

    public function __construct(User $user)
    {
        // user making the request
        $this->user = $user;

        // credential
        $this->client = new TextToSpeechClient([
            'credentials' => storage_path('app/secrets/google-credentials.json'),
        ]);
    }

    protected function getUserVoiceSelectionParams(): array
    {
        $params = [
            'language_code' => 'en-US',
            'ssml_gender' => SsmlVoiceGender::FEMALE
        ];

        if (!empty($this->user->configuration)) {
            $params['language_code'] = $this->user->configuration['language_code'] ?? $params['language_code'];
            $params['ssml_gender'] = $this->user->configuration['voice_gender'] ?? $params['ssml_gender'];
        }

        return $params;
    }

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


        $response = $this->client->synthesizeSpeech($request);

        $audioContent = $response->getAudioContent();

        $path = "tts/$filename";
        Storage::disk('local')->put($path, $audioContent);

        return $path;
    }
}