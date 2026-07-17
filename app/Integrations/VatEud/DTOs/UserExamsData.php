<?php

namespace App\Integrations\VatEud\DTOs;

readonly class UserExamsData
{
    public function __construct(
        /** @var ExamResultData[] */
        public array $results,
        public array $assignments,
    ) {}

    public static function fromApiResponse(array $data): self
    {
        $results = array_map(
            fn(array $r) => ExamResultData::fromApiResponse($r),
            $data['results'] ?? [],
        );

        return new self(
            results:     $results,
            assignments: $data['assignments'] ?? [],
        );
    }
}