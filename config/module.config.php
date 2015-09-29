<?php
return array(
    'service_manager' => array(
        'invokables' => array(
            'Omeka2Importer\Omeka2Client' => 'Omeka2Importer\Service\Omeka2Client'
        )
    ),
    'api_adapters' => array(
        'invokables' => array(
            'omekaimport_records'   => 'Omeka2Importer\Api\Adapter\OmekaimportRecordAdapter',
            'omekaimport_imports' => 'Omeka2Importer\Api\Adapter\OmekaimportImportAdapter'
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Omeka2Importer\Controller\Index' => 'Omeka2Importer\Controller\IndexController',
        ),
    ),
    'view_manager' => array(
        'template_path_stack'      => array(
            OMEKA_PATH . '/modules/Omeka2Importer/view',
        ),
    ),
    'view_helpers' => array(
        'invokables' => array(
        'resourceClassSelector'    => 'Omeka2Importer\View\Helper\ResourceClassSelector',
        )
    ),
    'entity_manager' => array(
        'mapping_classes_paths' => array(
            OMEKA_PATH . '/modules/Omeka2Importer/src/Entity',
        ),
    ),

    'router' => array(
        'routes' => array(
            'admin' => array(
                'child_routes' => array(
                    'omeka2importer' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/omeka2importer',
                            'defaults' => array(
                                '__NAMESPACE__' => 'Omeka2Importer\Controller',
                                'controller'    => 'Index',
                                'action'        => 'index',
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'past-imports' => array(
                                'type'    => 'Literal',
                                'options' => array(
                                    'route' => '/past-imports',
                                    'defaults' => array(
                                        '__NAMESPACE__' => 'Omeka2Importer\Controller',
                                        'controller'    => 'Index',
                                        'action'        => 'past-imports',
                                    ),
                                )
                            ),
                            'mapping' => array(
                                'type'    => 'Literal',
                                'options' => array(
                                    'route' => '/mapping',
                                    'defaults' => array(
                                        '__NAMESPACE__' => 'Omeka2Importer\Controller',
                                        'controller'    => 'Index',
                                        'action'        => 'mapping',
                                    ),
                                )
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'navigation' => array(
        'admin' => array(
            array(
                'label'      => 'Omeka 2 Importer',
                'route'      => 'admin/omeka2importer',
                'resource'   => 'Omeka2Importer\Controller\Index',
                'pages'      => array(
                    array(
                        'label'      => 'Import',
                        'route'      => 'admin/omeka2importer',
                        'resource'   => 'Omeka2Importer\Controller\Index',
                    ),
                    array(
                        'label'      => 'Past Imports',
                        'route'      => 'admin/omeka2importer/past-imports',
                        'controller' => 'Index',
                        'action'     => 'past-imports',
                        'resource'   => 'Omeka2Importer\Controller\Index',
                    ),
                ),
            ),
        ),
    )
);
