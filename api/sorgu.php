<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET');

class GuncelAdresSorgu {
    private $api_url = "https://api.kahin.org/kahinapi/guncel-adres";
    
    public function sorgula($tc) {
        if (!$this->tcDogrula($tc)) {
            return ["error" => "Geçersiz TC kimlik numarası"];
        }
        
        $response = $this->apiIstek($tc);
        
        if (isset($response['error'])) {
            return $response;
        }
        
        return $this->veriyiTemizle($response);
    }
    
    private function tcDogrula($tc) {
        if (strlen($tc) != 11 || $tc[0] == '0') return false;
        
        $tek = $cift = 0;
        for ($i = 0; $i < 9; $i++) {
            if ($i % 2 == 0) $tek += intval($tc[$i]);
            else $cift += intval($tc[$i]);
        }
        
        $toplam10 = ($tek * 7 - $cift) % 10;
        $toplam11 = ($tek + $cift + $toplam10) % 10;
        
        return $toplam10 == intval($tc[9]) && $toplam11 == intval($tc[10]);
    }
    
    private function apiIstek($tc) {
        $url = $this->api_url . "?tc=" . $tc;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code != 200) {
            return ["error" => "API hatası: HTTP " . $http_code];
        }
        
        $data = json_decode($response, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $data : ["error" => "Geçersiz JSON yanıtı"];
    }
    
    private function veriyiTemizle($data) {
        $temizVeri = [];
        $alanlar = ['adres', 'il', 'ilce', 'mahalle', 'cadde', 'sokak', 'binaNo', 'daireNo', 'postaKodu'];
        
        foreach ($alanlar as $alan) {
            if (isset($data[$alan]) && !empty($data[$alan])) {
                $temizVeri[$alan] = $this->reklamlariTemizle($data[$alan]);
            }
        }
        
        if (empty($temizVeri['adres']) && isset($temizVeri['il'])) {
            $temizVeri['adres'] = $this->adresBirlestir($temizVeri);
        }
        
        return $temizVeri;
    }
    
    private function reklamlariTemizle($metin) {
        $reklamlar = [
            '/kahin/iu', '/sahibinden/iu', '/n[\.\s]*a[\.\s]*b[\.\s]*i[\.\s]*s[\.\s]*y[\.\s]*s[\.\s]*t[\.\s]*e[\.\s]*m/iu',
            '/telegram/iu', '/https?:\/\/[^\s]+/iu', '/@[^\s]+/iu'
        ];
        return preg_replace($reklamlar, '', $metin);
    }
    
    private function adresBirlestir($veri) {
        $adres = "";
        if (isset($veri['mahalle'])) $adres .= $veri['mahalle'] . ' ';
        if (isset($veri['cadde'])) $adres .= $veri['cadde'] . ' ';
        if (isset($veri['sokak'])) $adres .= $veri['sokak'] . ' ';
        if (isset($veri['binaNo'])) $adres .= 'No:' . $veri['binaNo'] . ' ';
        if (isset($veri['daireNo'])) $adres .= 'D:' . $veri['daireNo'] . ' ';
        if (isset($veri['ilce'])) $adres .= $veri['ilce'] . '/';
        if (isset($veri['il'])) $adres .= $veri['il'];
        return trim($adres);
    }
}

// Main execution
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['tc'])) {
    $adresSorgu = new GuncelAdresSorgu();
    $sonuc = $adresSorgu->sorgula($_GET['tc']);
    echo json_encode($sonuc, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(400);
    echo json_encode(["error" => "TC kimlik parametresi gerekli. Kullanım: ?tc=11111111111"]);
}
?>
