<?php
declare(strict_types=1);

/**
 * Validate and store an uploaded room photo.
 * Returns the stored file name, or null when nothing was uploaded.
 * Throws RuleException with a readable message when the file is not acceptable.
 */
function store_room_photo(array $file): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuleException(match ($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The photo is too large. Please choose a smaller file (up to 3 MB).',
            default => 'The photo could not be uploaded. Please try again.',
        });
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuleException('The photo could not be uploaded. Please try again.');
    }
    if ($file['size'] > (int)cfg('upload_max_bytes', 3 * 1024 * 1024)) {
        throw new RuleException('The photo is too large. Please choose a file up to 3 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext) {
        throw new RuleException('Please upload a JPG, PNG, or WebP photo.');
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info || $info[0] < 100 || $info[1] < 100 || $info[0] > 8000 || $info[1] > 8000) {
        throw new RuleException('That file does not look like a valid photo (between 100 and 8000 pixels wide).');
    }
    $dir = APP_ROOT . '/uploads/rooms';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        throw new RuleException('The uploads folder is missing and could not be created.');
    }
    $name = 'room_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuleException('The photo could not be saved. Check that the uploads/rooms folder is writable.');
    }
    return $name;
}

function delete_room_photo(?string $name): void
{
    if ($name && preg_match('/^room_[a-f0-9]{16}\.(jpg|png|webp)$/', $name)) {
        $p = APP_ROOT . '/uploads/rooms/' . $name;
        if (is_file($p)) {
            @unlink($p);
        }
    }
}
