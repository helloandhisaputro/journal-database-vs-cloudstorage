## SETUP
- set .env file 
- composer install
- php artisan migrate

## RUN TEST 
1. Dwonload from S3
php artisan test tests/Feature/DownloadMediaTransmisiAWSS3.php

2. Download from Database
php artisan test tests/Feature/DownloadMediaTransmisiDatabase.php

3. Upload to S3
php artisan test tests/Feature/UploadMediaTransmisiAWSS3.php

4. Upload to Database
php artisan test tests/Feature/UploadMediaTransmisiDatabase.php