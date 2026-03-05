<?php

class AIScraper extends BaseScraper {
    public function chatGpt() {
        $prompt = $this->getQueryParam('prompt');
        $this->jsonResponse([
            'answer' => "Simulated ChatGPT response to: " . $prompt,
            'model' => 'gpt-4o-mini'
        ]);
    }
}
