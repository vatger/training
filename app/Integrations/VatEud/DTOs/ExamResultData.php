<?php

namespace App\Integrations\VatEud\DTOs;

use Carbon\Carbon;

readonly class ExamResultData
{
    public function __construct(
        public int    $examId,
        public bool   $passed,
        public Carbon $expiry,
    ) {}

    public static function fromApiResponse(array $data): self
    {
        return new self(
            examId: $data['exam_id'],
            passed: (bool) $data['passed'],
            expiry: Carbon::parse($data['expiry']),
        );
    }
}