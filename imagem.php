<?php

$url = "https://queryman.dimetrika.com/qr/kmk.dimetrika.com/checklist/1546634&res=200x200";

$nomeImagemArray = explode('/',explode('&',$url)[0]);

$ultimoSegmento = count($nomeImagemArray) -1;

$nomeImagem = $nomeImagemArray[$ultimoSegmento] . '.png';

file_put_contents($nomeImagem,file_get_contents($url));

print_r($nomeImagem);
