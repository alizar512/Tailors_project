<?php
function silah_cloudinary_config() {
    $cloud = getenv('CLOUDINARY_CLOUD_NAME');
    $key = getenv('CLOUDINARY_API_KEY');
    $preset = getenv('CLOUDINARY_UPLOAD_PRESET');
    $videoPreset = getenv('CLOUDINARY_VIDEO_UPLOAD_PRESET');
    return [
        'cloud_name' => $cloud ? (string)$cloud : '',
        'api_key' => $key ? (string)$key : '',
        'upload_preset' => $preset ? (string)$preset : '',
        'video_upload_preset' => $videoPreset ? (string)$videoPreset : '',
    ];
}

function silah_cloudinary_public_config() {
    $cfg = silah_cloudinary_config();
    $videoPreset = $cfg['video_upload_preset'] !== '' ? $cfg['video_upload_preset'] : $cfg['upload_preset'];
    return [
        'cloud_name' => $cfg['cloud_name'],
        'api_key' => $cfg['api_key'],
        'upload_preset' => $cfg['upload_preset'],
        'video_upload_preset' => $videoPreset,
        'enabled' => $cfg['cloud_name'] !== '' && $cfg['upload_preset'] !== '',
    ];
}
?>
