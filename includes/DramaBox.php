<?php
// php_project/includes/DramaBox.php

class DramaBox {
    private static $cachedToken = null;

    public static function getDramaBoxToken($force = false) {
        if (!defined('DRAMABOX_TOKEN_URL')) return null;

        if (!$force && self::$cachedToken && self::$cachedToken['exp'] > time()) {
            return self::$cachedToken;
        }

        $ch = curl_init(DRAMABOX_TOKEN_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (!$data || !isset($data['token']) || !isset($data['deviceid'])) {
            return null;
        }

        self::$cachedToken = [
            'token' => $data['token'],
            'deviceid' => $data['deviceid'],
            'exp' => time() + 3600 // 1 hour
        ];

        return self::$cachedToken;
    }

    private static function getTimeZoneOffset() {
        $offsetSeconds = date('Z');
        $hours = floor(abs($offsetSeconds) / 3600);
        $minutes = floor((abs($offsetSeconds) % 3600) / 60);
        $sign = ($offsetSeconds >= 0) ? '+' : '-';
        return sprintf("%s%02d%02d", $sign, $hours, $minutes);
    }

    public static function buildHeaders($tk) {
        return [
            "User-Agent: okhttp/4.10.0",
            "Accept-Encoding: gzip",
            "Content-Type: application/json; charset=UTF-8",
            "tn: Bearer " . $tk['token'],
            "version: " . (defined('DRAMABOX_VERSION_CODE') ? DRAMABOX_VERSION_CODE : "430"),
            "vn: " . (defined('DRAMABOX_VERSION_NAME') ? DRAMABOX_VERSION_NAME : "4.3.0"),
            "cid: " . (defined('DRAMABOX_CID') ? DRAMABOX_CID : "DRA1000042"),
            "package-name: " . (defined('DRAMABOX_PACKAGE_NAME') ? DRAMABOX_PACKAGE_NAME : "com.storymatrix.drama"),
            "apn: " . (defined('DRAMABOX_APN') ? DRAMABOX_APN : "1"),
            "device-id: " . $tk['deviceid'],
            "language: " . (defined('DRAMABOX_LANGUAGE') ? DRAMABOX_LANGUAGE : "in"),
            "current-language: " . (defined('DRAMABOX_LANGUAGE') ? DRAMABOX_LANGUAGE : "in"),
            "p: " . (defined('DRAMABOX_PLATFORM_P') ? DRAMABOX_PLATFORM_P : "43"),
            "time-zone: " . self::getTimeZoneOffset()
        ];
    }

    private static function postUpstream($url, $body, $headers) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_ENCODING, ""); // Handle gzip

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status' => $status, 'data' => json_decode($response, true)];
    }

    public static function withTokenRetry($callback) {
        $tk = self::getDramaBoxToken();
        if (!$tk) return ['status' => 500, 'data' => null];

        $res = $callback($tk);
        if ($res['status'] === 401 || $res['status'] === 403) {
            $tk = self::getDramaBoxToken(true);
            if (!$tk) return ['status' => 500, 'data' => null];
            $res = $callback($tk);
        }
        return $res;
    }

    public static function fetchLatest($pageNo = 1) {
        $url = "https://sapi.dramaboxdb.com/drama-box/he001/theater";
        return self::withTokenRetry(function($tk) use ($pageNo, $url) {
            $headers = self::buildHeaders($tk);
            $data = [
                'newChannelStyle' => 1,
                'isNeedRank' => 1,
                'pageNo' => (int)$pageNo,
                'index' => 1,
                'channelId' => (int)(defined('DRAMABOX_PLATFORM_P') ? DRAMABOX_PLATFORM_P : 43),
            ];
            return self::postUpstream($url, $data, $headers);
        });
    }

    public static function fetchStream($bookId, $index = 1) {
        $url = "https://sapi.dramaboxdb.com/drama-box/chapterv2/batch/load";
        return self::withTokenRetry(function($tk) use ($bookId, $index, $url) {
            $headers = self::buildHeaders($tk);
            $data = [
                'boundaryIndex' => 0,
                'comingPlaySectionId' => -1,
                'index' => (int)$index,
                'currencyPlaySource' => "discover_new_rec_new",
                'needEndRecommend' => 0,
                'currencyPlaySourceName' => "",
                'preLoad' => false,
                'rid' => "",
                'pullCid' => "",
                'loadDirection' => 0,
                'startUpKey' => "",
                'bookId' => $bookId,
            ];
            return self::postUpstream($url, $data, $headers);
        });
    }

    public static function fetchSuggest($keyword) {
        $url = "https://sapi.dramaboxdb.com/drama-box/search/suggest";
        return self::withTokenRetry(function($tk) use ($keyword, $url) {
            $headers = self::buildHeaders($tk);
            $data = ['keyword' => $keyword];
            return self::postUpstream($url, $data, $headers);
        });
    }

    public static function mapToItem($r) {
        $tags = $r['tags'] ?? $r['tagNames'] ?? $r['tagList'] ?? [];
        if (empty($tags) && isset($r['tagV3s']) && is_array($r['tagV3s'])) {
            foreach ($r['tagV3s'] as $t) {
                if (isset($t['tagName'])) $tags[] = $t['tagName'];
            }
        }

        $cover = $r['coverWap'] ?? $r['cover'] ?? $r['coverUrl'] ?? $r['image'] ?? $r['pic'] ?? "";
        $chapterCount = $r['chapterCount'] ?? $r['episodeCount'] ?? null;

        $play = $r['playCount'] ?? "";
        if (empty($play) && isset($r['inLibraryCount'])) {
            $play = self::compactNumber($r['inLibraryCount']);
        }

        $corner = null;
        if (isset($r['corner']) && (isset($r['corner']['name']) || isset($r['corner']['cornerType']))) {
            $corner = [
                'name' => $r['corner']['name'] ?? null,
                'color' => $r['corner']['color'] ?? self::defaultCornerColor($r['corner']['name'] ?? null)
            ];
        }

        return [
            'bookId' => (string)($r['bookId'] ?? $r['id'] ?? $r['contentId'] ?? ""),
            'bookName' => $r['bookName'] ?? $r['name'] ?? $r['title'] ?? $r['keyword'] ?? "",
            'coverWap' => $cover,
            'tags' => $tags,
            'playCount' => $play,
            'chapterCount' => $chapterCount,
            'corner' => $corner
        ];
    }

    public static function mapVideoSources($cdnList = []) {
        $out = [];
        foreach (($cdnList ?: []) as $cdn) {
            $cdnDomain = (string)($cdn['cdnDomain'] ?? "");
            $isDefaultCdn = ($cdn['isDefault'] ?? 0) === 1;
            foreach (($cdn['videoPathList'] ?? []) as $v) {
                $q = (int)($v['quality'] ?? 0);
                $url = (string)($v['videoPath'] ?? "");
                if (!$url || !$q) continue;
                $out[] = [
                    'url' => $url,
                    'quality' => $q,
                    'cdn' => $cdnDomain,
                    'isDefaultCdn' => $isDefaultCdn,
                    'isDefaultQuality' => ($v['isDefault'] ?? 0) === 1,
                    'isVip' => ($v['isVipEquity'] ?? 0) === 1,
                ];
            }
        }

        usort($out, function($a, $b) {
            if ($a['isDefaultCdn'] !== $b['isDefaultCdn']) return $a['isDefaultCdn'] ? -1 : 1;
            if ($a['isDefaultQuality'] !== $b['isDefaultQuality']) return $a['isDefaultQuality'] ? -1 : 1;
            return $b['quality'] - $a['quality'];
        });

        $seen = [];
        return array_values(array_filter($out, function($s) use (&$seen) {
            $parts = explode('?', $s['url']);
            $k = $s['cdn'] . ":" . $s['quality'] . ":" . $parts[0];
            if (isset($seen[$k])) return false;
            $seen[$k] = true;
            return true;
        }));
    }

    private static function compactNumber($n) {
        if ($n >= 1000000) return round($n / 1000000, 1) . 'jt';
        if ($n >= 1000) return round($n / 1000, 1) . 'rb';
        return (string)$n;
    }

    private static function defaultCornerColor($name) {
        if (!$name) return "#000000AA";
        $n = strtolower($name);
        if (strpos($n, "terbaru") !== false) return "#4D65ED";
        if (strpos($n, "hot") !== false || strpos($n, "anggota") !== false) return "#E94E77";
        return "#000000AA";
    }

    public static function getUrlExpiryMs($url) {
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $params);
            if (isset($params['Expires'])) {
                return (int)$params['Expires'] * 1000;
            }
        }
        return 0;
    }
}
