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
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => config('filesystems.disks.s3.region'),
            'credentials' => [
                'key'    => config('filesystems.disks.s3.key'),
                'secret' => config('filesystems.disks.s3.secret'),
            ],
        ]);
    }

    public function initiateMultipartUpload(Request $request)
    {
        $bucket = config('filesystems.disks.s3.bucket');
        $key = 'uploads/' . uniqid() . '-' . $request->file_name;

        $result = $this->s3Client->createMultipartUpload([
            'Bucket' => $bucket,
            'Key'    => $key,
            'ACL'    => 'public-read',
            'ContentType' => $request->file_type,
        ]);

        return response()->json([
            'uploadId' => $result['UploadId'],
            'key' => $key,
        ]);
    }

    public function generatePresignedUrlForPart(Request $request)
    {
        $bucket = config('filesystems.disks.s3.bucket');
        $cmd = $this->s3Client->getCommand('UploadPart', [
            'Bucket' => $bucket,
            'Key'    => $request->key,
            'UploadId' => $request->uploadId,
            'PartNumber' => $request->partNumber,
        ]);

        $presignedRequest = $this->s3Client->createPresignedRequest($cmd, '+20 minutes');

        return response()->json([
            'url' => (string) $presignedRequest->getUri(),
        ]);
    }

    public function completeMultipartUpload(Request $request)
    {
        $bucket = config('filesystems.disks.s3.bucket');

        $result = $this->s3Client->completeMultipartUpload([
            'Bucket' => $bucket,
            'Key'    => $request->key,
            'UploadId' => $request->uploadId,
            'MultipartUpload' => [
                'Parts' => $request->parts,
            ],
        ]);

        return response()->json(['location' => $result['Location']]);
    }
}