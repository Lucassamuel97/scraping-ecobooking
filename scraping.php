<?php

ini_set('max_execution_time', 0);
set_time_limit(0);
ini_set('memory_limit', '-1');

// ini_set('display_errors',1);
// ini_set('display_startup_erros',1);
// error_reporting(E_ALL);

require 'vendor/autoload.php'; 

use Goutte\Client;

$client = new Client();

$url = 'https://ecobooking.com.br/reports/eventos/CalendarioEstadual.php?TAG=pr';

$crawler = $client->request('GET', $url);

function geocodificar($cidade) {

    $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($cidade) . "&format=json";
    
    // $url = 'https://nominatim.openstreetmap.org/search?q=grindelwald&countryCodes=CH&addressdetails=1&format=json';
    $options = [
        'http' => [
            'user_agent' => 'Samuca teste',
        ],
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    // Decodifica a resposta JSON
    $data = json_decode($response, true);
    
    if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
        // Extrai latitude e longitude
        $lat = $data[0]['lat'];
        $lon = $data[0]['lon'];
        return array('lat' => $lat, 'lon' => $lon);
    } else {
        return null; 
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


$origemCidade = "PR-Palmital";
$origemCoordenadas = geocodificar($origemCidade);

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title></title>

    <style type="text/css">
        /* Estilo CSS para a tabela */
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(odd) {
            background-color: #f9f9f9;
        }
        tr:nth-child(even) {
            background-color: #ffffff;
        }
        .verde{
            background-color: green !important;
        }
    </style>
</head>
<body>

    <table>
        <thead>
            <tr>
                <th colspan="6">Origem : <?=$origemCidade?></th>
            </tr>
            <tr>
                <th>Data</th>
                <th>Cidade</th>
                <th>Km</th>
                <th>Tempo</th>
                <th>Inscricao</th>
                <th>Nome</th>
            </tr>
        </thead>


        <?php
        $insideDesiredSection = false;

        $crawler->filter('table tr')->each(function ($row) use (&$insideDesiredSection) {
            $cells = $row->filter('td');

            if ($cells->count() > 2 && $cells->first()->text() === '1*)') {
                $insideDesiredSection = true;
            }

            if ($insideDesiredSection){

                if ($cells->count() > 1){

                    global $origemCoordenadas;

                    $destinoCidade = $cells->eq(2)->text();
                    $destinoCoordenadas = geocodificar($destinoCidade);

                    $km = 0;
                    $tempo = 0;

                    if ($origemCoordenadas && $destinoCoordenadas) {

                        $resultado = calcularRota($origemCoordenadas, $destinoCoordenadas);

                        if ($resultado) {
                            $km = $resultado['distancia'];
                            $tempo = $resultado['duracao'];

                            $numero = preg_replace('/[^0-9.]/', '', $km);
                            // Converte a string para float
                            $numero = floatval($numero);
                            
                            if ($numero <= 250) {
                                $cor = "verde";
                            }else{
                                $cor = "";
                            }
                        }else{
                            $km = "Erro ao calcular";
                            $tempo = 0;
                        }
                    }

                    echo "<tr class='$cor'>
                    <td>".$cells->eq(1)->text()."</td>
                    <td>".$destinoCidade."</td>
                    <td>$km</td>
                    <td>$tempo</td>
                    <td>".$cells->eq(4)->text()."</td>
                    <td>".$cells->eq(5)->text()."</td>
                    </tr>";
                }

        // Se encontrarmos o marcador de fim da seção, pare de extrair
        // if ($cells->first()->text() === '4)') {
        //     $insideDesiredSection = false;
        // }
            }
        });


        ?>

    </table>


</body>
</html>