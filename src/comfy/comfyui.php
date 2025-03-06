<?php
//https://github.com/comfyanonymous/ComfyUI/blob/master/script_examples/websockets_api_example.py
//https://readmedium.com/comfyui-using-the-api-261293aa055a
//1. Сохранить workflow как file API (включить DEV мод)
//2. Подправить JSON файл
//3. Отправить req = request.Request("http://127.0.0.1:8188/prompt", data=data)
$url_api = 'http://172.17.254.160:8291';
$client_id = 'api_01';

$step=''; if(isset($_GET['step'])) $step = $_GET['step'];
if($step == 'job_create') {//Создание задачи
    $file_json = 'jsons/gen_flux_01.json';
    $prompt_array = json_decode(file_get_contents($file_json), 1);
//$prompt_array[15]['inputs']['filename_prefix'] = 'one/ComfyUI';
    $prompt = 'Sexy Woman in a formal suit with frills, a transparent shirt and see-through pants against the background of a school board';
    $md5_prompt = substr(md5($prompt), 0, 16);
    $params['json'] = $prompt_array;
    $params['field'] = 'CLIPTextEncode';
    $params['value'] = $prompt;
    $params['json'] = prompt_json_set($params);
    $params['field'] = 'SaveImage';
    $params['value'] = 'one/' . $md5_prompt;
    $params['json'] = prompt_json_set($params);
    $prompt_array = $params['json'];
    $post = array('prompt' => $prompt_array, 'client_id' => $client_id);
    $rez = cCurl($post);
//echo '<pre>'.print_r($post,1).'</pre>';
//echo '<pre>'.print_r($rez,1).'</pre>';
    if ($rez['info']['http_code'] == 200) {
        $file_job = 'jobs/' . $md5_prompt . '.json';
        if (file_exists($file_job)) $job_json = json_decode(file_get_contents($file_job), 1);
        else $job_json = array();
        $job_json['items'][] = json_decode($rez['out'], 1);
        file_put_contents($file_job, json_encode($job_json, JSON_UNESCAPED_UNICODE));
    }
}
if($step == 'job_history'){//Проверяем историю и свои задачи сохраняем себе
    $post = array(); $get=0; $iss=0;
    $rez = cCurl($post,'/api/history?max_items=64');
    $out = json_decode($rez['out'],1);
    foreach($out as $id => $v){
        if($v['prompt'][3]['client_id'] == $client_id){//наш промпт
            if($v['status']['completed']==1) {
                $cur['id'] = $id;
                $cur['num'] = $v['prompt'][4][0];
                $cur['image'] = $v['outputs'][$cur['num']]['images'][0]; //filename, subfolder, type
                $dir_local = 'upload/'.$cur['image']['subfolder'];
                if(!file_exists($dir_local)) mkdir($dir_local,0777);
                $file_local = $dir_local.'/'.$cur['image']['filename'];
                if(!file_exists($file_local)) { //закачаем файл себе
                    $url = $url_api . '/api/view?filename=' . $cur['image']['filename'] . '&type=' . $cur['image']['type'] . '&subfolder=' . $cur['image']['subfolder'];
                    $img = copy($url,$file_local);
                    $get++;
                }else{
                    $iss++;
                }
            }
        }
    }
    echo 'Получили '.$get.' фоток, уже были '.$iss.' фоток.';
    //echo '<pre>'.print_r($out,1).'</pre>';
    //echo '<pre>'.print_r($rez,1).'</pre>';
    //$out=Array(
    //    [e5aa56d4-9930-4c99-8f8d-ec7f1dd36041] => Array(
    //            [prompt] => Array(
    //                    [0] => 0
    //                    [1] => e5aa56d4-9930-4c99-8f8d-ec7f1dd36041
    //                    [2] => Array( файл json API )
    //                    [3] => Array( [client_id] => api_01 )
    //                    [4] => Array( [0] => 15 )
    //                )
    //            [outputs] => Array( [15] => Array( [images] => Array( [0] => Array(
    //                [filename] => 73e588e0c4316c30_00001_.png
    //                [subfolder] => one
    //                [type] => output
    //            ))))    //
    //            [status] => Array( [status_str] => success [completed] => 1
    //                    [messages] => Array( [0] => Array ( [0] => execution_start ... ) )
    //                )
    //            [meta] => Array(
    //                    [15] => Array(
    //                            [node_id] => 15
    //                            [display_node] => 15
    //                            [parent_node] =>
    //                            [real_node_id] => 15
    //                        )
    //                )
    //        )
}
if($step == 'job_check'){
    $post = array();
    $rez = cCurl($post,'/api/queue');
    $out = json_decode($rez['out'],1);
    echo '<pre>'.print_r($out,1).'</pre>';
    echo '<pre>'.print_r($rez,1).'</pre>';

    //$out=Array(
    //    [queue_running] => Array( выполняются
    //            [0] => Array(
    //                    [0] => 2
    //                    [1] => 03b5c2fc-8bc2-482f-bed0-d45af918c4b8
    //                    [2] => Array( тут json который слали )
    //                    [3] => Array( [client_id] => api_01 )
    //                    [4] => Array( [0] => 15 )
    //                )
    //        )
    //    [queue_pending] => Array() в очереди
    //)

}

//http://172.17.254.160:8291/api/view?filename=jobs_02_00002_.png&type=output&subfolder=one
//http://172.17.254.160:8291/api/view?filename=jobs_02&type=output&subfolder=one
//http://172.17.254.160:8291/api/view?filename=ComfyUI_00001_.png&type=output&subfolder=one



//http://172.17.254.160:8291/ws?clientId=api_01
//No WebSocket UPGRADE hdr: None
// Can "Upgrade" only to "WebSocket".
//http://172.17.254.160:8291/api/queue
//http://172.17.254.160:8291/api/history?max_items=64 - последние задачи просмотреть
//http://172.17.254.160:8291/api/view?filename=ComfyUI_00029_.png&type=output&subfolder= - получить фото

/**
 * Установка значений в промпте
 * @param $params array
 * <br>
 * <br>
 * json - массив структуры файла промпта
 * field - CLIPTextEncode|SaveImage|EmptySD3LatentImage|KSamplerSelect искомое поле для замены, имена как в class_type
 * value - значение, которое установим
 * @return array
 */
function prompt_json_set($params){
    $json = $params['json'];
    $field = $params['field'];
    $value = $params['value'];
    foreach($json as $k => $v){
        //Укажем текст промпта
        if($field=='CLIPTextEncode'&&$v['class_type']=='CLIPTextEncode'&&isset($v['inputs']['text'])){
            $json[$k]['inputs']['text'] = $value;
        }
        if($field=='SaveImage'&&$v['class_type']=='SaveImage'&&isset($v['inputs']['filename_prefix'])){
            $json[$k]['inputs']['filename_prefix'] = $value;
        }
        if($field=='KSamplerSelect'&&$v['class_type']=='KSamplerSelect'&&isset($v['inputs']['sampler_name'])){
            $json[$k]['inputs']['sampler_name'] = $value;
        }
        if($field=='EmptySD3LatentImage'&&$v['class_type']=='EmptySD3LatentImage'&&isset($v['inputs']['width'])){
            $tmp = explode('|',$value);
            $json[$k]['inputs']['width'] = $tmp[0];
            $json[$k]['inputs']['height'] = $tmp[1];
        }
        if($field=='BasicScheduler'&&$v['class_type']=='BasicScheduler'&&isset($v['inputs']['scheduler'])){
            $tmp = explode('|',$value);
            $json[$k]['inputs']['scheduler'] = $tmp[0];
            $json[$k]['inputs']['steps'] = $tmp[1];
            $json[$k]['inputs']['denoise'] = $tmp[2];
        }
    }
    return $json;
}

function cCurl($post,$url='/prompt'){
    GLOBAL $url_api;
    $out=array();
    if( $curl = curl_init() ) {
        curl_setopt($curl, CURLOPT_URL, $url_api.$url);
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