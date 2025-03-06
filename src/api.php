<?php
$rez = array();
$out = array();
if(isset($_GET['prompt'])){
    include_once 'jobs.php';
    $JOBS = new Jobs();
    $data_post['seed']=-1;
    $data_post['cfg_scale']=7;
    $data_post['steps']=20;
    $data_post['photo_count']=1;
    $data_post['model'] = 'juggernautXL_juggXIByRundiffusion';
    $data_post['sampler_name'] = 'DPM++ 2M';
    $data_post['prompt_suc'] = $_GET['prompt'];
    $data_post['prompt_neg'] = $_GET['negative'];
    $data_post['width'] = 512;
    $data_post['height'] = 512;
    $data_post['path_dir'] = 'api';
    $rez = $JOBS->generate_photo($data_post);
    if(!$rez['err']) {
        $out['err'] = 0;
        foreach($rez['photos'] as $k => $photo){
            $rez['photos'][$k] = str_replace('/var/www/html/','',$rez['photos'][$k]);
        }
        $out['photos'] = $rez['photos'];
    }else{
        $out['err'] = 1;
        $out['html'] = '<h2>Ошибка</h2><pre style="text-align: left;">'.print_r($rez['deb'],1).'</pre>';
    }
    echo json_encode($out,JSON_UNESCAPED_UNICODE);
}
//Array
//(
//    [url] => http://172.17.254.160:8084/api.php
//    [prompt] => professional 3d model {prompt} . octane render, highly detailed, volumetric, dramatic lighting
//    [negative] => ugly, deformed, noisy, low poly, blurry, painting
//)