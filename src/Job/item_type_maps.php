<?php

$itemTypeElementMap = array(
    //'Text' Make html media, if type is Text
    'Interviewer'             => 'bibo:interviewer',
    'Interviewee'             => 'bibo:interviewee',
    //'Location'
    //'Transcription'
    //'Local URL'
    'Original Format'         => 'dcterms:format',
    'Physical Dimensions'     => 'dcterms:extent',
    'Duration'                => 'dcterms:extent',
    //'Compression'
    'Producer'                => 'bibo:producer',
    'Director'                => 'bibo:director',
    //'Bit Rate/Frequency'
    //'Time Summary'
    //'Email Body' media?
    //'Subject Line'
    'From'                    => 'bibo:producer',
    'To'                      => 'bibo:recipient',
    'CC'                      => 'bibo:recipient',
    //'BCC'
    //'Number of Attachments'
    //'Standards'
    //'Objectives'
    //'Materials'
    //'Lesson Plan Text' media?
    //'URL'
    //'Event Type'
    //'Participants'
    //'Birth Date'
    //'Birthplace'
    //'Death Date'
    //'Occupation'
    //'Biographical Text'
    //'Bibliography'
);

$itemTypeMap = array(
    'Text'                        => array('class' => 'dctype:Text'), //during import, cached resource class ids will go in 'id' key of array
    'Moving Image'                => array('class' => 'dctype:MovingImage'),
    'Oral History'                => array('class' => 'bibo:AudioDocument'),
    'Sound'                       => array('class' => 'dctype:Sound'),
    'Still Image'                 => array('class' => 'dctype:StillImage'),
    'Website'                     => array('class' => 'bibo:Website'),
    'Event'                       => array('class' => 'dctype:Event'),
    'Email'                       => array('class' => 'bibo:Email'),
    'Lesson Plan'                 => array('class' => 'bibo:Workshop'),
    //'Hyperlink'
    'Person'                      => array('class' => 'foaf:Person'),
    'Interactive Resource'        => array('class' => 'dctype:InteractiveResource'),
    'Dataset'                     => array('class' => 'dctype:Dataset'),
    'Physical Object'             => array('class' => 'dctype:PhysicalObject'),
    'Service'                     => array('class' => 'dctype:Service'),
    'Software'                    => array('class' => 'dctype:Software')
);