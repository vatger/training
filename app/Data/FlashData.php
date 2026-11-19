<?php

namespace App\Data;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class FlashData extends Data
{
    public function __construct(
        public ?string $success,
        public ?string $error,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $session = $request->session();

        return new self(
            success: $session->get('flash.success', $session->get('success')),
            error: $session->get('flash.error', $session->get('error', $session->get('errors')?->first('error'))),
        );
    }
}
