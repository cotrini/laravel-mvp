<?php

namespace Tests\Unit\Modules\Property\Support;

use App\Modules\Property\Support\PdfPageCountValidator;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class PdfPageCountValidatorTest extends TestCase
{
    public function test_accepts_a_pdf_with_ten_pages(): void
    {
        $this->assertPdfPageCount($this->pdfWithPages(10), true);
    }

    public function test_rejects_a_pdf_with_more_than_ten_pages(): void
    {
        $this->assertPdfPageCount($this->pdfWithPages(11), false);
    }

    public function test_rejects_a_pdf_whose_page_count_cannot_be_verified(): void
    {
        $this->assertPdfPageCount("%PDF-1.4\n%%EOF", false);
    }

    private function assertPdfPageCount(string $contents, bool $expected): void
    {
        $path = tempnam(sys_get_temp_dir(), 'pdf');

        if ($path === false) {
            $this->fail('Unable to create a temporary PDF file.');
        }

        try {
            file_put_contents($path, $contents);
            $pdf = new UploadedFile($path, 'plans.pdf', 'application/pdf', UPLOAD_ERR_OK, true);

            $this->assertSame($expected, (new PdfPageCountValidator)->isWithinPageLimit($pdf, 10));
        } finally {
            unlink($path);
        }
    }

    private function pdfWithPages(int $pageCount): string
    {
        $pdf = "%PDF-1.4\n1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
        $pdf .= "2 0 obj << /Type /Pages /Count {$pageCount} /Kids [] >> endobj\n";

        for ($page = 0; $page < $pageCount; $page++) {
            $objectId = $page + 3;
            $pdf .= "{$objectId} 0 obj << /Type /Page /Parent 2 0 R >> endobj\n";
        }

        return $pdf."trailer << /Root 1 0 R >>\n%%EOF";
    }
}
