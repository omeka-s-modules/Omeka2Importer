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
    'Text'                        => 'dctype:Text',
    'Moving Image'                => 'dctype:MovingImage',
    'Oral History'                => 'bibo:AudioDocument',
    'Sound'                       => 'dctype:Sound',
    'Still Image'                 => 'dctype:StillImage',
    'Website'                     => 'bibo:Website',
    'Event'                       => 'dctype:Event',
    'Email'                       => 'bibo:Email',
    'Lesson Plan'                 => 'bibo:Workshop',
    //'Hyperlink'
    'Person'                      => 'foaf:Person',
    'Interactive Resource'        => 'dctype:InteractiveResource',
    'Dataset'                     => 'dctype:Dataset',
    'Physical Object'             => 'dctype:PhysicalObject',
    'Service'                     => 'dctype:Service',
    'Software'                    => 'dctype:Software'
);