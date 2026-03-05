<?php

class NetShortScraper extends BaseScraper {
    public function getForYou() {
        $this->jsonResponse([
            ['title' => 'Net Drama 1', 'shortPlayId' => '101'],
            ['title' => 'Net Drama 2', 'shortPlayId' => '102']
        ]);
    }
    public function getTheaters() { $this->getForYou(); }
    public function search() { $this->getForYou(); }
    public function getAllEpisodes() { $this->jsonResponse([]); }
}
