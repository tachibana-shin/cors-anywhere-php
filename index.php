<?php
  define("METHOD", $_SERVER["REQUEST_METHOD"]);
  define("URL",    substr($_SERVER["REQUEST_URI"], 1, 32));
  if (preg_match("/^https?:\\/\\//i", URL) == 0) {
    http_response_code(404);

    echo "Invalid URL: ".URL."<br>";
    echo "Please enter URL start with http or https.";

    exit();
  }

  $headers = array();
  array_push($headers, "Host: ".parse_url(URL)["host"]);
  foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0 && $key !== "HTTP_HOST") {
      $name = 
        strtolower(
          preg_replace(
            '/(?<!^)[A-Z]/',
            '-$0',
            str_replace(' ', '',
              ucwords(
                str_replace('_', ' ',
                  strtolower(
                    substr($key, 5)
                  )
                )
              )
            )
          )
        );
        
      if ($name == "cookie2") $name = "cookie";
      $headers[$name] = $value;
    }
  }
  $QUERY = array();
  foreach($_GET as $key => $value) {
    array_push($QUERY, $key."=".$value);
  }

  $request = curl_init();
  curl_setopt($request, CURLOPT_URL, URL."?".join("&", $QUERY));
  curl_setopt($request, CURLOPT_CUSTOMREQUEST, METHOD);
  curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($request, CURLOPT_ENCODING, "UTF8");
  curl_setopt($request, CURLOPT_HEADER, 1);
  curl_setopt($request, CURLOPT_HTTP_CONTENT_DECODING, 00);
  curl_setopt($request, CURLOPT_COOKIE, isset($headers["cookie"]) ? $headers["cookie"] : "");
  curl_setopt($request, CURLOPT_USERAGENT, $headers["user-agent"]);

  // curl_setopt($request, CURLOPT_POSTFIELD, http_build_query($_REQUEST));

  // curl_setopt($request, CURLOPT_POST, METHOD == "POST" ? 1 : 0);

  $response = rtrim((curl_exec($request)));
  $response = explode("\r\n\r\n", $response, 2);

  $headersText = $response[0];
  $data = $response[1];

  $headers = explode("\n",$headersText);

  array_shift($headers);

  // array.push("Host: ".$_SERVER["HTTP_HOST"]);

  foreach($headers as $part) {
      if (strpos($part, "Transfer-Encoding:") == 0) {
        continue;
      }

      //some headers will contain ":" character (Location for example), and the part after ":" will be lost, Thanks to @Emanuele
      $middle = explode(":",$part,2);

      //Supress warning message if $middle[1] does not exist, Thanks to @crayons
      if ( !isset($middle[1]) ) { $middle[1] = null; }

      header(trim($middle[0]).": ".trim($middle[1]));
  }

  echo $data;
?>
