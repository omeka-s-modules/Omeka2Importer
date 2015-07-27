<?php
namespace Omeka2Importer\Job;

use Omeka\Job\AbstractJob;

class Undo extends AbstractJob
{
    public function perform()
    {
        $jobId = $this->getArg('jobId');
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('omeka2items', array('job_id' => $jobId));
        $omeka2Items = $response->getContent();
        if ($omeka2Items) {
            foreach ($omeka2Items as $omeka2Item) {
                $omeka2Response = $api->delete('omeka2items', $omeka2Item->id());
                if ($omeka2Response->isError()) {
                }

                $itemResponse = $api->delete('items', $omeka2Item->item()->id());
                if ($itemResponse->isError()) {
                }
            }
        }
    }
}