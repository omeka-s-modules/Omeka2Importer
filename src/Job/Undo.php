<?php
namespace Omeka2Importer\Job;

use Omeka\Job\AbstractJob;

class Undo extends AbstractJob
{
    public function perform()
    {
        $jobId = $this->getArg('jobId');
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('omekaimport_records', array('job_id' => $jobId, 'remote_type' => 'item'));
        $omeka2Items = $response->getContent();
        if ($omeka2Items) {
            foreach ($omeka2Items as $omeka2Item) {
                $omeka2Response = $api->delete('omekaimport_records', $omeka2Item->id());
                if ($omeka2Response->isError()) {
                }

                $itemResponse = $api->delete('items', $omeka2Item->item()->id());
                if ($itemResponse->isError()) {
                }
            }
        }
    }
}