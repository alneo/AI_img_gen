<?php

class JOBS{
    private $DIR,$API,$DIRphoto;
    function __construct(){
        $this->DIR = '/var/www/html/jobs/';
        $this->DIRphoto = '/var/www/html/photos/';
        $this->FILEproc = '/var/www/html/proc.txt';
        $this->API['url'] = 'http://172.17.254.160:7860';
    }
    function jobs_list(){
        $out = array();
        $files = scandir($this->DIR);
        foreach($files as $k => $file){
            if($file!='.' && $file!='..'){
                $item_cur = $this->DIR.$file;
                if(is_file($item_cur)) {
                    $tmp = $this->jobfile_parse($item_cur);
                    $tmp['file'] = $item_cur;
                    $out[] = $tmp;
                }
            }
        }

        return $out;
    }
    function jobfile_parse($file){
        return json_decode(file_get_contents($file),1);
    }

    function jobs_cron(){
        if(file_exists($this->FILEproc)){
            echo 'В работе'.PHP_EOL;
            exit();
        }
        $jobs_lists = $this->jobs_list();
        if(count($jobs_lists)){
            foreach($jobs_lists as $job){
                echo 'job:run='.$job['run'].'; status='.$job['status'].';'.PHP_EOL;
                if($job['run']&&$job['status']==0){
                    echo 'job:count='.count($job['prompts']).';'.PHP_EOL;
                    $this->job_run($job);
                    exit();
                }
            }
        }
    }
    function job_run($job_data){
        file_put_contents($this->FILEproc,time());
        $data=array();
        $data['model']          = $job_data['sd_model_checkpoint'];
        $data['sampler_name']   = $job_data['sampler_name'];
        $data['photo_count']    = $job_data['batch_size'];
        $data['cfg_scale']      = $job_data['cfg_scale'];
        $data['seed']           = $job_data['seed'];
        $data['width']          = $job_data['width'];
        $data['height']         = $job_data['height'];
        $data['steps']          = $job_data['steps'];
        $data['path_dir']       = $job_data['dir_name'];
        $err = 0;
        foreach($job_data['prompts'] as $k => $v){
            $data['prompt_suc']     = $v['prompt_suc'];
            $data['prompt_neg']     = $v['prompt_neg'];
            $rez = $this->generate_photo($data);
            if($rez['err']) $err++;
        }
        $job_data['status']=1;
        $job_data['err']=$err;//кол-во ошибок
        file_put_contents($job_data['file'],json_encode($job_data,JSON_UNESCAPED_UNICODE));
        unlink($this->FILEproc);
    }

    function json_example(){
        $json = '{
        "run":1,
        "status":0,
        "prompts":[
            {
                "prompt_suc":"Vibrant salad bowl filled with leafy greens, tomatoes, cucumbers, onions, and a tangy vinaigrette dressing, placed on a picnic blanket outdoors, natural lighting, High resolution",
                "prompt_neg":"ugly, deformed, noisy, low poly, blurry, painting"
            },
            {
                "prompt_suc":"analog film photo. faded film, desaturated, 35mm photo, grainy, vignette, vintage, Kodachrome, Lomography, stained, highly detailed, found footage",
                "prompt_neg":"painting, drawing, illustration, glitch, deformed, mutated, cross-eyed, ugly, disfigured"
            }
        ],
        "sd_model_checkpoint":"epicrealism_pureEvolutionV3",
        "sampler_name":"Euler a",
        "cfg_scale":"7",
        "steps":"20",
        "batch_size":"4",
        "seed":"-1",
        "width":"512",
        "height":"512",
        "width_itog":"2048",
        "height_itog":"2048",
        "dir_name":"food_01"
        }';
        return $json;
    }

    function mod_get($mod){
        $out=array();
//        //FULL
//        $out['ADetailer'] = json_decode('{
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

        //https://github.com/Bing-su/adetailer/wiki/REST-API
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
        $out =array();
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
            'sampler_name'      => $data['sampler_name'],
            'restore_faces'     => true,
            'alwayson_scripts'  => $this->mod_get('adetailer'),
            'override_settings' => array(
                'sd_model_checkpoint'       => $data['model'],
                'CLIP_stop_at_last_layers'  => 2,
            )
        );
        $rez = $this->cCurl($post,'/sdapi/v1/txt2img');
        if($rez['info']['http_code']==200){
            $out1 = json_decode($rez['out'],1);
            if(isset($out1['error'])&&$out1['error']!=''){
                $out['err'] = 1;
                $out['deb']=json_decode($out1,1);
            }else {
                $out1['info'] = json_decode($out1['info'], 1);
                $out1['photo_dir'] = $data['path_dir'];
                $out = $this->parse_answer($out1);
                $out['err'] = 0;
            }
        }else{
            $out['err']=1;
            $out['deb']=json_decode($rez['out'],1);
        }
        return $out;
    }

    function parse_answer($answer){
        $out = array();
        $dir = $this->DIRphoto;
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
        $out=array();
        if( $curl = curl_init() ) {
            curl_setopt($curl, CURLOPT_URL, $this->API['url'].$url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
            curl_setopt($curl, CURLOPT_HEADER, 1);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post));
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

}