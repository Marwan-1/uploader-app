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
        url: '/dummy',
        method: 'put',
         // Upload one file at a time since we're using the S3 pre-signed URL scenario
        parallelUploads: 1,
        uploadMultiple: false,

        timeout: 0, // No timeout for the XHR request
        maxFilesize: null, // No limit on the file size

        //chunking
        chunking: true,
        forceChunking: true,
        chunkSize: 5 * 1024 * 1024, // 5MB chunk size minimum as set by S3 Amazon 
        parallelChunkUploads: false,
        retryChunks: true,
        retryChunksLimit: 3,
        defaultHeaders: false,
        binaryBody: true,

        autoProcessQueue: false,

        addRemoveLinks: true, // Add remove links to each file preview
        
        // Content-Type should be included, otherwise you'll get a signature
        // mismatch error from S3. We're going to update this for each file.
        headers: '',
    
        
        chunksUploaded: function (file, done) {
            //console.log("All chunks have been uploaded for file:", file.name);
            //console.log("file.chunkETags:", file.chunkETags);

            // Ensure that the ETags are available and in the correct format
            let parts = file.chunkETags.map((etag, index) => {
                //console.log(`Mapping ETag at index ${index}:`, etag);
                return {
                    PartNumber: index + 1, // Chunk number (1-based index)
                    ETag: etag // The ETag value, with quotes removed
                };
            });
            // console.log("Parts :", parts);
            axios.post('/s3/complete-multipart-upload', {
                file_name: file.upload.filename, // Assuming the key is the filename
                uploadId: file.uploadId, // Using the UUID as the uploadId
                parts: parts
            }).then(function (response) {
                console.log("Multipart upload completed for file", file.name, response.data);
                done();
            }).catch(function (error) {
                // console.error("Failed to complete S3 upload for file", file.name, error);
                done("Failed to complete upload");
            });
        },

        init: function() {
        this.on("addedfile", function(file) {
            console.log("File added:", file.name);

            // Initialize an array to store ETags for each chunk
            file.chunkETags = []; // Make sure this line is present to initialize the array

            axios.post('/s3/initiate-multipart-upload', {
                file_name: file.name,
                file_type: file.type
            }).then(function(response) {
                // console.log("Initiated multipart upload:", response.data);
                file.uploadId = response.data.uploadId;
                file.s3Key = response.data.key;
                myDropzone.processFile(file);
            }).catch(function(error) {
                // console.error("Failed to initiate S3 upload", error);
            });
        });
    



        this.on("sending", function(file, xhr) {
            console.log("Sending file:", file.name);
            // console.log("all file props:", file);
            // console.log("xhr props:", xhr);
            
            if(!file.chunkETags){
                file.chunkETags=[];
            }

            // If chunking is enabled, there should be a chunks array
        if (file.upload.chunks && file.upload.chunks.length > 0) {
        // Get the current chunk's data
        let currentChunk = file.upload.chunks[file.upload.chunks.length - 1];
        // The chunkIndex should be 1-based for S3, so add 1
        let chunkIndex = currentChunk.dataBlock.chunkIndex + 1;
        // console.log("Chunk Index:", chunkIndex);
        
        //this works!!

           // Override the onload function
        let originalOnload = xhr.onload;
        xhr.onload = function(e) {

              // Custom ETag logic
              if (xhr.status >= 200 && xhr.status < 300) {
                let etag = xhr.getResponseHeader('ETag');
                if (etag) {
                    // Ensure the file.chunkETags array is initialized
                    if (!file.chunkETags) {
                        file.chunkETags = [];
                    }
                    // Store the ETag for this chunk
                    file.chunkETags[chunkIndex - 1] = etag.replace(/"/g, '');
                    // console.log("Stored ETag for chunk", chunkIndex, ":", etag);
                }
            }


            // Call the original onload function
            if (originalOnload) {
                originalOnload(e);
            }

          
        };
        
   
            axios.get('/s3/generate-presigned-url', {
                params: {
                    key: file.s3Key,
                    uploadId: file.uploadId,
                    partNumber: chunkIndex
                }
            }).then(function(response) {
                // console.log("Received presigned URL for chunk:", chunkIndex, response.data.url);
                // Set the URL to the signed URL for this chunk
                xhr.open('PUT', response.data.url, true);
                // Set headers if required by S3
                // xhr.setRequestHeader('Content-Type', file.type);

                // Send the chunk data
                // console.log("Sending chunk data:", currentChunk.dataBlock.data);
                xhr.send(currentChunk.dataBlock.data);
           
            }).catch(function(error) {
                // console.error("Failed to get a presigned URL for chunk", error);
            });
        } else {
        // console.error("No chunks found in file.upload.chunks");
    }
        });
    
        // this.on("error", function(file, response) {
        //     console.error("Dropzone error:", response);
        // });

            this.on("success", function (file, response) {
                //only gets here when done is called on chunksuploaded
                console.log("File uploaded successfully", file.name);
                // console.log("Uploaded File:", file);

            });


        
    }
});
</script>

<!-- Add your HTML content here -->

</body>
</html>
