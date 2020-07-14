<?php

require 'aws.phar';

// required classes
use Aws\S3\S3Client;
use Aws\Rekognition\RekognitionClient;

// aws credentials
$bucket = "";
$region = "";
$access_key_id = "";
$secret_access_key = "";

// s3 client
$s3 = new S3Client([
    'region' => $region,
    'version' => 'latest',
    'credentials' => [
        'key' => $access_key_id,
        'secret' => $secret_access_key,
    ]
]);

// aws rekognition
$rekognition = new RekognitionClient([
    'region' => $region,
    'version' => 'latest',
    'credentials' => [
        'key' => $access_key_id,
        'secret' => $secret_access_key,
    ]
]);

if(isset($_REQUEST['imgUrl'])){
    $imgUrl = $_REQUEST['imgUrl'];

    try{
        // downloads file to server
        $tempFilePath = explode("?", basename($imgUrl))[0];
        $tempFile = fopen($tempFilePath, "w") or die("Error: Unable to open file.");
        $fileContents = file_get_contents($imgUrl);
        $tempFile = file_put_contents($tempFilePath, $fileContents);

        // uploads file to s3
        $upload = $s3->putObject([
            'Bucket' => $bucket,
            'Key' => $tempFilePath,
            'SourceFile' => $tempFilePath,
            'ACL' => 'public-read',
        ]);

        // extract text from image
        $result = $rekognition->detectText([
            'Image' => [
                'S3Object' => [
                    'Bucket' => $bucket,
                    'Name' => $tempFilePath,
                ]
            ]
        ]);

        // deleted temp s3 file
        $s3del =$s3->deleteObject([
            'Bucket' => $bucket,
            'Key' => $tempFilePath
        ]);

        // garbage collection
        gc_collect_cycles();
        try {unlink($tempFilePath);} catch(Exception $ex) {}

        header('Content-Type: application/json');
        print(json_encode(array('result' => $result['TextDetections'])));
        exit;
    }
    catch(Exception $exp){
        exit($exp);
    }
}

?>