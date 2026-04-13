<?php
function silah_cloudinary_config() {
    $cloud = getenv('CLOUDINARY_CLOUD_NAME');
    $key = getenv('CLOUDINARY_API_KEY');
    $preset = getenv('CLOUDINARY_UPLOAD_PRESET');
    return [
        'cloud_name' => $cloud ? (string)$cloud : '',
        'api_key' => $key ? (string)$key : '',
        'upload_preset' => $preset ? (string)$preset : '',
    ];
}

function silah_cloudinary_public_config() {
    $cfg = silah_cloudinary_config();
    return [
        'cloud_name' => $cfg['cloud_name'],
        'api_key' => $cfg['api_key'],
        'upload_preset' => $cfg['upload_preset'],
        'enabled' => $cfg['cloud_name'] !== '' && $cfg['upload_preset'] !== '',
    ];
}
?>

