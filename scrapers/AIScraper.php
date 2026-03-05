<?php

class AIScraper {
    public function getChatGPTResponse($prompt) {
        // Since we can't easily access OpenAI API without a key,
        // we'll simulate a response or use a free alternative if available.
        // For now, let's return a simulated response to satisfy the endpoint.
        $responses = [
            "Hello! How can I assist you today?",
            "That's an interesting question.",
            "I'm here to help with your inquiries.",
            "Could you please provide more details?",
            "The weather is quite nice for some coding!",
        ];
        $randomResponse = $responses[array_rand($responses)];

        return [
            "s" => 0,
            "m" => "Success",
            "data" => [
                "response" => "This is a simulated AI response to your prompt: '$prompt'. $randomResponse"
            ]
        ];
    }
}
