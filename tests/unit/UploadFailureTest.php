<?php

use App\Libraries\ProxyResponseMapper;
use PHPUnit\Framework\TestCase;

/**
 * A file PHP rejects (e.g. over upload_max_filesize) must surface as a 422 on the
 * `image` field instead of the save silently succeeding without the image.
 *
 * @internal
 */
final class UploadFailureTest extends TestCase
{
    public function testNoErrorMeansNoFailure(): void
    {
        $this->assertNull(ProxyResponseMapper::uploadFailure(UPLOAD_ERR_OK));
        $this->assertNull(ProxyResponseMapper::uploadFailure(UPLOAD_ERR_NO_FILE));
    }

    public function testOversizeIsReportedOnTheImageField(): void
    {
        foreach ([UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE] as $code) {
            $r = ProxyResponseMapper::uploadFailure($code);

            $this->assertNotNull($r);
            $this->assertSame(422, $r['status']);
            $this->assertSame([], $r['data']);
            $this->assertSame(0, $r['total']);
            $this->assertStringContainsString('5 MB', $r['errors']['image']);
            $this->assertSame($r['message'], $r['errors']['image']);
        }
    }

    public function testEveryOtherErrorCodeIsReported(): void
    {
        foreach ([UPLOAD_ERR_PARTIAL, UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION, 99] as $code) {
            $r = ProxyResponseMapper::uploadFailure($code);

            $this->assertNotNull($r, "code {$code}");
            $this->assertSame(422, $r['status']);
            $this->assertNotSame('', $r['errors']['image']);
        }
    }
}