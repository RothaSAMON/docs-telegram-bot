<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Aws\S3\S3Client;

class S3FileUpload
{
    public function uploadFile(string $fileContent, string $extension = 'jpg'): ?string
    {
        try {
            $s3Client = new S3Client([
                'version' => 'latest',
                'region'  => config('filesystems.disks.s3.region'),
                'credentials' => [
                    'key'    => config('filesystems.disks.s3.key'),
                    'secret' => config('filesystems.disks.s3.secret'),
                ],
            ]);

            $bucket = config('filesystems.disks.s3.bucket');
            $fileName = 'telegram/' . Str::uuid() . '.' . $extension;

            // Ensure binary content
            if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $fileContent)) {
                $fileContent = base64_decode($fileContent);
            }

            // Set correct content type based on extension
            $contentType = match($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'ogg', 'voice' => 'audio/ogg',
                default => 'application/octet-stream'
            };

            // Upload using AWS SDK without ACL
            $result = $s3Client->putObject([
                'Bucket' => $bucket,
                'Key'    => $fileName,
                'Body'   => $fileContent,
                'ContentType' => $contentType
            ]);

            if (!$result['ObjectURL']) {
                throw new \Exception('Upload failed - no URL returned');
            }

            $url = $result['ObjectURL'];
            
            Log::info('File uploaded successfully', [
                'fileName' => $fileName,
                'url' => $url,
                'size' => strlen($fileContent)
            ]);

            return $url;
        } catch (\Exception $e) {
            Log::error('S3 upload error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'bucket' => config('filesystems.disks.s3.bucket'),
                'region' => config('filesystems.disks.s3.region'),
                'key_exists' => !empty(config('filesystems.disks.s3.key')),
                'secret_exists' => !empty(config('filesystems.disks.s3.secret'))
            ]);
            return null;
        }
    }
}