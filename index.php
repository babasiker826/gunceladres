<?php
header('Content-Type: application/json');
echo json_encode([
    "api" => "Güncel Adres Sorgu API",
    "version" => "1.0",
    "endpoint" => "/api/sorgu.php?tc=TCKIMLIKNO",
    "note" => "TC kimlik numarası ile güncel adres sorgulama"
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
