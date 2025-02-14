<?php
session_start();
include_once 'jobs.php';
$JOBS = new Jobs();
$API['url'] = 'http://172.17.254.160:7860';
$API['models'] = models_get();
if(!count($API['models'])) {
    $API['models'] = array(
        0 => 'epicrealism_pureEvolutionV3',
        1 => 'fasercore_ponyV2FP16',
        2 => 'nude-woman-front-v2.0',
        3 => 'pornmasterPro_sdxlV1VAE',
        4 => 'realmixpony_rev07',
        5 => 'realvisxlV50_v50LightningBakedvae',
        6 => 'sd-v1-5-inpainting',
        7 => 'UltraRealPhoto',
        8 => 'v1-5-pruned-emaonly',
        9 => 'dreamshaper_8',
        10 => 'dreamshaperXL_v21TurboDPMSDE',
        11 => 'juggernautXL_juggXIByRundiffusion',
        12 => 'landscapeRealistic_v20WarmColor',
        13 => 'sdXL_v10VAEFix'
    );
}

$API['sampler'] = array(
    0=>'DPM++ 2M',
    1=>'DPM++ SDE',
    2=>'DPM++ 2M SDE',
    3=>'DPM++ 2M SDE Heun',
    4=>'DPM++ 2S a',
    5=>'DPM++ 3M SDE',
    6=>'Euler a',
    7=>'Euler',
    8=>'LMS',
    9=>'Heun',
    10=>'DPM2',
    11=>'DPM2 a',
    12=>'DPM fast',
    13=>'DPM adaptive',
    14=>'Restart',
    15=>'DDIM',
    16=>'PLMS',
    17=>'UniPC',
    18=>'LCM'
);
$API['model_cur'] = 0; if(isset($_POST['model'])) $API['model_cur'] = $_POST['model'];
if(!isset($_SESSION['AIanswer'])) $_SESSION['AIanswer']=array();
if(isset($_GET['folder'])){
    $folder = str_replace('.','',$_GET['folder']);
    $directoryPath = 'photos/'.$folder;
    if(file_exists($directoryPath)) {
        $archiveFileName = $folder.'.zip';
        $zip = new ZipArchive;
        if ($zip->open('photos/' . $archiveFileName, ZipArchive::CREATE) === TRUE) {
            addFilesToArchive($zip, $directoryPath);
            $zip->close();
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $archiveFileName . '"');
            readfile('photos/' . $archiveFileName);
        } else {
            echo 'Ошибка создания архива.';
        }
    }else{
        echo 'Нет такого пути';
    }
    exit();
}

if(isset($_POST['form_type'])){
    header('Content-Type: application/json; charset=utf-8');
    $out=array();
    if($_POST['form_type']=='generate'){
        //$rez = generate_photo($_POST); // для разработки, ниже 5 строк комментировать
        $data_post=$_POST;
        $data_post['model'] = $API['models'][$_POST['model']];
        $data_post['sampler_name'] = $API['sampler'][$_POST['sampler_name']];
        $_SESSION['post'] = $_POST;
        $rez = $JOBS->generate_photo($data_post);
        if(!$rez['err']) {
            $out['err'] = 0;
            $out['html'] = '';
            foreach($rez['photos'] as $k => $photo){
                $photo = str_replace('/var/www/html/','',$photo);
                $out['html'] .= '<img class="img-fluid" src="/'.$photo.'">';
            }
        }else{
            $out['err'] = 1;
            $out['html'] = '<h2>Ошибка</h2><pre style="text-align: left;">'.print_r($rez['deb'],1).'</pre>';
        }
    }
    echo json_encode($out);
    exit();
}

echo HTML();
exit();

function models_get(){
    $out = array();
    $models = cCurl(array(),'/sdapi/v1/sd-models');
    $models = json_decode($models['out'],1);
    foreach($models as $k => $v){
        $out[] = $v['model_name'];
    }
    return $out;
}

function HTML_JOBS(){
    $out = '
    <div class="container text-center">
        <div class="row">
            <div class="col">
                <div class="result"></div>
            </div>
        </div>
    </div>';
    include_once 'jobs.php';
    $JOBS = new JOBS();
    $files_jobs = $JOBS->jobs_list();
    $out .= '<table class="table">';
        $out .= '<thead><tr>';
            $out .= '<th>Директория</th>';
            $out .= '<th>Промтов</th>';
            $out .= '<th>Модель</th>';
            $out .= '<th>Сэмплер</th>';
            $out .= '<th>CFG</th>';
            $out .= '<th>Шагов</th>';
            $out .= '<th>Фоток</th>';
            $out .= '<th>Ширина</th>';
            $out .= '<th>Высота</th>';
            $out .= '<th>Статус</th>';
        $out .= '</tr></thead><tbody>';
    //echo '<pre>'.print_r($files_jobs,1).'</pre>';
    foreach($files_jobs as $k => $job){
        if(isset($job['dir_name'])) {
            $out .= '<tr>';
            $out .= '<td>' . $job['dir_name'] . '</td>';
            $out .= '<td>' . count($job['prompts']) . '</td>';
            $out .= '<td>' . $job['sd_model_checkpoint'] . '</td>';
            $out .= '<td>' . $job['sampler_name'] . '</td>';
            $out .= '<td>' . $job['cfg_scale'] . '</td>';
            $out .= '<td>' . $job['steps'] . '</td>';
            $out .= '<td>' . $job['batch_size'] . '</td>';
            $out .= '<td>' . $job['width'] . '</td>';
            $out .= '<td>' . $job['height'] . '</td>';
            $out .= '<td>' . $job['status'] . '</td>';
            $out .= '</tr>';
        }
    }
    $out .= '</tbody></table>';
    return $out;
}

function HTML_file_upload(){
    $out = '
    <div class="row g-3">
        <div class="col-8">
            <label for="file_CSV" class="form-label">Обработка файла <a href="help/CSV_templ.xlsx">CSV</a> </label>
            <input type="file" class="form-control" name="file_CSV" id="file_CSV" accept=".csv" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Загрузите файл формата CSV, выше пример файла (пересохранить в CSV!)">
        </div>
        <div class="col-4">
            <label for="file_CSVu" class="form-label"><input type="checkbox" id="file_CSV_start" class="form-check-input" data-bs-toggle="tooltip" data-bs-placement="bottom" title="т.к. файл долго будет обрабатываться - он запускается в фоне! и галочка говорит о том чтобы сразу его ставить на запуск или позже в списке"> старт</label>
            <a href="#" class="btn btn-warning" id="file_CSVu">Загрузить</a>
        </div>
    </div>';
    $out.='<script>$(document).ready(function(){
        var files;
        $("input[type=file]").on("change", function(){
            files = this.files;
        });
        $("#file_CSVu").click(function(){
            event.stopPropagation(); // остановка всех текущих JS событий
            event.preventDefault();  // остановка дефолтного события для текущего элемента - клик для <a> тега
            // ничего не делаем если files пустой
            if( typeof files == "undefined" ) return;
            // создадим объект данных формы
            var data = new FormData();
            // заполняем объект данных файлами в подходящем для отправки формате
            $.each( files, function( key, value ){
                data.append( key, value );
            });
            // добавим переменную для идентификации запроса
            data.append( "csv_file_upload", 1 );
            let start = 0; if ($("#file_CSV_start").is(":checked")) { start=1 }
            data.append( "start", start );
            $.ajax({
                url         : "./upload_f.php",
                type        : "POST",
                data        : data,
                cache       : false,
                dataType    : "json",
                processData : false,// отключаем обработку передаваемых данных, пусть передаются как есть
                contentType : false,// отключаем установку заголовка типа запроса. Так jQuery скажет серверу что это строковой запрос
                // функция успешного ответа сервера
                success     : function( respond, status, jqXHR ){// ОК - файлы загружены
                    console.log(respond);
                    if( typeof respond.error === "undefined" ){
                        // выведем пути загруженных файлов в блок ".ajax-reply"
                        //var files_path = respond.files;
                        var html = "Всего файлов:"+respond.kol+"; Успех:"+respond.suc+"; Ошибок:"+respond.err+"; Поставили в обработку...";
                        //$.each( files_path, function( key, val ){
                        //     html += val +"<br>";
                        //} )
                        $(".ajax-reply").html( html );
                    }
                    else { // ошибка
                        console.log("ОШИБКА: " + respond.data );
                    }
                },
                error: function( jqXHR, status, errorThrown ){// функция ошибки ответа сервера
                    console.log( "ОШИБКА AJAX запроса: " + status, jqXHR );
                }
            });
            return false;
        });
});</script>';
    return $out;
}

function HTML_config(){
    GLOBAL $API;
    $out = '';
    if(!isset($_SESSION['post']['model'])) $_SESSION['post']['model'] = $API['models'][0];
    if(!isset($_SESSION['post']['sampler_name'])) $_SESSION['post']['sampler_name'] = $API['sampler_name'][0];
    if(!isset($_SESSION['post']['steps'])) $_SESSION['post']['steps'] = 20;
    if(!isset($_SESSION['post']['photo_count'])) $_SESSION['post']['photo_count'] = 4;
    if(!isset($_SESSION['post']['width'])) $_SESSION['post']['width'] = 512;
    if(!isset($_SESSION['post']['height'])) $_SESSION['post']['height'] = 512;
    $seed=-1;if(isset($_SESSION['post']['seed'])) $seed=$_SESSION['post']['seed'];
    $cfg_scale=7;if(isset($_SESSION['post']['cfg_scale'])) $cfg_scale=$_SESSION['post']['cfg_scale'];
    $steps=20;if(isset($_SESSION['post']['steps'])) $steps=$_SESSION['post']['steps'];
    $photo_count=4;if(isset($_SESSION['post']['photo_count'])) $photo_count=$_SESSION['post']['photo_count'];
    $width=512;if(isset($_SESSION['post']['width'])) $width=$_SESSION['post']['width'];
    $height=512;if(isset($_SESSION['post']['height'])) $height=$_SESSION['post']['height'];

    $out_models=''; foreach ($API['models'] as $k => $model){
        $sel=''; if($API['model_cur']==$k) $sel=' selected';
        if($_SESSION['post']['model']==$k) $sel=' selected';
        $out_models .= '<option value="'.$k.'" '.$sel.'>'.$model.'</option>';
    }
    $out_sampler=''; foreach ($API['sampler'] as $k => $sampler){
        $sel=''; if($_SESSION['post']['sampler_name']==$k) $sel=' selected';
        $out_sampler .= '<option value="'.$k.'" '.$sel.'>'.$sampler.'</option>';
    }

    $out .= '<div class="row g-3">
        <div class="col-6">
            <label for="model" class="form-label" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Модель">Модель</label>
            <select class="form-select" aria-label="Выбор модели" name="model">'.$out_models.'</select>
        </div>
        <div class="col-6">
            <label for="sampler_name" class="form-label" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Sampler">Sampler</label>
            <select class="form-select" aria-label="Выбор Sampler" name="sampler_name">'.$out_sampler.'</select>
        </div>
    </div>';

    $out .= '<div class="row g-3">
        <div class="col-6">
            <label for="seed" class="form-label">Seed</label>
            <input type="number" class="form-control" id="seed" name="seed" value="'.$seed.'" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Seed">
        </div>   
        <div class="col-6">
            <label for="cfg_scale" class="form-label">CFG scale</label>
            <input type="number" class="form-control" id="cfg_scale" name="cfg_scale" value="'.$cfg_scale.'" data-bs-toggle="tooltip" data-bs-placement="bottom" title="CFG scale">
        </div>
    </div>';

    $out .= '<div class="row g-3">
        <div class="col-6">
            <label for="steps" class="form-label">Steps</label>
            <input type="number" class="form-control" name="steps" id="steps" placeholder="20" value="'.$steps.'" data-bs-toggle="tooltip" data-bs-placement="bottom" title="steps">
        </div>
        <div class="col-6">
            <label for="photo_count" class="form-label">Кол-во фото</label>
            <input type="number" class="form-control" name="photo_count" id="photo_count" placeholder="4" value="'.$photo_count.'" data-bs-toggle="tooltip" data-bs-placement="bottom" title="batch_count За один запрос получим указанное количество фото!">
        </div>
    </div>';

    $out .= '<div class="row g-3">
        <div class="col-4">
            <label for="width" class="form-label" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Ширина изображения">Ширина</label>
            <input type="number" class="form-control" name="width" id="width" placeholder="512" value="'.$width.'">
        </div>
        <div class="col-4">
            <label for="width" class="form-label" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Высота изображения">Высота</label>
            <input type="number" class="form-control" name="height" id="height" placeholder="512" value="'.$height.'">
        </div>
        <div class="col-4">
            <label for="path_dir" class="form-label" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Сохранить результат в директорию с именем указанным тут. Ниже можно скачать архив с фотками">Директория</label>
            <input type="text" class="form-control" name="path_dir" id="path_dir" placeholder="food" value="food">
        </div>
    </div>';
    return $out;
}

function mod_get($mod){
    //FULL
//    $out['ADetailer'] = json_decode('{
//      "args": [ true, false,{
//          "ad_model": "face_yolov8n.pt",        "ad_model_classes": "",         "ad_tab_enable": true,
//          "ad_prompt": "",                      "ad_negative_prompt": "",       "ad_confidence": 0.3,
//          "ad_mask_filter_method": "Area",      "ad_mask_k": 0,
//          "ad_mask_min_ratio": 0.0,             "ad_mask_max_ratio": 1.0,       "ad_dilate_erode": 4,
//          "ad_x_offset": 0,                     "ad_y_offset": 0,               "ad_mask_merge_invert": "None",
//          "ad_mask_blur": 4,                    "ad_denoising_strength": 0.4,   "ad_inpaint_only_masked": true,
//          "ad_inpaint_only_masked_padding": 32, "ad_use_inpaint_width_height": false,
//          "ad_inpaint_width": 512,              "ad_inpaint_height": 512,       "ad_use_steps": false,
//          "ad_steps": 28,                       "ad_use_cfg_scale": false,      "ad_cfg_scale": 7.0,
//          "ad_use_checkpoint": false,           "ad_checkpoint": null,          "ad_use_vae": false,
//          "ad_vae": null,                       "ad_use_sampler": false,        "ad_sampler": "DPM++ 2M Karras",
//          "ad_scheduler": "Use same scheduler", "ad_use_noise_multiplier": false,   "ad_noise_multiplier": 1.0,
//          "ad_use_clip_skip": false,            "ad_clip_skip": 1,              "ad_restore_face": false,
//          "ad_controlnet_model": "None",        "ad_controlnet_module": "None", "ad_controlnet_weight": 1.0,
//          "ad_controlnet_guidance_start": 0.0,  "ad_controlnet_guidance_end": 1.0
//        }
//      ]
//    }',1);
    //{ "ad_model": "person_yolov8s-seg.pt","ad_tab_enable": true },
    //MIN
    $out['ADetailer'] = json_decode('{
      "args": [ true, false,
        { "ad_model": "face_yolov8n.pt","ad_tab_enable": true,"ad_confidence": 0.3, "ad_sampler": "DPM++ 2M Karras" },
        { "ad_model": "hand_yolov8n.pt","ad_tab_enable": true,"ad_confidence": 0.8, "ad_sampler": "DPM++ 2M Karras","ad_prompt":"[PROMPT], maximum five fingers" }
      ]
    }',1);

    return $out;
}

function generate_photo($data){
    GLOBAL $API; $out =array();
    //http://172.17.254.160:7860/docs#/default/build_resource_assets__path__get
    if(!isset($data['photo_count'])) $data['photo_count']=1;
    elseif($data['photo_count']=='') $data['photo_count']=1;

    $model = $API['models'][0]; if(isset($data['model'])) $model = $API['models'][$data['model']];
    $sampler_name = $API['sampler'][0]; if(isset($data['sampler_name'])) $sampler_name = $API['sampler'][$data['sampler_name']];
    $_SESSION['post'] = $data;
    //http://172.17.254.160:7860/docs#/default/build_resource_assets__path__get
    $post = array(
        'prompt'            => $data['prompt_suc'],
        'negative_prompt'   => $data['prompt_neg'],
        'batch_size'        => $data['photo_count'],
        'cfg_scale'         => $data['cfg_scale'],
        'seed'              => $data['seed'],
        'width'             => $data['width'],
        'height'            => $data['height'],
        'steps'             => $data['steps'],
        'sampler_name'      => $sampler_name,
        'restore_faces'     => true,
        'alwayson_scripts'  => mod_get('adetailer'),
        'override_settings' => array(
            'sd_model_checkpoint'       => $model,
            'CLIP_stop_at_last_layers'  => 2,
        )
    );
    $rez = cCurl($post,'/sdapi/v1/txt2img');
    if($rez['info']['http_code']==200){
        $out1 = json_decode($rez['out'],1);
        if(isset($out1['error'])&&$out1['error']!=''){
            $out['err'] = 1;
            $out['deb']=json_decode($out1,1);
        }else {
            $_SESSION['AIanswer'] = $out1;
            $out1['info'] = json_decode($out1['info'], 1);
            $out1['photo_dir'] = $data['path_dir'];
            $out = parse_answer($out1);
            $out['err'] = 0;
        }
    }else{
        $out['err']=1;
        $out['deb']=json_decode($rez['out'],1);
    }
    return $out;
}

function get_photos_dir(){
    $out = array();
    $dir = 'photos/';
    $files = scandir($dir);
    foreach($files as $k => $file){
        if($file!='.' && $file!='..'){
            $item_cur = $dir.$file;
            if(is_dir($item_cur)) $out[] = $item_cur;
        }
    }
    return $out;
}

// Функция для добавления файлов в архив
function addFilesToArchive($zip, $directoryPath) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directoryPath));
    foreach ($iterator as $file) {
        if (!$file->isDir()) {
            $relativePath = substr($file->getPathname(), strlen($directoryPath) + 1);
            $zip->addFile($file->getPathname(), $relativePath);
        }
    }
}

function parse_answer($answer){
    $out = array();
    $dir = 'photos/';
    if($answer['photo_dir']!=''){
        $dir .= $answer['photo_dir'] . '/';
        if(!is_dir($dir)) mkdir($dir,0777,true);
    }
    foreach($answer['images'] as $k => $img) {
        $photo = $dir . time().'_'.$k. '.png';
        file_put_contents($photo, base64_decode($img));
        $out['photos'][] = $photo;
    }
    return $out;
}

function cCurl($post,$url='/sdapi/v1/txt2img'){
    GLOBAL $API;
    $out=array();
    if( $curl = curl_init() ) {
        curl_setopt($curl, CURLOPT_URL, $API['url'].$url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        if(count($post)) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post));
        }
        $headers = array(
            "accept: application/json",
            "Content-Type: application/json",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $out = substr($response, $header_size);
        $info = curl_getinfo($curl);
        curl_close($curl);
    }
    return array('out'=>$out,'header'=>$header,'info'=>$info);
}

function promptex_tab(){
    $out='';
    $dir = 'prompts/';
    $files = scandir($dir);
    foreach($files as $file)if($file!='.'&&$file!='..'){
        $json = json_decode(file_get_contents($dir.$file),1);
        if(isset($json['prompts'])){
            foreach($json['prompts'] as $k => $v){
                $out .= '<a href="#" class="list-group-item list-group-item-action" data-replace=1 data-prompt1="'.$v['prompt'].'" data-prompt0="'.$v['negative'].'">'.$v['title'].'</a>'.PHP_EOL;
            }
        }
    }
    return $out;
}

function HTML(){
    GLOBAL $API;
    if(!isset($_SESSION['post']['prompt_suc'])) $_SESSION['post']['prompt_suc']='';
    if(!isset($_SESSION['post']['prompt_neg'])) $_SESSION['post']['prompt_neg']='';
    if(!isset($_SESSION['post']['sampler_name'])) $_SESSION['post']['sampler_name']=0;
    $out = '';
    $out .= '<!doctype html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI generate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
  </head>
  <body>
    <div class="container text-center">
        <div class="row">
            <div class="col"><h2>Генерация фоток</h2></div>
        </div>
    </div>
            
    <div class="container text-center">
        <form method="post" id="form_ai">
        <div class="row">
            <div class="col">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Промпты готовые, которые заменяют промпт пользователя!">
                        <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">Примеры</button>
                    </li>
                    <li class="nav-item" role="presentation" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Улучшения к промптам, добавляются к тексту промпта пользователя!">
                        <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">Улучшения</button>
                    </li>
                    <li class="nav-item" role="presentation" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Подборка готовых Промтов">
                        <button class="nav-link" id="promptex-tab" data-bs-toggle="tab" data-bs-target="#promptex-tab-pane" type="button" role="tab" aria-controls="promptex-tab-pane" aria-selected="false">Промты</button>
                    </li>
                </ul>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" aria-labelledby="home-tab" tabindex="0">
                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action" data-replace=1 data-copytext="Food photography of a gourmet meal, with a shallow depth of field, elegant plating, and soft lighting.">Пирожное, украшенное ягодами, на белой тарелке</a>
                            <a href="#" class="list-group-item list-group-item-action" data-replace=1 data-copytext="A bundle of berries with a juicy splashy background by midjourney, CGI, HD, 4K, 8K, cinematic photorealistic masterpiece.">Ежевика, малина и черника крупным планом</a>
                            <a href="#" class="list-group-item list-group-item-action" data-replace=1 data-copytext="Highly detailed epic Unconventional composition photo of cup of coffee espresso, [haunted, interesting, attractive, intricate, dystopian, extremely detailed], (abstract vfx fluid coffee floating liquid), [[music artist album cover attention-grabbing concept-design]], thought-provoking composition, [airy depth of field volumetric dusty slow motion bokeh], [[hazed detailed rainy foggy moody haunted]], 16bit artistry, Capcom artists incredible beautiful (hyperdetailed Pixel-art: 1. 2) by Neogeo, simple clean composition, strong lines.">Чашка, в которую наливают кофе</a>
                            <a href="#" class="list-group-item list-group-item-action" data-replace=1 data-copytext="Hamburger, french fries, Food for the trip. Fast food.+ fine ultra-detailed realistic + ultra photorealistic + Hasselblad H6D + high definition + 8k + cinematic + color grading + depth of field + photo-realistic + film lighting + rim lighting + intricate + realism + maximalist detail + very realistic.">Гамбургер с картошкой фри</a>
                            <a href="#" class="list-group-item list-group-item-action" data-replace=1 data-copytext="Inspired by realflow-cinema4d editor features, create image of a transparent luxury cup with ice fruits and mint, connected with white, yellow and pink cream, Slow — High Speed MO Photography, 4K Commercial Food, YouTube Video Screenshot, Abstract Clay, Transparent Cup, molecular gastronomy, wheel, 3D fluid, Simulation rendering, still video, 4k polymer clay futras photography, very surreal, Houdini Fluid Simulation, hyperrealistic CGI and FLUIDS & MULTIPHYSICS SIMULATION effect, with Somali Stain Lurex, Metallic Jacquard, Gold Thread, Mulberry Silk, Toub Saree, Warm background, a fantastic image worthy of an award.">Бокал с ягодным напитком</a>
                            <a href="#" class="list-group-item list-group-item-action" data-replace=1 data-copytext="Indulge in a healthy and flavorful meal with our baked salmon and roasted vegetables.">Филе сёмги, запечённое с овощами</a>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action" data-replace=0 data-copytext="Glamour shot of an instagram model, ocean grunge style">Гламурный снимок</a>
                            <a href="#" class="list-group-item list-group-item-action" data-replace=0 data-copytext="Portrait, mint green linen scarf, realistic image">Portrait</a>
                            <a href="#" class="list-group-item list-group-item-action" data-replace=0 data-copytext="candid photo, dreamlike lighting, sunlight shining through in her hair, 85mm, f 1.2">Скрытая фотография</a>
                            <a href="#" class="list-group-item list-group-item-action" data-replace=0 data-copytext="Micro photography, ultra detailed">Micro Photography</a>
                            <a href="#" class="list-group-item list-group-item-action" data-replace=0 data-copytext="Catalogue Photography, reflective background, ultra detailed">Фотография в каталоге</a>
                            <a href="#" class="list-group-item list-group-item-action" data-replace=0 data-copytext="Food photography, Luxurious Michelin Kitchen Style">Food Photography</a>
                            <a href="#" class="list-group-item list-group-item-action" data-replace=0 data-copytext="Time-Lapse Photography, UHD image, high contrast">Фотосъемка с замедлением времени</a>
                            <a href="#" class="list-group-item list-group-item-action" data-replace=0 data-copytext="High-speed photography, black background, uhd image">Высокоскоростная фотография</a>
                            <a href="#" class="list-group-item list-group-item-action" data-replace=0 data-copytext="deep depth of field, sharp background">Глубокая глубина резкости</a>
                            <a href="#" class="list-group-item list-group-item-action" data-replace=0 data-copytext="vibrant colors, rack focus photo, golden hour, UHD">Rack Focus</a>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="promptex-tab-pane" role="tabpanel" aria-labelledby="promptex-tab" tabindex="0">
                        <div class="list-group" style="overflow: auto;max-height: 290px;">'.promptex_tab().'</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    <label for="prompt_suc" class="form-label" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Текст пропта, который идет в ИИ, англ.язык">Промпт</label>
                    <textarea class="form-control" id="prompt_suc" name="prompt_suc" rows="3">'.$_SESSION['post']['prompt_suc'].'</textarea>
                </div>
                <div class="mb-3">
                    <label for="prompt_neg" class="form-label">Промпт негатив</label>
                    <textarea class="form-control" id="prompt_neg" name="prompt_neg" rows="3">'.$_SESSION['post']['prompt_neg'].'</textarea>
                </div>
                '.HTML_file_upload().'
            </div>
            <div class="col">';
    $out .= HTML_config();
    $out .= '<input type="hidden" name="form_type" value="generate">
                <div class="send_ready" ><input type="submit" id="send" name="send" class="btn btn-success"></div>
                <div class="send_work" style="display:none;">работаем...</div>
                <div class="send_error" style="display:none;"></div>
            </div>
        </div>
        </form>
    </div>
    <hr>
    <div class="container text-center">
        <div class="row">
            <div class="col"><span class="badge text-bg-secondary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Получить архив директории с фотками!">Скачать архив: </span>';
    $folders = get_photos_dir();
    foreach ($folders as $folder){
        $folder = str_replace('photos/','',$folder);
        $out .= '<a href="/?folder='.$folder.'" class="btn btn-info">'.$folder.'</a>';
    }
    $out .='
            </div>
        </div>
    </div>
    <pre style="display: none;">'.print_r($_SESSION['AIanswer'],1).'</pre>
    <div class="container">
        <div class="row">
            <div class="col ajax-reply">
            </div>
        </div>
    </div>
    '.HTML_JOBS().'
    <script>$(document).ready(function(){
    $("#send").click(function(){
        let prompt_suc = $("#prompt_suc").val();
        if(prompt_suc!=""){
            $(".send_error").hide();
            $(".send_ready").hide();
            $(".send_work").show();
            var datastring = $("#form_ai").serialize();
            $.ajax({url: "/",cache:false,datatype:"json",method:"post",data: datastring,
                success:function (data){
                    console.log(data);
                    if(data.err==0){
                        $(".send_ready").show();
                        $(".send_work").hide();
                        $(".result").html(data.html);
                    }else{
                        $(".send_ready").show();
                        $(".send_work").hide();
                        $(".result").html(data.html);
                    }
                }
            });
        }
        return false;
    });
    $("a[data-copytext]").click(function(event) {
        event.preventDefault();
        var replace=0;
        replace = $(this).attr("data-replace");
        var text = $(this).attr("data-copytext");
        if(replace==1){
            $("#prompt_suc").val( text);
        }else{
            var text_cur = $("#prompt_suc").val();
            $("#prompt_suc").val( text_cur +" "+ text);
        }
    });
    $("a[data-prompt1]").click(function(event) {
        event.preventDefault();
        var replace=0;
        replace = $(this).attr("data-replace");
        var prompt_suc = $(this).attr("data-prompt1");
        var prompt_neg = $(this).attr("data-prompt0");
        if(replace==1){
            $("#prompt_suc").val(prompt_suc);
            $("#prompt_neg").val(prompt_neg);
        }else{
            var text_cur = $("#prompt_suc").val();
            $("#prompt_suc").val( text_cur +" "+ prompt_suc);
            var text_cur = $("#prompt_neg").val();
            $("#prompt_neg").val( text_cur +" "+ prompt_neg);
        }
    });
    var tooltipTriggerList = [].slice.call(document.querySelectorAll("[data-bs-toggle=\"tooltip\"]"))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl) });
    })</script>
  </body>
</html>';
    return $out;
}