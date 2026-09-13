<?php

namespace App\Integrations\Datahub;

interface DatahubClientInterface
{
    /**
     * Fetch the configured datahub station registry files.
     *
     * @return array<string,string> map of mirror-relative path
     *                              (e.g. "datahub/edgg/ctr.json") => raw JSON
     */
    public function fetch(): array;
}
