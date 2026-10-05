

<?php
$files = [
    'bb_6ac1042e982b96.68376410.pdf',
    'bb_6ac1048d539ed7.30917065.pdf',
    'bb_6ac0fc308a3841.01363899.pdf',
    'bb_6ac0fc7c8372c5.84815535.pdf',
];

foreach ($files as $f) {
    $url = "https://rmwxhaxusmpuhwryseab.supabase.co/storage/v1/object/public/pdfs/" . $f;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "$f -> Supabase CDN HTTP: $code" . PHP_EOL;
}
