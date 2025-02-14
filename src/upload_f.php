<?php
if( isset( $_POST['csv_file_upload'] ) ){
    $jobsdir = './jobs';
    $uploaddir = './uploads';
    if( ! is_dir( $uploaddir ) ) mkdir( $uploaddir, 0777 );
    if( ! is_dir( $jobsdir ) ) mkdir( $jobsdir, 0777 );
    $files      = $_FILES;
    $done_files = array();
    $data=array();
    $data['kol']=0;
    $data['suc']=0;
    $data['err']=0;
    foreach( $files as $file ){
        $file_name = $file['name'];
        if( move_uploaded_file( $file['tmp_name'], "$uploaddir/$file_name" ) ){
            $done_files[] = realpath( "$uploaddir/$file_name" );
            $data['suc']++;
        }else{
            $data['err']++;
        }
        $data['kol']++;
    }
    foreach ($done_files as $k => $file){//Обработаем файл
        $filenamej = pathinfo($file, PATHINFO_FILENAME);
        $csvData = getDataFromCSV($file);
        $datas = array();
        if(isset($_POST['start'])) $datas['run'] = (int)$_POST['start'];
        $datas['status'] = 0;
        $datas['prompts'] = array();
        $heads = array('sd_model_checkpoint','sampler_name','cfg_scale','steps','batch_size','seed','width','height','width_itog','height_itog','dir_name');
        foreach($csvData as $k => $row){
            if($k==0){//получим заголовки
                $heads = $row;
            }
            if($k==1){//получим данные
                foreach($row as $k1 => $col) {
                    $datas[trim($heads[$k1])]=trim($col);
                }
            }
            if($k>1){//Получим промпты
                $datas['prompts'][] = array('prompt_suc'=>$row[0],'prompt_neg'=>$row[1]);
            }
        }
        file_put_contents($jobsdir.'/'.$filenamej.'.json',json_encode($datas,JSON_UNESCAPED_UNICODE));
        unlink($file);
    }
    die( json_encode( $data ) );
}

function getDataFromCSV($file, $delimiter = ';', $enclosure = '"') {
    $data = array();
    if (($handle = fopen($file, "r")) !== FALSE) {
        while (($row = fgetcsv($handle, 0, $delimiter, $enclosure)) !== FALSE) {
            $data[] = $row;
        }
        fclose($handle);
    }
    return $data;
}