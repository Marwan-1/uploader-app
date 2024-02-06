<?php

namespace App\Http\Controllers;

use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    protected $s3Client;

    public function __construct()
    {

        //Warning! SSL is disabled for debugging 
       
        
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => config('filesystems.disks.s3.region'),
            'credentials' => [
                'key'    => config('filesystems.disks.s3.key'),
                'secret' => config('filesystems.disks.s3.secret'),
            ],

            //Disable SSL verification for debugging
            'http' => [
                'verify' => false,
            ],


        ]);


    }


    public function initiateMultipartUpload(Request $request)
    {
        // Log::info('Received request to initiate multipart upload.', ['file_name' => $request->file_name]);

        $bucket = config('filesystems.disks.s3.bucket');

        // Log::info('Using bucket for upload.', ['bucket' => $bucket]);

        //adjust key to to the path of your choice
        //recommended hashed for not overwriting files
        // $key = 'uploads/' . uniqid() . '-' . $request->file_name;
        $key = 'uploads/' . $request->file_name;

        // ACL line is commented 
        //remove it if you want to make the file public and you have the permissions to do so
        $result = $this->s3Client->createMultipartUpload([
            'Bucket' => $bucket,
            'Key'    => $key,
            // 'ACL'    => 'public-read',
            'ContentType' => $request->file_type,
        ]);

        
        // Log::info('Multipart upload initiated.', ['uploadId' => $result['UploadId']]);

        return response()->json([
            'uploadId' => $result['UploadId'],
            'key' => $key,
        ]);
    }

    public function generatePresignedUrlForPart(Request $request)
    {
        $bucket = config('filesystems.disks.s3.bucket');

        // Log::info("Uploading part", ['PartNumber' => $request->partNumber]);

        $cmd = $this->s3Client->getCommand('UploadPart', [
            'Bucket' => $bucket,
            'Key'    => $request->key,
            'UploadId' => $request->uploadId,
            'PartNumber' => $request->partNumber,
        ]);

        //link viability is 20 minutes
        $presignedRequest = $this->s3Client->createPresignedRequest($cmd, '+20 minutes');

        return response()->json([
            'url' => (string) $presignedRequest->getUri(),
        ]);
    }

    public function completeMultipartUpload(Request $request)
    {
        $bucket = config('filesystems.disks.s3.bucket');


        //adjust key again to the path of your choice it must be the same as the key used in initiateMultipartUpload
        $key = 'uploads/' . $request->file_name;


        $result = $this->s3Client->completeMultipartUpload([
            'Bucket' => $bucket,
            'Key'    => $key,
            'UploadId' => $request->uploadId,
            'MultipartUpload' => [
                'Parts' => $request->parts,
            ],
        ]);

        // Return a successful response
        Log::info('Multipart upload completed.', ['location' => $result['Location']]);
        return response()->json(['location' => $result['Location']]);
    }
}