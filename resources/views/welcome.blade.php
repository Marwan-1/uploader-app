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

        autoProcessQueue: false,

        // Content-Type should be included, otherwise you'll get a signature
        // mismatch error from S3. We're going to update this for each file.
        headers: '',
      
        // dictDefaultMessage: document.querySelector('#dropzone-message').innerHTML,
        sending: function(file, xhr) {
            // Set the Content-Type header for the file.
            xhr.setRequestHeader('Content-Type', file.type);
            
            //Override default behaviour of Dropzone which uses FormData to send the file.
            let _send = xhr.send;
            xhr.send = () => {
                // Call the original send function with the file as the body
                _send.call(xhr, file);
            };
        },
        accept: function(file, done) {
            axios.get('/presigned-url', {
                params: { file_name: file.name, file_type: file.type }
            }).then(function(response) {
                // Set the upload URL to the pre-signed URL.
                file.uploadURL = response.data.url;
                done();
                setTimeout(() => myDropzone.processFile(file));
                
            }).catch(function(error) {
                done('Failed to get an S3 signed upload URL');
            });
        }
    });

    myDropzone.on('processing', function(file) {
        myDropzone.options.url = file.uploadURL;
    });
</script>

<!-- Add your HTML content here -->

</body>
</html>
