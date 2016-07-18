<?php

/**
 * Plugin Name: Salesforce CRM Integration
 * Description: This plugin handles the integration of Wordpress forms with Salesforce CRM
 * Version: 1.0
 * Author: Jason McMaster
 */

require_once ('libs/soapclient/SforceEnterpriseClient.php');

class WordpressSalesforceIntegration {

    public $record_type = 'Lead';

    private $settings = [
        "USERNAME"       => "username",
        "PASSWORD"       => "password",
        "SECURITY_TOKEN" => "token",
        "WSDL"           => "wp-content/plugins/salesforce_integration/filename.wsdl.xml",
    ];

    public function __construct() {
        // Define Settings
        define("USERNAME", $this->settings["USERNAME"]);
        define("PASSWORD", $this->settings["PASSWORD"]);
        define("SECURITY_TOKEN", $this->settings["SECURITY_TOKEN"]);
        define("WSDL", $this->settings["WSDL"]);

        $this->mySforceConnection = new SforceEnterpriseClient();
        $this->mySforceConnection->createConnection(WSDL);
        $this->mySforceConnection->login(USERNAME, PASSWORD.SECURITY_TOKEN);
    }

    public function prepareData($data) {
        // Set form type
        $this->type = $data['form_type'];

        $records = array();
        $records[0] = new stdclass();

        $records[0]->FirstName = $data['first_name'];
        $records[0]->LastName = $data['last_name'];
        $records[0]->Email = strtolower($data['email']);
        $records[0]->LeadSource = 'Web';
        $records[0]->Company = 'None Provided';

        // if you are updating a Lead, pass in the Lead ID
        if ( $this->type == 'update') {
            $records[0]->Id = $data['id'];
        }

        // Conditional fields
        if ( $this->type == 'full' || $this->type == 'update' ) {

            if (isset($data['other_title']) && $data['other_title'] !== ''){
                $records[0]->School_Title__c = $data['other_title'];
            } else {
                if (isset($data['school_title']) && $data['school_title'] !== ''){
                    $records[0]->School_Title__c = $data['school_title'];
                }
            }

            if (isset($data['organization_type'])) {
                $records[0]->Organization_Type__c = $data['organization_type'];
            }

            if (isset($data['company'])) {
                $records[0]->Company = $data['company'];
            }
            if (isset($data['city'])){
                $records[0]->City = $data['city'];
            }
            if (isset($data['state'])) {
                $records[0]->State = $data['state'];
            }

            if (isset($data['city']) && isset($data['state'])) {
                $records[0]->Street = '';
                $records[0]->Country = '';
                $records[0]->PostalCode = '';
            }
        }

        return $records;
    }

    public function sendToSalesforce($records) {
        if ($this->type == 'update') {
            $response = $this->mySforceConnection->update($records, $this->record_type);
        } else {
            $response = $this->mySforceConnection->create($records, $this->record_type);
        }

        return $response;
    }
}
