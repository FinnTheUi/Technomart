<?php

namespace Tests\Unit;

use App\Services\Uploads\ImageSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageSanitizerTest extends TestCase
{
    public function test_valid_image_is_reencoded_and_stored_as_random_jpeg(): void
    {
        Storage::fake('public');
        $upload = UploadedFile::fake()->image('phone-photo.png', 320, 240);

        $path = app(ImageSanitizer::class)->store($upload, 'maintenance');

        $this->assertMatchesRegularExpression('/^maintenance\/[0-9a-f-]{36}\.jpg$/', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame('image/jpeg', (new \finfo(FILEINFO_MIME_TYPE))->buffer(Storage::disk('public')->get($path)));
    }

    public function test_invalid_image_payload_is_rejected(): void
    {
        Storage::fake('public');
        $upload = UploadedFile::fake()->createWithContent('fake.jpg', '<?php echo "not an image";');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('could not be processed');

        app(ImageSanitizer::class)->store($upload, 'maintenance');
    }
}
