<?php
/* Corbin - responsive gallery creator
 *
 * Nov 2021 © Jan van den Berg
 * j11g.com
 *
 */

/* Instructions
 * Set the correct input_dir (e.g. images/)
 * Run this file with the correct permission:
 *
 * php genstat.php
 * 
 * This will generate a thumb folder and ultimately a index.html.
 * Move the index.html, thumbs and images folder anywhere and it'll work.
 *
 */

// Variables you can edit
$album_name = "Corbin Sample Album";
$input_dir = 'sample/'; 
$thumb_dir = 'thumbs/';

//Variables that don't need editting
$image_size = 94;
$video_extensions = Array('mp4','avi','MP4');
$video_convert_extensions = Array('3gp','3GP','mov','MOV');
$image_extensions = Array('jpg','png','jpeg','JPG');


// Main loop starts here

// Loop through a folder with images and videos
// Only process images and videos
// But first convert strange video formats to mp4


//Check for video files that need to be converted first
if ($handle = opendir($input_dir)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            $file_parts = pathinfo($entry);
            $file_parts['extension'];

            if (in_array($file_parts['extension'], $video_convert_extensions)){
                echo "Converting video $entry to mp4 format\n"; 
                convert_vid($entry); 
            }
        }
    }
    closedir($handle);
}

// Loop through the dir again
// Look for images and video with the correct format
if ($handle = opendir($input_dir)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            $file_parts = pathinfo($entry);
            $file_parts['extension'];

            //Check for video files
            if (in_array($file_parts['extension'], $video_extensions)){
                process_vid($entry); 
                echo "Processing video $entry\n"; 
            }
            //Check for image files
            if (in_array($file_parts['extension'], $image_extensions)){                                                                                                                                            
                generate_thumb($entry, $input_dir);
                echo "Processing image $entry\n";
            }
        }
    }
    closedir($handle);
}

// Last step generate index.html file 
generate_index();


// Functions follow here

function convert_vid($entry){
    global $input_dir;
    $video = $entry; 
    $convert = system("ffmpeg -loglevel quiet -i $input_dir$video -qscale 0 -y $input_dir$video.mp4");
}

// First extract image still from video with ffmpeg
// Second add a play button to the still
function process_vid($entry){
    global $input_dir;
    global $thumb_dir;
    global $image_size;                                                                                                                                                                                                 
    
    // Extract still and put in the thumbs dir to make sure the still doesn't get processed twice
    $video = $entry;
    $extract_frame = system("ffmpeg -loglevel quiet -ss 00:00:00 -i $input_dir$video -frames:v 1 -y $thumb_dir$video.jpg", $retval);
    $image_name = $video.".jpg";

    // Generate thumb 
    generate_thumb($image_name,$thumb_dir);
    echo "The generated name for the video still is $image_name\n";
    list($width, $height) = getimagesize($thumb_dir."tn_".$image_name);                                                                                                                                            

    // Add play button to still
    // Center the button based on image width and height
    $image_with_play_button = imagecreatefromjpeg($thumb_dir."tn_$image_name");
    $red = imagecolorallocate($image_with_play_button,255,0,0);
    $x1 = $width / 4;
    $y1 = $height / 1.3;

    $x2 = $width / 4;
    $y2 = $height - $y1;

    $x3 = $width / 1.33;
    $y3 = $height / 2;

    $poly_points = array($x1,$y1,$x2,$y2,$x3,$y3);
    imagefilledpolygon($image_with_play_button,$poly_points,3,$red);
    imagejpeg($image_with_play_button, $thumb_dir."/tn_$image_name",90);

}

// Generate thumbnails and put them in the $thumbs_dir folder
// The image orientation is taken into consideration
function generate_thumb($entry, $work_dir) {
    global $image_size;
    global $input_dir;
    global $thumb_dir;

    // Decide whether this is a regular image or a still from a video
    if ($work_dir == $input_dir) {
        $thumb_dir = "thumbs/";
    } 
    if ($work_dir == $thumb_dir){
        $work_dir == "thumbs/";
    }

    $image_output = $entry;
    $file_parts = pathinfo($entry);
    $file_parts['extension'];
    //Check for video files
    $jpg_extensions = Array('jpg','jpeg','JPG','JPEG');
    if (in_array($file_parts['extension'], $jpg_extensions)){
        correctImageOrientation($work_dir.$image_output);
    }

    // Decide image orientation
        list($width, $height) = getimagesize($work_dir.$image_output);
        if ($width > 0 && $width >= $height) {
                $modwidth = $image_size;
                $modheight = $height * $image_size / $width;
        } elseif ($width < $height) {
                $modwidth = $width * $image_size / $height;
                $modheight = $image_size;
        } else {
            echo "Error: something went wrong with $image_output\n";
            exit();
        }

    // Create thumb
        $tn = imagecreatetruecolor($modwidth, $modheight);

        $file_parts = pathinfo($entry);
        switch($file_parts['extension'])
        {
            case "png":
            $source = imagecreatefrompng($work_dir.$image_output);                                                                                                                                               
            break;

            default:
            $source = imagecreatefromjpeg($work_dir.$image_output);                                                                                                                                               
        }

        imagecopyresampled($tn, $source, 0, 0, 0, 0, $modwidth, $modheight, $width, $height);
        imagejpeg($tn, $thumb_dir."/tn_".$image_output, 90);
}

// Some smartphone images have the wrong orientation, this function fixes this
function correctImageOrientation($filename) {
  if (function_exists('exif_read_data')) {
    $exif = exif_read_data($filename);
    if($exif && isset($exif['Orientation'])) {
      $orientation = $exif['Orientation'];
      if($orientation != 1){
        $img = imagecreatefromjpeg($filename);
        $deg = 0;
        switch ($orientation) {
          case 3:
            $deg = 180;
            break;
          case 6:
            $deg = 270;
            break;
          case 8:
            $deg = 90;
            break;
        }
        if ($deg) {
          $img = imagerotate($img, $deg, 0);        
        }
        // then rewrite the rotated image back to the disk as $filename 
        imagejpeg($img, $filename, 95);
      } 
    } 
  }       
}


function generate_index(){
    global $input_dir;
    global $thumb_dir;
    global $album_name;

    $index_file = fopen("index.html", "w") or die("Unable to open file!");
    echo "Writing index.html file\n";

    $head = 
'<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width" />
          <title>'.$album_name.'</title>
            <style type = text/css>
                div.Photo {
                        padding: 2px;
                        float:left;
                        width: 96px;
                        height: 96px;
                        
                        vertical-align: top;

                        overflow: hidden;
                        white-space: nowrap;
                        /*text-overflow: ellipsis;*/
                }
                div.imgBorder {
                        width: 94px;
                        height: 94px;
                        border: 1px solid #E0DFE3;
                        background-color: #FFFFFF;
                        overflow: hidden;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                }

                a.Photo {
                        border: 0px;
                        margin: 0px;
                        padding: 0px;
                        color: #000000;
                        font-family: Tahoma, sans-serif;
                        font-weight: normal;
                        font-size: 11px;
                        text-decoration: none;
                }
                h1 {
                        font-family: Tahoma, sans-serif;
                        font-weight: bold;
                        font-size:40px;
                        text-decoration: none;
                   }
                </style>
                <link
                  rel="stylesheet"
                  href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0/dist/fancybox.css"
                  />
       </head>
       <h1>'. $album_name.' </h1>
       <body>'; 
    fwrite($index_file, $head);

    // Only process the correct extensions from your image folder
    $correct_extensions = Array('jpg','png','jpeg','JPG','mp4','MP4');
    if ($handle = opendir($input_dir)) {
    while (false !== ($entry = readdir($handle))) {
            $file_parts = pathinfo($entry);
            $file_parts['extension'];

            if (in_array($file_parts['extension'], $correct_extensions)){

                $link = '<div class="Photo" align="center"><div class="imgBorder" align="center"><a data-fancybox=gallery href="';
                $link .= "$input_dir$entry";
                $link .= '"><img src=' . '"' . $thumb_dir . 'tn_' . $entry .' "/>';
                // Put the image name in a href, by default this is hidden with CSS
                // Change overflow option to make the image name visible
                $link .= "</a></div><a href=" . '"'. $input_dir.$entry .'" class="Photo">'. $entry. '</a></div>';

                $newline = PHP_EOL . $link;
                fwrite($index_file, $newline);
        }
    }
    closedir($handle);
    $bottom = '<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0/dist/fancybox.umd.js"></script>';
    $bottom .= '</body></html>';

    fwrite($index_file, $bottom);
    fclose($index_file);

    }
}
echo "All done!\n";

?>
