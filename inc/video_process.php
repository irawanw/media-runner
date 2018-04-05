<?php

ini_set('max_execution_time', 0);
ini_set('memory_limit', '1024M');

/******************************************************

  Video_process class

  Created By    : Irawan Wijanarko
  Creation Date : 03/26/2018

  Notes
  -------------------------------------------
  Video process to create thumbnail and resize 

*******************************************************/

class Video_process
{

  /******************************************************

    function __construct()

    Created By    : Irawan Wijanarko
    Creation Date : 03/26/2018

    Notes
    -------------------------------------------
    Set folder for processing

  *******************************************************/
  function __construct()
  {

    //process folder from original video
    $this->process_folder = $_SERVER['DOCUMENT_ROOT'] . '/process/';
    
    //watermark logo fir unpaid video
    $this->watermark_folder = $_SERVER['DOCUMENT_ROOT'] . '/watermark/';
    
    //finish folder for resize, watermark and thumbnail video
    $this->finish_folder = $_SERVER['DOCUMENT_ROOT'] . '/done/';

  }

  /******************************************************

    public function get_unprocessed_media()

    Created By    : Irawan Wijanarko
    Creation Date : 03/26/2018

    Description:
    Get unprocessed media from database

    Parameters:
    None

    Return:
    Array object of available video data

  ********************************************************/
  public function get_unprocessed_media()
  {

    $apikey = '12345678'; //apikey
    $limit = 5; //limit selected row data
    $type = 'video';

    $url = 'https://app.freefallvideo.com/api/?page=get-unprocessed-media&key=' . $apikey . '&type=' . $type . '&limit=' . $limit;

    $options = array(
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_HEADER         => false,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_ENCODING       => "",
              CURLOPT_USERAGENT      => "spider",
              CURLOPT_AUTOREFERER    => true,
              CURLOPT_CONNECTTIMEOUT => 120,
              CURLOPT_TIMEOUT        => 120,
              CURLOPT_MAXREDIRS      => 10,
              CURLOPT_SSL_VERIFYPEER => false
    );

    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    $available = curl_exec($ch);
    curl_close($ch);

    return ($available);

  }

  /******************************************************

    public function get_watermark()

    Created By    : Irawan Wijanarko
    Creation Date : 03/23/2018
    Update Date   : 04/02/2018

    Description:
    Get and set watermark logo image or text

    Parameters:
    None

    Return:
    None

  ********************************************************/
  public function get_watermark()
  {

    $watermark_url = 'https://app.freefallvideo.com/api/?page=get-watermark-media';

    $options = array(
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_HEADER         => false,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_ENCODING       => "",
              CURLOPT_USERAGENT      => "spider",
              CURLOPT_AUTOREFERER    => true,
              CURLOPT_CONNECTTIMEOUT => 120,
              CURLOPT_TIMEOUT        => 120,
              CURLOPT_MAXREDIRS      => 10,
              CURLOPT_SSL_VERIFYPEER => false
    );

    $ch = curl_init($watermark_url);
    curl_setopt_array($ch, $options);
    $watermark = curl_exec($ch);
    curl_close($ch);

    if($watermark == '')
    {
      $this->watermark = $this->watermark_folder . 'watermark.png';
    }
    else
    {

    }

  }

  /******************************************************

    public function get_raw_media($data)

    Created By    : Irawan Wijanarko
    Creation Date : 03/26/2018
    Update Date   : 04/02/2018

    Description:
    Get data raw from app server

    Parameters:
    $data : Array Object of video data

    Return:
    1 for Success
    0 for Fail

  ********************************************************/
  public function get_raw_media($data)
  {

    $apikey = '12345678'; //apikey

    $raw_url = 'https://app.freefallvideo.com/api/?page=get-raw-media&key=' . $apikey . '&log_id=' . $log_id;

    $post_data = array(
                      'id'       => $data['id'],
                      'filename' => $data['filename']
    );

    $options = array(
              CURLOPT_URL            => $raw_url,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_CONNECTTIMEOUT => 120,
              CURLOPT_TIMEOUT        => 120,
              CURLOPT_SSL_VERIFYPEER => false,
              CURLOPT_POST           => true,
              CURLOPT_BINARYTRANSFER => true,
              CURLOPT_POSTFIELDS     => $post_data,
    );

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $raw = curl_exec($ch);
    curl_close($ch);

    $process = json_decode($raw, true); 

    if($process['data'] != '')
    {
      $put_to_process = $this->_get_original_file($process['data'], $data['filename']);
      
      if($put_to_process)
      {
        $retval = 1;  
      }
      else
      {
        $retval = 0;
      }
    }
    else
    {
      $retval = 0;
    }
    
    return ($retval);

  }

  /******************************************************

    public function process_media($data)

    Created By    : Irawan Wijanarko
    Creation Date : 03/26/2018
    Update Date   : 04/02/2018

    Description:
    Processing create thumbnail, watermark and resize video 

    Parameters:
    $data : Array Object of video data

    Return:
    1 for Success
    0 for Fail

  ********************************************************/
  public function process_media($data)
  {

    $file_to_send = array();
    $retval = 1;

    $video_source    = $this->process_folder . $data['filename'];
    $video_resize    = $this->finish_folder . 'resize/' . $data['filename'];
    $video_watermark = $this->finish_folder . 'watermark/' . $data['filename'];
    $video_thumbnail = $this->finish_folder . 'thumb/' . $data['thumbname'];

    exec('ffmpeg -ss 5 -i '. $video_source .' -y -vf scale=200x200 -frames:v 1 -q:v 4 ' . $video_thumbnail); //create thumbnail

    if($data['paid'] == 0) //no paid then create watermark first
    {
      exec('ffmpeg -i '. $video_source . ' -y  -c:a copy -s hd720 ' . $video_resize); //resize 720

      exec('ffmpeg -i ' . $video_resize . ' -i ' . $this->watermark . ' -filter_complex "overlay=(main_w-overlay_w)/2:(main_h-overlay_h)/2" -y -codec:a copy ' . $video_watermark);
      // exec('ffmpeg -i ' . $video_resize . ' -y -vf "drawtext=text=Freefallvideo.com: fontfile=/usr/share/fonts/truetype/freefont/FreeSerif.ttf: fontsize=50: fontcolor=white: x=100: y=50" -strict -2 ' . $video_watermark);  
    }
    else
    {
      exec('ffmpeg -i '. $video_source . ' -y  -c:a copy -s hd1080 ' . $video_resize); //resize 1080 get from process folder then resize it
    }

    if($data['paid'])
    {
      $file_video  = $this->finish_folder . 'resize/' . $data['filename'];
    }
    else
    {
      $file_video  = $this->finish_folder . 'watermark/' . $data['filename'];
    }

    $handle_video = fopen($file_video, "r");
    $data_video   = fread($handle_video, filesize($file_video));

    $file_thumb  = $this->finish_folder . 'thumb/' . $data['thumbname'];
    
    $handle_thumb = fopen($file_thumb, "r");
    $data_thumb   = fread($handle_thumb, filesize($file_thumb));

    $file_to_send = array(
                  'id'         => $data['id'],
                  'jump_id'    => $data['jump_id'],
                  'file_name'  => $data['filename'],
                  'file_bin'   => base64_encode($data_video),
                  'thumb_name' => $data['thumbname'],
                  'thumb_bin'  => base64_encode($data_thumb),
                  'parent'     => 'video_web',
    );
    
    $this->post_data[] = json_encode($file_to_send);

    return ($retval);

  }

  /******************************************************

    public function reupload_media($log_id)

    Created By    : Irawan Wijanarko
    Creation Date : 03/25/2018
    Update Date   : 04/02/2018

    Description:
    - Reupload to app server to save into folder 

    Parameters:
    $log_id : log id of runner process table

    Return:
    None

  ********************************************************/
  public function reupload_media($log_id)
  {

    $key = '12345678';

    $reupload_url = 'https://app.freefallvideo.com/api/?page=put-processed-media&type=video&key='. $key .'&log_id=' . $log_id;

    $options = array(
        CURLOPT_URL            => $reupload_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POST           => true,
        CURLOPT_BINARYTRANSFER => 1,
        CURLOPT_POSTFIELDS     => $this->post_data,
    );

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    curl_close($ch);

    echo $response;

  }

  /******************************************************

    private function clean_data($data)

    Created By    : Irawan Wijanarko
    Creation Date : 04/02/2018

    Description:
    - Delete image file in each folder

    Parameters:
    $data : array object of processed file(s)

    Return:
    None

  ********************************************************/
  public function clean_data($data)
  {

    foreach($data as $key => $file)
    {
      unlink($this->process_folder . $data[$key]['filename']);
      unlink($this->finish_folder . 'resize/' . $data[$key]['filename']);
      unlink($this->finish_folder . 'thumb/' .$data[$key]['thumbname']);
      unlink($this->finish_folder . 'watermark/' . $data[$key]['filename']);
    }

  }

  /******************************************************

    public function _get_original_file($data, $filename)

    Created By    : Irawan Wijanarko
    Creation Date : 03/26/2018

    Description:
    download original file to put in process folder

    Parameters:
    $data : Array Object of video data
    $filename : File name video
    
    Return:
    1 for Success put into folder
    0 for Fail

  ********************************************************/
   private function _get_original_file($data, $filename)
  {

    $media_url = $data;
    $save_to   = $this->process_folder . $filename;

    $options = array(
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_BINARYTRANSFER => true,
              CURLOPT_HEADER         => false,
              CURLOPT_USERAGENT      => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322',
              CURLOPT_SSL_VERIFYPEER => false,
              CURLOPT_FOLLOWLOCATION => true
    );

    $ch = curl_init(str_replace(" ","%20",$media_url));
    curl_setopt_array($ch, $options);
    $raw = curl_exec($ch);
    curl_close($ch);

    if(file_exists($save_to))
    {
        unlink($save_to);
    }

    if($handle = fopen($save_to,'w+'))
    {
      if (fwrite($handle, $raw) === false)
      {
        $retval = 0;
      }
      else
      {
        fclose($handle);
        $retval = 1;
      }
    }
    else
    {
      $retval = 0;
    }

    return($retval);

  }
}

?>