<?php

namespace Omeka2Importer\View\Helper;

use Zend\View\Helper\AbstractHelper;

class ResourceTemplateSelector extends AbstractHelper
{
    /**
     * Return the resource template selector form control.
     *
     * @return string
     */
    public function __invoke($text = null, $active = true)
    {
        return $this->getView()->partial(
            'omeka2-importer/common/resource-template-selector',
            [
                'templates' => $this->getView()->api()->search('resource_templates')->getContent(),
                'text' => $text,
                'state' => $active ? 'active' : '',
            ]
        );
    }
}
