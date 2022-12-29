<?php
set_time_limit(0);
require_once __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require_once __DIR__ . '/vendor/shuchkin/simplecsv/src/SimpleCSV.php';
require_once __DIR__ . '/vendor/shuchkin/simplexls/src/SimpleXLS.php';
require_once __DIR__ . '/vendor/shuchkin/simplexlsx/src/SimpleXLSX.php';

$log = new Logger('import');
$log->pushHandler(new StreamHandler('import-'.date('y-m-d').'.log', Logger::DEBUG));

// add records to the log
$log->info('inicianco leitura do arquivo.');

$arquivo = $_FILES['arquivo'];

$arquivoNome = $arquivo['name'];

$arquivoTitulo = explode('.',$arquivoNome)[0];

$arquivoTipo = explode('.',$arquivoNome)[1];

$log->debug('Arquivo importado: ' . $arquivoNome);
$log->debug('Tipo do arquivo importado: ' . $arquivoTipo);

$log->debug('criando pasta para importar upload');

$caminhoUpload = __DIR__.'/files/'.$arquivoTitulo.'-'.date('Y-m-d').'/';

$dir = false;

$log->debug($caminhoUpload);

if(!is_dir($caminhoUpload)){
    
    if(mkdir($caminhoUpload, 0777, true)){
        
        $log->debug('pasta criada com sucesso!');
        
        $dir = true;
        
    }else{
        
        $log->error('Erro ao criar pasta!');
        
    }
    
}else{
    $log->debug('Pasta já existe.');
    
    $dir = true;
    
}

if($dir){
    $log->debug('Movendo upload para a pasta');
    
    $caminhoArquivoSalvo = $caminhoUpload . basename($arquivo['name']);
    
    if(move_uploaded_file($arquivo['tmp_name'], $caminhoArquivoSalvo)){
        
        $log->debug('Upload salvo com sucesso!');
        
        $log->debug('Arquivo salvo: ' . $caminhoArquivoSalvo);
    }else{
        $log->error('Erro ao mover upload: ' .$arquivo['name']["error"]);
    }
}


function salvaImagem($tag,$id,$url,$pasta){

    $nomeImagem = $id;
    
    $arquivoImagem = @file_get_contents($url);

    if($arquivoImagem){
        file_put_contents($pasta.'/'.$nomeImagem . '.png',$arquivoImagem);

        return true;
    }else{
        return false;
    }
    
}

function getUrlContent($url,$pasta){

    echo $url;

    $nomeImagemArray = explode('/',explode('&',$url)[0]);
    
    $ultimoSegmento = count($nomeImagemArray) -1;
    
    $nomeImagem = $nomeImagemArray[$ultimoSegmento];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $data = curl_exec($ch);

    print_r($data);

    file_put_contents($pasta.'/'.$nomeImagem . '.png',$data);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($httpcode>=200 && $httpcode<300) ? $data : false;
    }

function apagaArquvo($arquivo){
    unlink($arquivo);
}
function selecionaIndiceLink($array){
    $indice = 0;
    foreach($array as $coluna){
        if(substr($coluna,0,4) == 'http'){
            return $indice;
        }else{
            $indice ++;
        }
    }  
    
}
function criaZip($caminho, $arquivo){
    // Get real path for our folder
    $rootPath = realpath($caminho);
    
    // Initialize archive object
    $zip = new ZipArchive();
    $zip->open($arquivo, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    
    // Create recursive directory iterator
    /** @var SplFileInfo[] $files */
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $name => $file)
    {
        // Skip directories (they would be added automatically)
        if (!$file->isDir())
        {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);
            
            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
    }
    
    // Zip archive will be created only after closing object
    $zip->close();

    $file_name = basename($arquivo);

    header("Content-Type: application/zip");
    header("Content-Disposition: attachment; filename=$file_name");
    header("Content-Length: " . filesize($arquivo));

    readfile($arquivo);
    exit;
}

switch ($arquivoTipo) {
    case 'csv':
        $arquivoDados = SimpleCSV::import($caminhoArquivoSalvo);
        break;
        
        case 'xls':
        $arquivoDados = SimpleXLS::parseFile($caminhoArquivoSalvo)->rows();
        break;
        
        case 'xlsx':
        $arquivoDados = SimpleXLSX::parse($caminhoArquivoSalvo)->rows();
        break;
        
        default:
        $log->error('Tipo de arquivo não reconhecido:' . $caminhoArquivoSalvo);
        break;
        
}

$totalRegistros = count($arquivoDados);

$log->debug('Total de registros: ' . $totalRegistros);

$totalProcessados = 0;

apagaArquvo($caminhoArquivoSalvo);

$listaLinkFalha = [];

foreach($arquivoDados as $linha){
    $Dlinha = print_r($linha, true);
    
    $log->debug($Dlinha);
    $id = $linha[0];
    $linkImagem = 'https://chart.apis.google.com/chart?cht=qr&chl=https://kmk.dimetrika.com/checklist/'.$id.'&chs=200x200';
    $log->debug($linkImagem);
    $tag = str_replace('|','-',$linha[1]);
    $empresa = explode("\\",$linha[3])[0];
    $local = $linha[2];
    $log->debug($empresa);
    
    $caminhoUploadN = $caminhoUpload.$empresa.'/'.$local;

    $log->debug($caminhoUploadN);

    if(!is_dir($caminhoUploadN)){
        
        if(mkdir($caminhoUploadN, 0777, true)){
            
            $log->debug('pasta criada com sucesso!');
            
            $dir = true;
            
        }else{
            
            $log->error('Erro ao criar pasta!');
            
        }
        
    }else{
        $log->debug('Pasta já existe.');
        
        $dir = true;
        
    }

    if(substr($linkImagem,0,4) == 'http'){
        if(salvaImagem($tag,$id,$linkImagem,$caminhoUploadN)){
            $log->debug('Arquivo salvo com sucesso: ' . $linkImagem);
            sleep(1);
            $totalProcessados++;
        }else{
            $log->error('Erro ao salvar arquivo: ' . $linkImagem);
            $listaLinkFalha[] = $linkImagem;
            sleep(1);
        }
    }
}
$log->debug('Total de registros processados: ' . $totalProcessados);
echo 'Total de registros processados: ' . $totalProcessados . '<br>';

criaZip($caminhoUpload, $arquivoNome.' - '.date('Y-m-d') . '.zip');