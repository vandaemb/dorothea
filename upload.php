<?php

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
        # nada
    } else {
        header("Location: http://10.132.32.231/dorothea/error/mail.html");
        exit;
    }
}



$uploaddir = '/var/www/dorothea/uploads/';
$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);


if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
    # File is valid

        # mail image if checkbox checked
        # the server is configured with postfix, using the intranets smtp as relayserver.
        if (isset($_POST['checkb_mail'])) {
            $my_file = basename($_FILES['userfile']['name']);
            $my_path = $uploaddir;
            $my_name = "Dorothea";
            $my_mail = "dorothea@mow.vlaanderen.be";
            $my_replyto = "marc.vandael@mow.vlaanderen.be";
            $my_subject = "Dorothea Mailservice";
            # $my_message = $_POST["usertext"] . "\r\n" . $_POST["ident8"] . " - " . $_POST["refpt"] . "\r\n" . $_POST["lambert72"] . "\r\n" . $_POST["wgs84"];
            $my_message  = "Verslag van opmeting gedaan op de " . $_POST["ident8"] . ", referentiepunt " . $_POST["refpt"];
            $my_message .= "\r\n\r\n\r\n";
            $my_message .= $_POST["usertext"] . "\r\n";
            $my_message .= "GPS: " . $_POST["wgs84"] . "\r\n";
            $my_message .= "Lambert72: " . $_POST["lambert72"] . "\r\n";;
            mail_attachment($my_file, $my_path, "marc.vandael@mow.vlaanderen.be", $my_mail, $my_name, $my_replyto, $my_subject, $my_message);
        }

        #  upload to geoloket personal config
        if (isset($_POST['checkb_geoloket'])) {
            #
        }

        #  generate kml
        if (isset($_POST['checkb_kml'])) {
            #

        }

        # return to app. I suppose we should be using ajax.
        header("Location: http://10.132.32.231/dorothea/");
        exit;

} else {

    # File is invalid
    header("Location: http://10.132.32.231/dorothea/error/file.html");
}

echo "</p>";
echo '<pre>';
print_r($_FILES);
print "</pre>";







?>
