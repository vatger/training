<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class TeamOverviewTemplate extends Model
{
    use LogsActivity;

    /** Placeholders replaced with the generated lists; each must appear in the template. */
    public const PLACEHOLDERS = ['{{pruefer}}', '{{bremen}}', '{{langen}}', '{{muenchen}}'];

    protected $fillable = ['content'];

    /** The edited template, or the default shipped with the app if none was saved yet. */
    public static function currentContent(): string
    {
        return self::query()->value('content') ?? self::defaultContent();
    }

    public static function defaultContent(): string
    {
        return file_get_contents(resource_path('team-overview/post.bbcode'));
    }
}
