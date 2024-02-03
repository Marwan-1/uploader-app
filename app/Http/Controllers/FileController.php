<?php

namespace App\Http\Controllers;

use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{

public function generatePresignedUrl(Request $request)
    {
        // $validatedData = $request->validate([
        //     'file_name' => 'required|string',
        // ]);

        // Log::info("generate presigned url function called");

        // Create an S3 client instance
        $s3Client = new S3Client([
            'version' => 'latest',
            'region'  => config('filesystems.disks.s3.region'),
            'credentials' => [
                'key'    => config('filesystems.disks.s3.key'),
                'secret' => config('filesystems.disks.s3.secret'),
            ],
        ]);

        // Define the S3 bucket and key
        $bucket = config('filesystems.disks.s3.bucket');
        //insert in folder uploads _ uniqid _ file_name
        $key = 'uploads/' . uniqid() . '-' . $request->file_name;

        // Generate a pre-signed URL for a PUT request
        $cmd = $s3Client->getCommand('PutObject', [
            'Bucket' => $bucket,
            'Key'    => $key, // $request->file_name if you dont want any hash naming 
            'ACL'    => 'public-read', // or 'private',
            'ContentType' => $request->file_type // Set the content type
        ]);

        $request = $s3Client->createPresignedRequest($cmd, '+20 minutes'); //+1 hours , +2 hours

        // Get the pre-signed URL
        $presignedUrl = (string) $request->getUri();

        return response()->json([
            'url' => $presignedUrl,
        ]);
    }

}
