<?php

ini_set('max_execution_time', 0);
ini_set('memory_limit', '1024M');

/******************************************************

  Image_process class

  Created By    : Irawan Wijanarko
  Creation Date : 03/26/2018

  Notes
  -------------------------------------------
  Image process to create thumbnail and resize 

*******************************************************/
  
class Image_process
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
    
    //process folder from original image
    $this->process_folder = $_SERVER['DOCUMENT_ROOT'] . '/process/';
    
    //watermark logo fir unpaid image
    $this->watermark_folder = $_SERVER['DOCUMENT_ROOT'] . '/watermark/';
    
    //finish folder for resize, watermark and thumbnail image
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
    Array object of available image data

  ********************************************************/
  public function get_unprocessed_media()
  {

    $apikey = '12345678'; //apikey
    $limit = 5; //limit selected row data
    $type = 'image';

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

    //get data
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
    Creation Date : 03/29/2018
    Update Date   : 04/02/2018

    Description:
    Get data raw from app server

    Parameters:
    $data : Array Object of image data

    Return:
    1 for Success
    0 for Fail

  ********************************************************/
  public function get_raw_media($data,$log_id)
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
    Creation Date : 03/31/2018
    Update Date   : 04/02/2018

    Description:
    Processing create thumbnail, watermark and resize image 

    Parameters:
    $data : Array Object of image data

    Return:
    1 for Success
    0 for Fail

  ********************************************************/
  public function process_media($data)
  {

    $file_to_send = array();
    $retval = 1;

    if($data['type'] == 'image/png')
    {
      $success = $this->_imagepng($data['paid'], $data['filename'], $data['thumbname']);
    }

    if($data['type'] == 'image/gif')
    {
      $success = $this->_imagegif($data['paid'], $data['filename'], $data['thumbname']);
    }

    if($data['type'] == 'image/jpg' OR $data['type'] == 'image/jpeg') 
    {
      $success = $this->_imagejpg($data['paid'], $data['filename'], $data['thumbname']);
    }

    
    if($data['paid'])
    {
      $file_image  = $this->finish_folder . 'resize/' . $data['filename'];
    }
    else
    {
      $file_image  = $this->finish_folder . 'watermark/' . $data['filename'];
    }

    $file_thumb  = $this->finish_folder . 'thumb/' . $data['thumbname'];
    
    $handle_image = fopen($file_image, "r");
    $data_image   = fread($handle_image, filesize($file_image));

    $handle_thumb = fopen($file_thumb, "r");
    $data_thumb   = fread($handle_thumb, filesize($file_thumb));

    $file_to_send = array(
                      'id'         => $data['id'],
                      'jump_id'    => $data['jump_id'],
                      'file_name'  => $data['filename'],
                      'file_bin'   => base64_encode($data_image),
                      'thumb_name' => $data['thumbname'],
                      'thumb_bin'  => base64_encode($data_thumb),
                      'parent'     => 'image_web',
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

    $reupload_url = 'https://app.freefallvideo.com/api/?page=put-processed-media&type=image&key='. $key .'&log_id=' . $log_id;

    $options = array(
        CURLOPT_URL            => $reupload_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POST           => true,
        CURLOPT_BINARYTRANSFER => true,
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
    Creation Date : 03/25/2018
    Update Date   : 04/02/2018

    Description:
    download original file to put in process folder

    Parameters:
    $data : Array Object of image data
    $filename : File name image
    
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

  /******************************************************

    private function _imagepng($paid, $filename, $thumbname)

    Created By    : Irawan Wijanarko
    Creation Date : 03/25/2018
    Update Date   : 04/02/2018

    Description:
    - Create resize, watermark and thumbnail of png type

    Parameters:
    $paid : paid mean no watermark
    $filename : file object for process
    $thumbname : thumbnail name

    Return:
    1 for Success
    0 for Fail

  ********************************************************/
  private function _imagepng($paid, $filename, $thumbname)
  {
    
    $retval = 1;
    $image  = imagecreatefrompng($this->process_folder . $filename);
    $width  = imagesx($image);
    $height = imagesy($image);

    $thumb_process = $this->_thumb_image($image, $width, $height);
    $resize_process = $this->_resize_image($image, $width, $height);

    imagepng($thumb_process, $this->finish_folder . 'thumb/' . $thumbname);
    imagepng($resize_process, $this->finish_folder . 'resize/' . $filename);
    imagedestroy($image);

    if($paid == 0)
    {
      $sourceImage    = imagecreatefrompng($this->finish_folder . 'resize/' . $filename);
      $watermarkImage = imagecreatefrompng($this->watermark);
      imagealphablending($watermarkImage, true);

      $sourceWidth  = imagesx($sourceImage);
      $sourceHeight = imagesy($sourceImage);

      $watermarkWidth  = imagesx($watermarkImage);
      $watermarkHeight = imagesy($watermarkImage);

      imagecopymerge(
        $sourceImage, 
        $watermarkImage, 
        ($sourceWidth-$watermarkWidth)/2, 
        ($sourceHeight-$watermarkHeight)/2, 
        0, 0,
        $watermarkWidth, $watermarkHeight,
        60
      );

      imagepng($sourceImage, $this->finish_folder . 'watermark/' . $filename);
      imagedestroy($sourceImage);
      imagedestroy($watermarkImage);
    }
    
    return ($retval);

  }

  /******************************************************

    private function _imagegif($paid, $filename, $thumbname)

    Created By    : Irawan Wijanarko
    Creation Date : 03/25/2018
    Update Date   : 04/02/2018

    Description:
    - Create resize, watermark and thumbnail of gif type

    Parameters:
    $paid : paid mean no watermark
    $filename : file object for process
    $thumbname : thumbnail name

    Return:
    1 for Success
    0 for Fail

  ********************************************************/
  private function _imagegif($paid, $filename, $thumbname)
  {

    $retval = 1;
    $image  = imagecreatefromgif($this->process_folder . $filename);
    $width  = imagesx($image);
    $height = imagesy($image);

    $thumb_process = $this->_thumb_image($image, $width, $height);
    $resize_process = $this->_resize_image($image, $width, $height);

    imagegif($thumb_process, $this->finish_folder . 'thumb/' . $thumbname);
    imagegif($resize_process, $this->finish_folder . 'resize/' . $filename);
    imagedestroy($image);

    if($paid == 0)
    {
      $sourceImage    = imagecreatefromgif($this->finish_folder . 'resize/' . $filename);
      $watermarkImage = imagecreatefrompng($this->watermark);
      imagealphablending($watermarkImage, true);

      $sourceWidth  = imagesx($sourceImage);
      $sourceHeight = imagesy($sourceImage);

      $watermarkWidth  = imagesx($watermarkImage);
      $watermarkHeight = imagesy($watermarkImage);

      imagecopymerge(
        $sourceImage, 
        $watermarkImage, 
        ($sourceWidth-$watermarkWidth)/2, 
        ($sourceHeight-$watermarkHeight)/2, 
        0, 0,
        $watermarkWidth, $watermarkHeight,
        60
      );

      imagegif($sourceImage, $this->finish_folder . 'watermark/' . $filename);
      imagedestroy($sourceImage);
      imagedestroy($watermarkImage);
    }

    return ($retval);

  }

  /******************************************************

    private function _imagejpg($paid, $filename, $thumbname)

    Created By    : Irawan Wijanarko
    Creation Date : 03/25/2018
    Update Date   : 04/02/2018

    Description:
    - Create resize, watermark and thumbnail of jpg type

    Parameters:
    $paid : paid mean no watermark
    $filename : file object for process
    $thumbname : thumbnail name

    Return:
    1 for Success
    0 for Fail

  ********************************************************/
  private function _imagejpg($paid, $filename, $thumbname)
  {

    $retval = 1;
    $image  = imagecreatefromjpeg($this->process_folder . $filename);
    $width  = imagesx($image);
    $height = imagesy($image);

    $thumb_process = $this->_thumb_image($image, $width, $height);
    $resize_process = $this->_resize_image($image, $width, $height);

    imagejpeg($thumb_process, $this->finish_folder . 'thumb/' . $thumbname);
    imagejpeg($resize_process, $this->finish_folder . 'resize/' . $filename);
    imagedestroy($image);

    if($paid == 0)
    {
      $sourceImage    = imagecreatefromjpeg($this->finish_folder . 'resize/' . $filename);
      $watermarkImage = imagecreatefrompng($this->watermark);
      imagealphablending($watermarkImage, true);

      $sourceWidth  = imagesx($sourceImage);
      $sourceHeight = imagesy($sourceImage);

      $watermarkWidth  = imagesx($watermarkImage);
      $watermarkHeight = imagesy($watermarkImage);

      imagecopymerge(
        $sourceImage, 
        $watermarkImage, 
        ($sourceWidth-$watermarkWidth)/2, 
        ($sourceHeight-$watermarkHeight)/2, 
        0, 0,
        $watermarkWidth, $watermarkHeight,
        60
      );

      imagejpeg($sourceImage, $this->finish_folder . 'watermark/' . $filename);
      imagedestroy($sourceImage);
      imagedestroy($watermarkImage);
    }

    return ($retval);

  }

  /******************************************************

    private function _thumb_image($image_resource_id,$width,$height)

    Created By    : Irawan Wijanarko
    Creation Date : 03/25/2018

    Description:
    Create thumbnail

    Parameters:
    $image_resource_id : image object
    $width : width of image properties
    $height : height of image properties

    Return:
    Image Objet

  ********************************************************/  
  private function _thumb_image($image_resource_id,$width,$height) 
  {

    $target_width = 200;
    $target_height = 200;
    $target_image = imagecreatetruecolor($target_width,$target_height);
    imagecopyresampled($target_image,$image_resource_id,0, 0, 0, 0, $target_width, $target_height, $width, $height);
    
    return ($target_image);

  }

  /******************************************************

    private function _resize_image($image_resource_id,$width,$height)

    Created By    : Irawan Wijanarko
    Creation Date : 03/25/2018

    Description:
    - Create resize

    Parameters:
    $image_resource_id : image object
    $width : width of image properties
    $height : height of image properties

    Return:
    Image Object

  ********************************************************/
  private function _resize_image($image_resource_id,$width,$height)
  {

    $target_width = 1024;
    $target_height = 1024;

    if($width > $target_width || $height > $target_height)
    {
      if ($width > $height) 
      {
        $target_height = floor(($height/$width)*$target_width);
        $target_width  = $target_width;
      } 
      else
      {
        $target_width  = floor(($width/$height)*$target_height);
        $target_height = $target_height;
      }
    }

    $target_image = imagecreatetruecolor($target_width,$target_height);
    imagecopyresampled($target_image,$image_resource_id,0, 0, 0, 0, $target_width, $target_height, $width, $height);

    return ($target_image);

  }
}

?>