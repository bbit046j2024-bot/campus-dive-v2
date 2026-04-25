<?php
/**
 * File Upload Service
 */
class FileService {

    public static function uploadDocument(array $file, int $userId): array {
        self::validateFile($file, ALLOWED_DOC_TYPES, UPLOAD_MAX_SIZE);

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = UPLOAD_DIR . $filename;

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            Response::error('Failed to upload file.', 500);
        }

        return [
            'filename'      => $filename,
            'original_name' => $file['name'],
            'file_type'     => $file['type'],
            'file_size'     => $file['size'],
        ];
    }

    public static function uploadAvatar(array $file, int $userId): string {
        self::validateFile($file, ALLOWED_IMAGE_TYPES, AVATAR_MAX_SIZE);

        $avatarDir = UPLOAD_DIR . 'avatars/';
        if (!is_dir($avatarDir)) {
            mkdir($avatarDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
        $destination = $avatarDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            Response::error('Failed to upload avatar.', 500);
        }

        return 'uploads/avatars/' . $filename;
    }

    public static function deleteFile(string $filename): bool {
        $path = UPLOAD_DIR . $filename;
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }

    private static function validateFile(array $file, array $allowedTypes, int $maxSize): void {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds server size limit.',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file.',
            ];
            Response::error($errors[$file['error']] ?? 'Unknown upload error.', 400);
        }

        if (!in_array($file['type'], $allowedTypes)) {
            Response::error('File type not allowed. Accepted: ' . implode(', ', $allowedTypes), 400);
        }

        if ($file['size'] > $maxSize) {
            $maxMB = round($maxSize / 1024 / 1024, 1);
            Response::error("File is too large. Maximum size: {$maxMB}MB.", 400);
        }
    }
}
