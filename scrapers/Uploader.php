<?php

class Uploader {
    public function handleUpload($file) {
        if (!isset($file['name']) || empty($file['name'])) {
            return ["s" => 1, "m" => "No file uploaded"];
        }

        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = basename($file['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $fileUrl = $protocol . "://" . $host . "/" . $targetPath;
            return [
                "s" => 0,
                "m" => "File uploaded successfully",
                "data" => [
                    "url" => $fileUrl
                ]
            ];
        } else {
            return ["s" => 1, "m" => "Failed to move uploaded file"];
        }
    }
}
