<?php

/******************************************************

  Log_process class

  Created By    : Irawan Wijanarko
  Creation Date : 03/26/2018

  Notes
  -------------------------------------------
  Image process to create thumbnail and resize 

*******************************************************/
  
class Log_process
{

  /******************************************************

    function __construct()

    Created By    : Irawan Wijanarko
    Creation Date : 03/26/2018

    Notes
    -------------------------------------------
    Nothing to do in __construct

  *******************************************************/
  function __construct()
  {

  }

  /******************************************************

    public function get_unprocessed_media()

    Created By    : Irawan Wijanarko
    Creation Date : 03/23/2018

    Description:
    Get unprocessed media from database

    Parameters:
    None

    Return:
    Array object of available image data

  ********************************************************/
  public function get_unprocessed_media()
  {

    //first process get unprocessed media from database

    $apikey = '12345678'; //apikey
    $limit = 5; //limit selected row data
    $type = 'image';

    $url = 'https://app.freefallvideo.com/api/?page=set-log-media&key=' . $apikey;

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
}

?>