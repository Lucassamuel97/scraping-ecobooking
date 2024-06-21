<?php
// Função para geocodificar o nome da cidade em coordenadas geográficas (latitude e longitude)
function geocodificar($cidade) {

    $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($cidade) . "&format=json";
    
    $options = [
        'http' => [
            'user_agent' => 'João teste',
        ],
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    // Decodifica a resposta JSON
    $data = json_decode($response, true);
    
    // Verifica se a resposta contém resultados válidos
    if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
        // Extrai latitude e longitude
        $lat = $data[0]['lat'];
        $lon = $data[0]['lon'];
        return array('lat' => $lat, 'lon' => $lon);
    } else {
        return null; // Se não houver resultados válidos, retorna null
    }
}

function calcularRota($origem, $destino) {
    // Chave da API do Google Maps
    $apiKey = 'sua_chave_api_google';

    // Formata a URL da requisição
    $url = "https://maps.googleapis.com/maps/api/directions/json?origin=$origem[lat],$origem[lon]&destination=$destino[lat],$destino[lon]&key=$apiKey";

    // Faz a requisição à API
    $response = file_get_contents($url);

    // Decodifica a resposta JSON
    $data = json_decode($response, true);

    // Verifica se a resposta contém resultados válidos
    if (!empty($data['routes']) && isset($data['routes'][0]['legs'][0]['distance']['value']) && isset($data['routes'][0]['legs'][0]['duration']['value'])) {
        // Extrai a distância em metros e converte para quilômetros
        $distancia = $data['routes'][0]['legs'][0]['distance']['text'];

        // Extrai a duração em segundos e converte para minutos
        $duracao = $data['routes'][0]['legs'][0]['duration']['text'];

        return array('distancia' => $distancia, 'duracao' => $duracao);
    } else {
        return null; // Se não houver resultados válidos, retorna null
    }
}

// Exemplo de uso
$origemCidade = "PR-Palmital";
$destinoCidade = "PR-Mandirituba";

// Geocodifica os nomes das cidades em coordenadas geográficas
$origemCoordenadas = geocodificar($origemCidade);
$destinoCoordenadas = geocodificar($destinoCidade);

var_dump($origemCoordenadas);
var_dump($destinoCoordenadas);

if ($origemCoordenadas && $destinoCoordenadas) {
    // Calcula a rota entre as coordenadas
    $resultado = calcularRota($origemCoordenadas, $destinoCoordenadas);

    if ($resultado) {
        $distancia = $resultado['distancia'];
        $duracao = $resultado['duracao'];
        echo "A distância entre $origemCidade e $destinoCidade é de aproximadamente $distancia km.";
        echo "<br>";
        echo "O tempo de viagem de carro é de aproximadamente $duracao minutos.";
    } else {
        echo "Não foi possível calcular a rota entre as cidades.";
    }
}else{
    echo "Não foi possível encontrar uma ou ambas as cidades.";
}
?>
