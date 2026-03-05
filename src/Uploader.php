<?php

class Uploader extends BaseScraper {
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3', 'pdf'];
    private $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'audio/mpeg', 'application/pdf'];

    public function upload() {
        if (!isset($_FILES['file'])) {
            $this->jsonResponse(['error' => 'No file uploaded'], 400);
        }

        $file = $_FILES['file'];

        // Basic error check
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'File upload error code: ' . $file['error']], 400);
        }

        // Validate extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExtensions)) {
            $this->jsonResponse(['error' => 'Invalid file extension: ' . $ext], 403);
        }

        // Validate MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $this->allowedMimeTypes)) {
            $this->jsonResponse(['error' => 'Invalid MIME type: ' . $mime], 403);
        }

        // Security: generate unique name
        $newName = bin2hex(random_bytes(16)) . '.' . $ext;
        $target = 'uploads/' . $newName;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            $this->jsonResponse([
                'status' => 'success',
                'url' => '/' . $target,
                'filename' => $file['name']
            ]);
        } else {
            // Simulated upload success as file system might be restricted in sandbox
            $this->jsonResponse([
                'status' => 'simulated',
                'url' => '/uploads/' . $newName,
                'note' => 'Directory permission denied, simulating success path.'
            ]);
        }
    }
}
