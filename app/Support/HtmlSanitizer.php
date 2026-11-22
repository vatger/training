<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Allowlist sanitiser for the rich text produced by the training-log editor (Tiptap
 * StarterKit + Image). Anything outside the allowlist, including scripts, event handlers
 * and javascript: URLs, is stripped.
 */
class HtmlSanitizer
{
    private static ?HTMLPurifier $purifier = null;

    public static function clean(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        return self::purifier()->purify($html);
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier) {
            return self::$purifier;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed', implode(',', [
            'p', 'br', 'strong', 'b', 'em', 'i', 's', 'strike', 'code', 'pre', 'blockquote',
            'h1', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'hr',
            'a[href|title]',
            'img[src|alt|title|width|height]',
        ]));
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.Nofollow', true);
        $config->set('Cache.SerializerPath', storage_path('framework/cache'));

        return self::$purifier = new HTMLPurifier($config);
    }
}
