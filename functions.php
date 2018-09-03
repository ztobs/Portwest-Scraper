<?php


include('lib/simple_html_dom.php');





function toFile($data, $output_filename, $type="append")
{
	if($type=="append") $myfile = fopen($output_filename, "a") or die("Unable to open file! $output_filename");
	if($type=="replace") $myfile = fopen($output_filename, "w") or die("Unable to open file! $output_filename");
	fwrite($myfile, $data);
	fclose($myfile);
}




function getWeight($pdom)
{
  $weight = 1000;
  foreach($pdom->find("#product-attribute-specs-table tr") as $jdom)
  {
    if(strpos($jdom->plaintext, "Shell Weight (grams)") !== FALSE)
    {
      $weight = trim(str_replace("Shell Weight (grams)", "", $jdom->plaintext));
    }
  }
  return $weight/1000;
}




function getDom2($endpoint, $method="GET", $headers=[], $body="", $content_type="html", $return_type="")
  {
    global $cookie;
    $curl = curl_init();
    if($content_type == 'form') $headers = array_merge($headers, array('Content-Type:application/x-www-form-urlencoded; charset=UTF-8'));
    if($content_type == 'json') $headers = array_merge($headers, array('Content-Type:application/json; charset=UTF-8'));
    if($content_type == 'html') $headers = array_merge($headers, array('Content-Type:text/html; charset=UTF-8'));
    $options = array(
      CURLOPT_URL => $endpoint,
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_FOLLOWLOCATION => 1,
      CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
      CURLOPT_HEADER => 0,
      CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36",
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_POSTFIELDS => $body, 
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_COOKIE  =>  $cookie
    );
    curl_setopt_array($curl, $options);
    

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $status = curl_getinfo($curl)['http_code'];

    curl_close($curl);

    if($return_type == 'json') return array('status'=>$status, 'response'=>json_decode($response, true), 'error'=>$err);
    else return array('status'=>$status, 'response'=>$response, 'error'=>$err);
  }


  function getDom($endpoint, $method="GET", $headers=[], $body="", $content_type="html", $return_type="")
  {   
      $dom = array();
      while(true)
      {
          $dom = getDom2($endpoint, $method, $headers, $body, $content_type);
          // var_dump($dom);
          if(($dom['status'] != "200") || ($dom['response'] == null))
          {
              echo "(".$dom['status'].") retrying...\r\n";
          }
          else break;

      }
      return str_get_html($dom['response']);
  }



function sendEmail($mailto, $from, $fromName, $subject, $message, $file)
{
    $filename = "leads.txt";
    $content = file_get_contents($file);
    $content = chunk_split(base64_encode($content));

    // a random hash will be necessary to send mixed content
    $separator = md5(time());

    // carriage return type (RFC)
    $eol = "\r\n";

    // main header (multipart mandatory)
    $headers = "From: $fromName <$from>" . $eol;
    $headers .= "MIME-Version: 1.0" . $eol;
    $headers .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"" . $eol;
    $headers .= "Content-Transfer-Encoding: 7bit" . $eol;
    $headers .= "This is a MIME encoded message." . $eol;

    // message
    $body = "--" . $separator . $eol;
    $body .= "Content-Type: text/plain; charset=\"iso-8859-1\"" . $eol;
    $body .= "Content-Transfer-Encoding: 8bit" . $eol;
    $body .= $message . $eol;

    // attachment
    $body .= "--" . $separator . $eol;
    $body .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"" . $eol;
    $body .= "Content-Transfer-Encoding: base64" . $eol;
    $body .= "Content-Disposition: attachment" . $eol;
    $body .= $content . $eol;
    $body .= "--" . $separator . "--";

    //SEND Mail
    if (mail($mailto, $subject, $body, $headers)) return true;
}