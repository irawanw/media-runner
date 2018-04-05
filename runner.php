<?php 

include 'inc/image_process.php'; 
include 'inc/video_process.php';

$error = 1;

//Image process
$image_process = new Image_process();

$available = $image_process->get_unprocessed_media();

if($available != "null")
{
  $watermark = $image_process->get_watermark();

  $result = json_decode($available,true);
  $total = count($result['data']);

  for($i=0;$i<$total;$i++)
  {
    $begin = $image_process->get_raw_media($result['data'][$i], $result['log']);

    if($begin)
    {
      $process = $image_process->process_media($result['data'][$i]);
      if($process)
      {
        $error = 0;
      }
    }
    else
    {
      $error = 1;
    }
  }

  if($error == 0)
  {
    $finish = $image_process->reupload_media($result['log']);
    $image_process->clean_data($result['data']);
  }
}

// Video process

$video_process = new Video_process();

$available = $video_process->get_unprocessed_media();

if($available != "null")
{
  $watermark = $video_process->get_watermark();

  $result = json_decode($available,true);
  $total = count($result['data']);

  for($i=0;$i<$total;$i++)
  {

    $begin = $video_process->get_raw_media($result['data'][$i], $result['log']);

    if($begin)
    {
      $process = $video_process->process_media($result['data'][$i]);
      if($process)
      {
        $error = 0;
      }
    }
    else
    {
      $error = 1;
    }
  }

  if($error == 0)
  {
    $finish = $video_process->reupload_media($result['log']);
    $video_process->clean_data($result['data']);
  }
}

?>