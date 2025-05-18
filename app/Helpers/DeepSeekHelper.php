<?php

namespace App\Helpers;

use Exception;
class DeepSeekHelper
{
    // properties for configuration
    private $apiEndpoint = 'https://api.deepseek.com/';
    private $apiKey;
    private $model;
    private $timeout; // Default timeout in seconds
    private $temperature;
    private $path;
    private $max_tokens;
    private $stream;

    /**
     * system prompt for the model
     * @var array
     */
    private $system_prompt;



    public function __construct($path)
    {
        $this->apiKey = config('application.deepseek.apikey');
        $this->model = config('application.deepseek.model');
        $this->timeout = config('application.deepseek.timeout');
        $this->max_tokens = config('application.deepseek.max-tokens');

        $this->path = $path;

        // Coding / Math   	0.0
        // Data Cleaning / Data Analysis	1.0
        // General Conversation	1.3
        // Translation	1.3
        // Creative Writing / Poetry	1.5
        $this->temperature = 1.3;

        $this->stream = false;

        $this->setSystemPrompt(config('application.deepseek.system-prompt'));
    }


    /**
     * Set the temperature for the model's response.
     *
     * @param float $temperature
     */
    public function setTemperature($temperature)
    {
        if ($temperature >= 0.0 && $temperature <= 2.0) {
            $this->temperature = $temperature;
        } else {
            throw new Exception('Temperature must be between 0.0 and 2.0.');
        }
    }

    /**
     * set the system prompt
     * 
     * @param string prompt
     */
    public function setSystemPrompt($prompt)
    {
        $this->system_prompt = [
            "content" => $prompt,
            "role" => "system"
        ];
    }

    /**
     * Generate a response from the DeepSeek API with a customizable temperature.
     *
     * @param array $messages the conversation so far
     * @return array|false
     */
    public function generateResponse($messages)
    {
        $message_payload = array_merge([$this->system_prompt], $messages);
        // Prepare the payload
        $payload = [
            'temperature' => $this->temperature,
            'model' => $this->model,
            'max_tokens' => $this->max_tokens,
            'stream' => $this->stream,
            'messages' => $message_payload
        ];


        // return json_encode($payload);

        // Make the request to the API
        return $this->sendRequest('POST', $payload);
    }

    /**
     * Make a request to the DeepSeek API.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $path API endpoint path
     * @param array $data Request data
     * @return array|false
     */
    public function sendRequest($method, $data = [])
    {
        // Ensure the API key is set
        if (empty($this->apiKey)) {
            throw new Exception('API key is not set.');
        }

        // Build the full URL
        $url = $this->apiEndpoint . $this->path;

        // Initialize cURL
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);

        // Handle different HTTP methods
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            default:
                // For GET requests, append data as query parameters
                if (!empty($data)) {
                    $url .= '?' . http_build_query($data);
                    curl_setopt($ch, CURLOPT_URL, $url);
                }
                break;
        }

        // Execute the request
        $response = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }

        // Get the HTTP status code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Close cURL
        curl_close($ch);

        // Decode the JSON response
        $result = json_decode($response, true);

        // Check for API errors
        if ($httpCode >= 400) {
            throw new Exception('API error: ' . ($result['message'] ?? 'Unknown error'));
        }

        return $result;
    }
}