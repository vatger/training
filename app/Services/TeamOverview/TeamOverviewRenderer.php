<?php

namespace App\Services\TeamOverview;

use App\Models\TeamOverviewTemplate;

class TeamOverviewRenderer
{
    public function __construct(private readonly TeamOverviewData $data) {}

    /** Fills the dynamic `{{section}}` placeholders of the post template with the current data. */
    public function render(): string
    {
        $replacements = [];

        foreach ($this->data->sections() as $key => $lines) {
            $replacements['{{'.$key.'}}'] = implode("\n", $lines);
        }

        return strtr(TeamOverviewTemplate::currentContent(), $replacements);
    }
}
