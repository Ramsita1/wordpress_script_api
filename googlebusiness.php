<?php

require 'vendor/autoload.php';
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load("C:\Users\abc\Downloads\Google_Bussiness_data.csv");
$worksheet = $spreadsheet->getActiveSheet();
$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2Jsb2cubXBjYy5pbiIsImlhdCI6MTY4MjE0ODM2OSwibmJmIjoxNjgyMTQ4MzY5LCJleHAiOjE2ODI3NTMxNjksImRhdGEiOnsidXNlciI6eyJpZCI6IjEifX19.Tzo6ZUQj22s1wzGNUsRV9MyEGYKxpQDSvmUO6cdWTvQ";
$site_url = "https://blog.mpcc.in/";
// $wordpress_username = "techhome";
// $wordpress_password = "pseo@gmail.com";

$media_url = $site_url."wp-json/wp/v2/media";
$post_url = $site_url."wp-json/wp/v2/posts";


// $auth = base64_encode($wordpress_username.":".$wordpress_password);

foreach ($worksheet->getRowIterator(2) as $row) 
{
    $rowData = [];
    foreach ($row->getCellIterator() as $cell) 
    {
        $rowData[] = $cell->getValue();
    }
    $title = $rowData[0];
    $address = $rowData[1];
    $Phone = $rowData[2];
    $image_url = $rowData[3];

    $images = file_get_contents($image_url);
    file_put_contents("image.jpg", $images);

    $feature = "image.jpg";

    $content = "<b>Address:</b> ".$address."\n <b>Phone No:</b> ".$Phone."<br>" ;
    $content = nl2br($content);
    $media_id = get_media($media_url,$feature,$token);
    $result = create_post($title,$content,$media_id,$post_url,$token);

    if ($result === false) {
        echo "Error creating post: " . print_r(error_get_last(), true);     
    } else {
        $post_id = json_decode($result, true)['id'];
        echo "Post created with ID $post_id \n";
    }
    unlink($feature);
}

function get_media($media_url,$feature,$token)
{
    $image_url = $feature;
    $image_filename = basename($image_url);

    // Get the image data using file_get_contents()
    $image_data = file_get_contents($image_url);
    $headers = array(
        'Content-Disposition: attachment; filename="' . $image_filename . '"',
        'Content-Type: image/jpeg', // Change this based on your image format
        'Authorization: Bearer '.$token
    );
    $options = array(
        'httpversion' => '1.1',
        'header' => implode("\r\n", $headers),
        'method' => 'POST',
        'content' => $image_data,
    );

    $context = stream_context_create(array('http' => $options));
    $response = file_get_contents($media_url, false, $context);
    $image_data = json_decode($response, true);

    // Get the ID of the uploaded image
    $image_id = $image_data['id'];

    return $image_id;
}

function create_post($title,$content,$image_id,$post_url,$token)
{
    $post_data = array(
        'title' => $title,
        'content' => $content,
        'status' => 'publish',
        'featured_media' => $image_id ,// Set the image ID as featured image ID
    );
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer '.$token 
    );
    $options = array(
        'httpversion' => '1.1',
        'header' => implode("\r\n", $headers),
        'method' => 'POST',
        'content' => json_encode($post_data),
    );
    
    // Make the REST API request to create the post and handle the response 
    $context = stream_context_create(array('http' => $options));
    $result = file_get_contents($post_url, false, $context);
   return $result;
}