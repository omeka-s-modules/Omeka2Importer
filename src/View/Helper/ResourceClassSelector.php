<?php

namespace Omeka2Importer\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class ResourceClassSelector extends AbstractHelper
{
    /**
     * Return the resource class selector form control.
     *
     * @return string
     */
    public function __invoke($text = null, $active = true)
    {
        $response = $this->getView()->api()->search('vocabularies');

        $valueOptions = [];
        foreach ($response->getContent() as $vocabulary) {
            $options = [];
            foreach ($vocabulary->resourceClasses() as $resourceClass) {
                $options[$resourceClass->id()] = $resourceClass->label();
            }
            if (!$options) {
                continue;
            }
            $valueOptions[] = [
                'label' => $vocabulary->label(),
                'options' => $options,
            ];
        }

        return $this->getView()->partial(
            'omeka2-importer/common/resource-class-selector',
            [
                'vocabularies' => $response->getContent(),
                'text' => $text,
                'state' => $active ? 'active' : '',
            ]
        );
    }
}
