<?php  
$bucket = "mybucket";
$subFolder = "";  // leave blank for upload into the bucket directly
if (!class_exists('S3'))require_once('S3.php');
			
//AWS access info
if (!defined('awsAccessKey')) define('awsAccessKey', 'mykey');
if (!defined('awsSecretKey')) define('awsSecretKey', 'mysecretkey');


 $options = array( 'image_versions' => array(
   'small' => array(
		'max_width' => 1920,
		'max_height' => 1200,
		'jpeg_quality' => 95
	),
	
	'medium' => array(
		'max_width' => 800,
		'max_height' => 600,
		'jpeg_quality' => 80
	),
	
	'thumbnail' => array(
		'max_width' => 80,
		'max_height' => 80
	)
  ) 
);
		
//instantiate the class
$s3 = new S3(awsAccessKey, awsSecretKey);

 function getFileInfo($bucket, $fileName) {
    global $s3;
    $fileArray = "";
    $size = $s3->getBucket($bucket);
    $furl = "http://" . $bucket . ".s3.amazonaws.com/".$fileName;
    $fileArray['name'] = $fileName;
    $fileArray['size'] = $size;
    $fileArray['url'] = $furl;
    $fileArray['thumbnail'] = $furl;
    $fileArray['delete_url'] = "server/php/index.php?file=".$fileName;
    $fileArray['delete_type'] = "DELETE";
    return $fileArray;
}


 function uploadFiles($bucket, $prefix="") {
    global $s3;
    if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
        return "";
    }
    $upload = isset($_FILES['files']) ? $_FILES['files'] : null;
	//print_r($_FILES);exit;
    $info = array();
    if ($upload && is_array($upload['tmp_name'])) {
	foreach($upload['tmp_name'] as $index => $value) {
		$fileTempName = $upload['tmp_name'][$index];
		$fileName = (isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : $upload['name'][$index]);
		$fileName = $prefix.str_replace(" ", "_", $fileName);
	    // $response = $s3->create_object($bucket, $fileName, array('fileUpload' => $fileTempName, 'acl' => AmazonS3::ACL_PUBLIC, 'meta' => array('keywords' => 'example, test'),));
	    $response = $s3->putObjectFile($fileTempName,$bucket,'images/'.$fileName,S3::ACL_PUBLIC_READ);
	    //print_r($response);
		if ($response==1) {
			$info[] = getFileInfo($bucket, $fileName);
		} else {
				 echo "<strong>Something went wrong while uploading your file... sorry.</strong>";
		}
	}
    } else {
        if ($upload || isset($_SERVER['HTTP_X_FILE_NAME'])) {
            $fileTempName = $upload['tmp_name'];
            $fileName = (isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : $upload['name']);
            $fileName =  $prefix.str_replace(" ", "_", $fileName);
            //$response = $s3->create_object($bucket, $fileName, array('fileUpload' => $fileTempName, 'acl' => AmazonS3::ACL_PUBLIC, 'meta' => array('keywords' => 'example, test'),));
			$response = $s3->putObjectFile($upload['tmp_name'],$bucket,$fileName,S3::ACL_PUBLIC_READ);
            if ($response->isOK()) {
                $info[] = getFileInfo($bucket, $fileName);
            } else {
                     echo "<strong>Something went wrong while uploading your file... sorry.</strong>";
            }
        }
    }
    header('Vary: Accept');
    $json = json_encode($info);
    $redirect = isset($_REQUEST['redirect']) ? stripslashes($_REQUEST['redirect']) : null;
    if ($redirect) {
        header('Location: ' . sprintf($redirect, rawurlencode($json)));
        return;
    }
    if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-type: application/json');
    } else {
        header('Content-type: text/plain');
    }
    return $info;
}
?>
