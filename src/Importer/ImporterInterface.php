<?php

namespace Omeka2Importer\Importer;

interface ImporterInterface
{
    /**
     * Return the updated $resourceJson
     */
    public function import($itemData, $resourceJson);
}
