<?php

    $uploaddir = '/var/www/dorothea/uploads/';     # UPLOAD : upload dir
    $my_name = "Dorothea";                         # EMAIL  : name
    $my_mail = "dorothea@mow.vlaanderen.be";       # EMAIL  : sender email address
    $geoloket_put_url = 'http://10.132.32.231/geoloket/rest/configreader/loketten/set/test/test';


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


    $uploadfile = $uploaddir . basename($_FILES['userfile']['name']);



    # upload file
    if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
        echo "FILE UPLOAD: SUCCESS </br>";
    } else {
        echo "FILE UPLOAD: ERROR </br>";
    }

    # mail survey
    if (isset($_POST['checkb_mail'])) {
        $my_file = basename($_FILES['userfile']['name']);
        $my_path = $uploaddir;
        $my_replyto = "marc.vandael@mow.vlaanderen.be";
        $my_subject = "Dorothea Mailservice";
        $my_message  = "Verslag van opmeting gedaan op de " . $_POST["ident8"] . ", referentiepunt " . $_POST["refpt"];
        $my_message .= "\r\n\r\n\r\n";
        $my_message .= $_POST["usertext"] . "\r\n";
        $my_message .= "GPS: " . $_POST["wgs84"] . "\r\n";
        $my_message .= "Lambert72: " . $_POST["lambert72"] . "\r\n";;
        mail_attachment($my_file, $my_path, "marc.vandael@mow.vlaanderen.be", $my_mail, $my_name, $my_replyto, $my_subject, $my_message);
    }

    #  upload to geoloket personal config
    if (isset($_POST['checkb_geoloket'])) {
        echo "GEOLOKET: SUCCESS </br>";


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


        $data = (array("ConfigServices_error_mail_to_address" => "dorothea@mow.vlaanderen.be"));
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

        !rewind($verbose);
        $verboseLog = stream_get_contents($verbose);

        echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";





    }

    #  generate kml
    if (isset($_POST['checkb_kml'])) {
        echo "KML: SUCCESS </br>";
    }

    # return to map

?>
