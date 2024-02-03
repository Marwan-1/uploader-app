<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Uploader</title>

    <!-- Include Dropzone.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css" />
    <!-- Include Axios for HTTP requests -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body>

<!-- Add your HTML content here -->


<!-- <form method="post" action="/upload"  enctype="multipart/form-data" class="dropzone" id="my-dropzone">
    @csrf
</form> -->
<form class="dropzone" id="my-dropzone"></form>


<script src="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js"></script>
<script>
    Dropzone.autoDiscover = false;

    let myDropzone = new Dropzone("#my-dropzone", {
        url: '/',
        method: 'put',
         // Upload one file at a time since we're using the S3 pre-signed URL scenario
        parallelUploads: 1,
        uploadMultiple: false,

        timeout: 0, // No timeout for the XHR request
        maxFilesize: null, // No limit on the file size

        //chunking
        chunking: true,
        forceChunking: true,
        chunkSize: 5 * 1024 * 1024, // 5MB chunk size
        parallelChunkUploads: false,
        retryChunks: true,
        retryChunksLimit: 3,
        defaultHeaders: false,
        binaryBody: true,

        autoProcessQueue: false,

        // Content-Type should be included, otherwise you'll get a signature
        // mismatch error from S3. We're going to update this for each file.
        headers: '',
      
        init: function() {
            this.on("addedfile", function(file) {
                // Initiate multipart upload and get upload ID
                axios.post('/s3/initiate-multipart-upload', {
                    file_name: file.name,
                    file_type: file.type
                }).then(function(response) {
                    file.uploadId = response.data.uploadId;
                    file.s3Key = response.data.key;
                    myDropzone.processFile(file); // Start processing file
                }).catch(function(error) {
                    console.error("Failed to initiate S3 upload", error);
                });
            });

            this.on("sending", function(file, xhr, formData) {
                // Override the default sending behavior to use raw file data
                let _send = xhr.send;
                xhr.send = () => _send.call(xhr, file);
            });

            this.on("uploadprogress", function(file, progress) {
                console.log(`File progress: ${progress}%`);
            });

            this.on("error", function(file, response) {
                console.error("Dropzone error:", response);
            });

            this.on("success", function(file, response) {
                console.log("File uploaded successfully:", response);
            });
            this.on("chunksUploaded", function (file, done) {
                 // This function is called when all chunks have been uploaded // We need to tell S3 to complete the multipart upload axios.post('/s3/complete-multipart-upload', { key: file.s3Key, uploadId: file.uploadId, parts: file.uploadedChunks.map((chunk, index) => ({ ETag: chunk.xhr.getResponseHeader('ETag'), PartNumber: index + 1 })) }).then(function(response) { console.log("Multipart upload completed:", response.data); done(); }).catch(function(error) { console.error("Failed to complete S3 upload", error); done("Failed to complete upload"); }); });
            
        }
    });

    myDropzone.on('sending', function(file, xhr) {
        // Get presigned URL for each chunk
        axios.get('/s3/generate-presigned-url', {
            params: {
                key: file.s3Key,
                uploadId: file.uploadId,
                partNumber: file.upload.chunkIndex + 1
            }
        }).then(function(response) {
            // Set the upload URL to the pre-signed URL
            xhr.open(myDropzone.options.method, response.data.url, true);
        }).catch(function(error) {
            console.error("Failed to get a presigned URL for chunk", error);
        });
    });

    myDropzone.on('complete', function(file) {
        // Complete multipart upload after all chunks are uploaded
        axios.post('/s3/complete-multipart-upload', {
            key: file.s3Key,
            uploadId: file.uploadId,
            parts: file.upload.chunks.map(chunk => ({
                ETag: chunk.xhr.getResponseHeader('ETag'),
                PartNumber: chunk.index + 1
            }))
        }).then(function(response) {
            console.log("Multipart upload completed:", response.data);
        }).catch(function(error) {
            console.error("Failed to complete S3 upload", error);
        });
    });
</script>

<!-- Add your HTML content here -->

</body>
</html>
