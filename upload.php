<?php

    $uploaddir = '/var/www/dorothea/uploads/';                                                      # UPLOAD : upload dir
    $my_name = "Dorothea";                                                                          # EMAIL  : name
    $my_mail = "dorothea@mow.vlaanderen.be";                                                        # EMAIL  : sender email address

    $idgenerator = date("dmY-His") .'-DORO' . rand(0, 10000);  # FILE UPLOAD: expression to generate unique filenames

    $geoloket_put_url = 'http://10.132.32.231/geoloket/rest/configreader/loketten/set/test/test';   # GEOLOKET PUSH: path to configreader endpoint
    $ext = "." . substr($_FILES["userfile"]["name"], strrpos($_FILES["userfile"]["name"], '.')+1);

    $uploadfile = $uploaddir .$idgenerator . $ext;                                                  # Destination file on server

    function mail_attachment($filename, $path, $mailto, $from_mail, $from_name, $replyto, $subject, $message) {
        $file = $path.$filename;
        $file_size = filesize($file);
        $handle = fopen($file, "r");
        $content = fread($handle, $file_size);
        fclose($handle);
        $content = chunk_split(base64_encode($content));
        $uid = md5(uniqid(time()));
        $name = basename($file);
        $header = "From: ".$from_name." <".$from_mail.">\r\n";
        $header .= "Reply-To: ".$replyto."\r\n";
        $header .= "MIME-Version: 1.0\r\n";
        $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
        $header .= "This is a multi-part message in MIME format.\r\n";
        $header .= "--".$uid."\r\n";
        $header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
        $header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $header .= $message."\r\n\r\n";
        $header .= "--".$uid."\r\n";
        $header .= "Content-Type: application/octet-stream; name=\"".$filename."\"\r\n"; // use different content types here
        $header .= "Content-Transfer-Encoding: base64\r\n";
        $header .= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
        $header .= $content."\r\n\r\n";
        $header .= "--".$uid."--";
        if (mail($mailto, $subject, "", $header)) {
            echo "MAIL: SUCCESS </br>";
        } else {
            echo "MAIL: ERROR </br>";
        }
    }

    # upload file
    if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
        echo "FILE UPLOAD: SUCCESS </br>";
    } else {
        echo "FILE UPLOAD: ERROR </br>";
    }

    # mail survey
    if (isset($_POST['checkb_mail'])) {

        # generate KML
        $kmlfile = $uploaddir . $idgenerator . ".kml";
        $output =  '<?xml version="1.0" encoding="UTF-8"?>' .PHP_EOL;
        $output .= '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">' .PHP_EOL;
        $output .= '<Document>' .PHP_EOL;
        $output .= '<name>Testlabel.kml</name>' .PHP_EOL;
        $output .= '<Placemark>' .PHP_EOL;
        $output .= '<name>Testlabel</name>' .PHP_EOL;
        $output .= '<description><![CDATA[' . "Verslag van opmeting gedaan op de " . $_POST["ident8"] . ", referentiepunt " . $_POST["refpt"] . '.' . $_POST["usertext"] . '<a href="http://10.132.32.231/dorothea/uploads/' . basename(basename($uploadfile)) . '">Klik hier voor de afbeelding bij deze survey</a>]]></description>' .PHP_EOL;
        $output .= '<LookAt><longitude>' . $_POST["wgs84lon"] . '</longitude><latitude>' . $_POST["wgs84lat"] . '</latitude><altitude>0</altitude><heading>1.121395505621607</heading><tilt>0</tilt><range>1050.267731980781</range><gx:altitudeMode>relativeToSeaFloor</gx:altitudeMode></LookAt>' .PHP_EOL;
        $output .= '<Point><coordinates>' . $_POST["wgs84lon"] . "," . $_POST["wgs84lat"] . ',0</coordinates></Point>' .PHP_EOL;
        $output .= '</Placemark>' .PHP_EOL;
        $output .= '</Document>' .PHP_EOL;
        $output .= '</kml>' .PHP_EOL;

        file_put_contents($kmlfile, $output);

        # compose message
        $my_file = basename($kmlfile);
        $my_path = $uploaddir;
        $my_replyto = "marc.vandael@mow.vlaanderen.be";
        $my_subject = "Dorothea Mailservice";
        $my_message  = 'SURVEY ID: ' . $idgenerator .               "\r\n";
        $my_message .= '########################################'.  "\r\n";
        $my_message .= 'IDENT8: ' . $_POST["ident8"].               "\r\n";
        $my_message .= 'REFPT : ' . $_POST["refpt"].                "\r\n";
        $my_message .= '########################################'.  "\r\n";
        $my_message .= 'SURVEY: ' . $_POST["usertext"].             "\r\n";
        $my_message .= '########################################'.  "\r\n";
        $my_message .= "GPS: " . $_POST["wgs84"] .                  "\r\n";
        $my_message .= "Lambert72: " . $_POST["lambert72"] .        "\r\n";
        $my_message .= '########################################'.  "\r\n";
        $my_message .= 'IMAGE : ' .'http://10.132.32.231/dorothea/uploads/' . basename($uploadfile).  "\r\n";
        $my_message .= 'KML : ' .  'http://10.132.32.231/dorothea/uploads/' . basename($kmlfile). "\r\n";

        mail_attachment($my_file, $my_path, "marc.vandael@mow.vlaanderen.be", $my_mail, $my_name, $my_replyto, $my_subject, $my_message);
    }

    #  upload to geoloket personal config
    if (isset($_POST['checkb_geoloket'])) {

        #obviously, we're trying to push some dummy data for now
//        $data = array (
//            Annotaties =>
//            array (
//                x => "51.2515",
//                y => "5.123",
//                imgurl => "www.test.be/test/test",
//                text => "testbeschrijving"
//            ),
//        );


        $data = (array("ConfigServices_error_mail_to_address" => "dorothea_mobile@mow.vlaanderen.be"));
        $data_string = json_encode($data);

        echo $data_string;


        $ch = curl_init($geoloket_put_url);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );

        $verbose = fopen('php://temp', 'rw+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);


        $result = curl_exec($ch);
        curl_close($ch);

        !rewind($verbose);
        $verboseLog = stream_get_contents($verbose);

        echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";





    }
?>
