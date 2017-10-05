<?php

return [
    'service_manager' => [
        'invokables' => [
            'Omeka2Importer\Omeka2Client' => 'Omeka2Importer\Service\Omeka2Client',
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'omekaimport_records' => 'Omeka2Importer\Api\Adapter\OmekaimportRecordAdapter',
            'omekaimport_imports' => 'Omeka2Importer\Api\Adapter\OmekaimportImportAdapter',
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => OMEKA_PATH . '/modules/Omeka2Importer/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            'Omeka2Importer\Controller\Index' => 'Omeka2Importer\Service\Controller\IndexControllerFactory',
        ],
    ],
    'form_elements' => [
        'invokables' => [
            'Omeka2Importer\Form\ImportForm' => 'Omeka2Importer\Form\ImportForm',
        ],
        'factories' => [
            'Omeka2Importer\Form\MappingForm' => 'Omeka2Importer\Service\Form\MappingFormFactory',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH.'/modules/Omeka2Importer/view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
        'resourceClassSelector' => 'Omeka2Importer\View\Helper\ResourceClassSelector',
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            OMEKA_PATH.'/modules/Omeka2Importer/src/Entity',
        ],
    ],

    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'omeka2importer' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/omeka2importer',
                            'defaults' => [
                                '__NAMESPACE__' => 'Omeka2Importer\Controller',
                                'controller' => 'Index',
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'past-imports' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/past-imports',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Omeka2Importer\Controller',
                                        'controller' => 'Index',
                                        'action' => 'past-imports',
                                    ],
                                ],
                            ],
                            'map-elements' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/map-elements',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Omeka2Importer\Controller',
                                        'controller' => 'Index',
                                        'action' => 'map-elements',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Omeka 2 Importer', // @translate
                'route' => 'admin/omeka2importer',
                'resource' => 'Omeka2Importer\Controller\Index',
                'pages' => [
                    [
                        'label' => 'Import', // @translate
                        'route' => 'admin/omeka2importer',
                        'resource' => 'Omeka2Importer\Controller\Index',
                    ],
                    [
                        'label' => 'Import', // @translate
                        'route' => 'admin/omeka2importer/map-elements',
                        'resource' => 'Omeka2Importer\Controller\Index',
                        'visible' => false,
                    ],
                    [
                        'label' => 'Past Imports', // @translate
                        'route' => 'admin/omeka2importer/past-imports',
                        'controller' => 'Index',
                        'action' => 'past-imports',
                        'resource' => 'Omeka2Importer\Controller\Index',
                    ],
                ],
            ],
        ],
    ],
];
