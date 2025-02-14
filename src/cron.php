<?php
error_reporting(E_ALL);
$step = '';
$des = '';
$id = 0;
if(isset($argv)&&count($argv)>1){
    $step = $argv[1];
    if(isset($argv[2])) $des = $argv[2];
    if(isset($argv[3])) $id = $argv[3];
}else{
    if(isset($_GET['step'])) $step=$_GET['step'];
    if(isset($_GET['des'])) $des=$_GET['des'];
    if(isset($_GET['id'])) $id=$_GET['id'];
}

if($step=='jobs_prompt'){
    echo date("Y-m-d H:i:s").PHP_EOL;
    include_once 'jobs.php';
    $jobs = new Jobs();
    $jobs->jobs_cron();
}