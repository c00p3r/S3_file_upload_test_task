## S3 file upload (with SQS)
Using Laravel, Docker, Localstack (AWS simulation)
### Requirements
* [docker](https://docs.docker.com/get-docker/)
* [docker-compose](https://docs.docker.com/compose/install/)
* [composer](https://getcomposer.org/)
* [aws-cli](https://github.com/aws/aws-cli)

### Instructions:
1) run `aws configure`. Set your credentials.
2) run `cp .env.example .env`
3) check your `.env` file and add following values
    ```dotenv
    WWWGROUP=1000
    WWWUSER=1000
    APP_FILE_SIZE_THRESHOLD=500
    AWS_ACCESS_KEY_ID=<your-aws-id>
    AWS_SECRET_ACCESS_KEY=<your-aws-access-key>
    AWS_DEFAULT_REGION=us-east-1 #any
    AWS_BUCKET=<your-bucket-name>
    AWS_USE_PATH_STYLE_ENDPOINT=true
    AWS_ENDPOINT=http://localstack:4566
    SQS_PREFIX=http://localstack:4566/000000000000
    ```
   for debugging add also `SAIL_XDEBUG_MODE=debug`
4) run `composer install`
5) run `docker-compose up -d`
6) create S3 bucket `aws s3 mb s3://<your-bucket-name> --endpoint-url http://localhost:4566`
7) create SQS 'default' queue `aws sqs create-queue --queue-name default --endpoint-url http://localhost:4566`
8) get in php container shell e.g. `docker exec -it laravel bash`
9) run in container shell queue worker `php artisan queue:work sqs -v`
10) now you need files to upload in a folder at Laravel local storage path (./storage/app/upload). You can easily
    generate fake files using command like `mkfile -n 200m 200mb.file`
11) in another terminal window also get in container shell and run `php artisan s3:upload <your-folder-with-files>`

To check jobs execution see queue worker log.

To see files on S3 run `aws s3 ls s3://<your-bucket-name> --recursive --human-readable --summarize --endpoint-url=http://localhost:4566`

### Notes
There are some things I considered but did not implement for simplicity of the task:
* single file size - one file can be larger than 500 MB
* archive names - can be generated to be more unique
* duplicate file names - files can be renamed with suffix to store both duplicates
* folder structure - folder structure could be preserved (with subdirectories)
* exceptions handling - there are plenty of places in the code where exceptions handling can be added
