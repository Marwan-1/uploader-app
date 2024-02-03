#File Uploader 
_______________________________________________________________
Laravel app to upload files directly to S3 bucket using dropzone

basic files:
*view file `resources\views\welcome.blade.php`
*controller file `app\Http\Controllers\FileController.php`
*and the route in web.php
`Route::get('/presigned-url', [FileController::class  , 'generatePresignedUrl']);`
________________________________________________________________

deployment
________________________________________________________________

#1#
Update the .env File
-Adjust DB connection for proper laravel startup.
-Add your AWS S3 credentials to your Laravel project's .env file:

(.env)++++++++++++++++++++++
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
++++++++++++++++++++++++++++

#2#
run in cmd in cloned directory:
`composer install`
`php artisan key:generate`
`php artisan serve` and go to `/` to view dropzone.

#Ensure that your S3 bucket has the appropriate CORS configuration to allow uploads from your domain.

Replace `https://yourdomain.com` with your actual domain or * for testing purposes (not recommended for production).

`<CORSConfiguration>
 <CORSRule>
   <AllowedOrigin>https://yourdomain.com</AllowedOrigin>
   <AllowedMethod>PUT</AllowedMethod>
   <AllowedMethod>POST</AllowedMethod>
   <AllowedHeader>*</AllowedHeader>
 </CORSRule>
</CORSConfiguration>`
