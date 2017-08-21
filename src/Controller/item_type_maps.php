<?php

$itemTypeElementMap = array(
    //'Text' Make html media, if type is Text
    'Interviewer'  // @translate
        => 'bibo:interviewer',
    'Interviewee'  // @translate
        => 'bibo:interviewee',
    //'Location'
    //'Transcription'
    //'Local URL'
    'Original Format'  // @translate
        => 'dcterms:format',
    'Physical Dimensions'  // @translate
        => 'dcterms:extent',
    'Duration'  // @translate
        => 'dcterms:extent',
    //'Compression'
    'Producer'  // @translate
        => 'bibo:producer',
    'Director'  // @translate
        => 'bibo:director',
    //'Bit Rate/Frequency'
    //'Time Summary'
    //'Email Body' media?
    //'Subject Line'
    'From'  // @translate
        => 'bibo:producer',
    'To'  // @translate
        => 'bibo:recipient',
    'CC'  // @translate
        => 'bibo:recipient',
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
    'Text'  // @translate
        => 'dctype:Text',
    'Moving Image'  // @translate
        => 'dctype:MovingImage',
    'Oral History'  // @translate
        => 'bibo:AudioDocument',
    'Sound'  // @translate
        => 'dctype:Sound',
    'Still Image'  // @translate
        => 'dctype:StillImage',
    'Website'  // @translate
        => 'bibo:Website',
    'Event'  // @translate
        => 'dctype:Event',
    'Email'  // @translate
        => 'bibo:Email',
    'Lesson Plan'  // @translate
        => 'bibo:Workshop',
    //'Hyperlink'
    'Person'  // @translate
        => 'foaf:Person',
    'Interactive Resource' 
        => 'dctype:InteractiveResource',
    'Dataset'  // @translate
        => 'dctype:Dataset',
    'Physical Object'  // @translate
        => 'dctype:PhysicalObject',
    'Service'  // @translate
        => 'dctype:Service',
    'Software'  // @translate
        => 'dctype:Software',
);
