<?php

class ShortMaxHLSProxy {
    private const AES_IV = 'shortmax00000000';

    public function handle($url) {
        if (!$url) {
            http_response_code(400);
            echo "Missing url parameter";
            return;
        }

        $parsed = parse_url($url);
        $allowedDomains = ['shortmax.tv', 'dramabox.com', 'reelshort.com', 'crazymaplestudios.com', 'dramaboxdb.com'];
        $host = $parsed['host'] ?? '';
        $allowed = false;
        foreach ($allowedDomains as $domain) {
            if ($host === $domain || substr($host, -strlen('.' . $domain)) === '.' . $domain) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            http_response_code(403);
            echo "Domain not allowed";
            return;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'okhttp/4.12.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $buffer = curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        $lowUrl = strtolower($finalUrl);
        $isM3u8 = strpos($lowUrl, '.m3u8') !== false || substr($buffer, 0, 7) === '#EXTM3U';
        $isTs = strpos($lowUrl, '.ts') !== false;

        if ($isM3u8) {
            $this->handleM3U8($buffer, $finalUrl);
        } elseif ($isTs) {
            $this->handleTS($buffer);
        } else {
            header("Content-Type: application/octet-stream");
            echo $buffer;
        }
    }

    private function handleM3U8($content, $baseUrl) {
        $lines = explode("\n", $content);
        $newContent = "";
        $host = $_SERVER['HTTP_HOST'];
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $origin = "$protocol://$host" . dirname($_SERVER['SCRIPT_NAME']);
        if ($origin === "$protocol://$host/") $origin = "$protocol://$host";

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if ($line[0] === '#') {
                // Rewrite URI in tags like #EXT-X-KEY
                $line = preg_replace_callback('/URI="([^"]+)"/', function($m) use ($baseUrl, $origin) {
                    $abs = $this->absoluteUrl($m[1], $baseUrl);
                    return 'URI="' . $origin . '/index.php/shortmax/hls?url=' . urlencode($abs) . '"';
                }, $line);
                $newContent .= $line . "\n";
            } else {
                $abs = $this->absoluteUrl($line, $baseUrl);
                $newContent .= $origin . '/index.php/shortmax/hls?url=' . urlencode($abs) . "\n";
            }
        }

        header("Content-Type: application/vnd.apple.mpegurl");
        echo $newContent;
    }

    private function handleTS($buffer) {
        $decrypted = $this->decryptSegment($buffer);
        header("Content-Type: video/mp2t");
        echo $decrypted;
    }

    private function decryptSegment($buf) {
        if (empty($buf) || $buf[0] === "\x47") return $buf;
        if (strlen($buf) < 1040) return $buf;

        $magic = substr($buf, 0, 8);
        if ($magic !== 'shortmax') return $buf;

        try {
            $keyPos = (int)substr($buf, 16, 4);
            $keyOffset = $keyPos - 24;
            $aesKey = substr($buf, 24 + $keyOffset, 16);

            $tail16 = substr($buf, 1024, 16);
            $payload = substr($buf, 1040);
            $ciphertext = $tail16 . substr($payload, 0, 1024);

            $decryptedPart = openssl_decrypt(
                $ciphertext,
                'aes-128-cbc',
                $aesKey,
                OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                self::AES_IV
            );

            if ($decryptedPart[0] !== "\x47") {
                return substr($buf, 1040);
            }

            return $decryptedPart . substr($payload, 1024);
        } catch (Exception $e) {
            return substr($buf, 1040);
        }
    }

    private function absoluteUrl($url, $base) {
        if (preg_match('/^https?:\/\//', $url)) return $url;
        $parts = parse_url($base);
        $path = $parts['path'] ?? '/';
        $path = dirname($path);
        if ($url[0] === '/') {
            return $parts['scheme'] . '://' . $parts['host'] . $url;
        }
        return $parts['scheme'] . '://' . $parts['host'] . $path . '/' . $url;
    }
}
