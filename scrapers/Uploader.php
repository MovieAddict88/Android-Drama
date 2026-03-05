<?php

class Uploader {
    public function handleUpload($file) {
        if (!isset($file['name']) || empty($file['name'])) {
            return ["s" => 1, "m" => "No file uploaded"];
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'm3u8', 'ts'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions)) {
            return ["s" => 1, "m" => "File type not allowed"];
        }

        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate a safe name
        $fileName = bin2hex(random_bytes(16)) . '.' . $fileExtension;
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
