<?php

namespace Luigel\AmazonMws;

use Luigel\AmazonMws\AmazonInboundCore;

/**
 * Copyright 2013 CPI Group, LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 *
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 *  Fetches an inbound shipment plan from Amazon.
 *
 * This Amazon Inbound Core object retrieves a newly-generated inbound shipment
 * plan from Amazon using the provided information. In order to generate a
 * shipment plan, an address and a list of items are required.
 */
class AmazonPackageLabels extends AmazonInboundCore
{
    private $i = 0;
    private $pdfDocument;
    private $checksum;

    /**
     * AmazonShipmentPlanner fetches a shipment plan from Amazon. This is how you get a Shipment ID.
     *
     * The parameters are passed to the parent constructor, which are
     * in turn passed to the AmazonCore constructor. See it for more information
     * on these parameters and common methods.
     * @param string $s <p>Name for the store you want to use.</p>
     * @param boolean $mock [optional] <p>This is a flag for enabling Mock Mode.
     * This defaults to <b>FALSE</b>.</p>
     * @param array|string $m [optional] <p>The files (or file) to use in Mock Mode.</p>
     * @param string $config [optional] <p>An alternate config file to set. Used for testing.</p>
     */
    public function __construct($s, $mock = false, $m = null)
    {
        parent::__construct($s, $mock, $m);

        $this->options['Action'] = 'GetPackageLabels';
    }

    /**
     * Sets the shipment id. (Required)
     *
     * This method sets the shipment id to be sent in the next request.
     * This parameter is required for planning a fulfillment order with Amazon.
     * The array provided should have the following fields:
     * <ul>
     * <li><b>Name</b> - max: 50 char</li>
     * <li><b>AddressLine1</b> - max: 180 char</li>
     * <li><b>AddressLine2</b> (optional) - max: 60 char</li>
     * <li><b>City</b> - max: 30 char</li>
     * <li><b>DistrictOrCounty</b> (optional) - max: 25 char</li>
     * <li><b>StateOrProvinceCode</b> (recommended) - 2 digits</li>
     * <li><b>CountryCode</b> - 2 digits</li>
     * <li><b>PostalCode</b> (recommended) - max: 30 char</li>
     * </ul>
     * @param array $a <p>See above.</p>
     * @return boolean <b>FALSE</b> if improper input
     */
    public function setShipmentId($s)
    {
        if (!$s || is_null($s)) {
            $this->log("Tried to set shipment id to invalid values", 'Warning');
            return false;
        }
        $this->options['ShipmentId'] = $s;
    }

    /**
     * Sets the Page Type you want to print the labels on. (Required)
     *
     * This method sets the Page Type to be send
     * as a request parameter.
     * The string provided should have the following values:
     * <ul>
     * <li><b>PackageLabel_Letter_2</b></li>
     * <li><b>PackageLabel_Letter_6</b></li>
     * <li><b>PackageLabel_A4_2</b></li>
     * <li><b>PackageLabel_A4_4</b></li>
     * <li><b>PackageLabel_Plain_Paper</b></li>
     * </ul>
     *
     * @param string $s
     * @return boolean|void
     */
    public function setPageType($s)
    {
        if (!$s || is_null($s)) {
            $this->log("Page Type should be a string.", 'Warning');
            return false;
        }
        $this->options['PageType'] = $s;
    }

    /**
     * Sets the Number of Packages
     *
     * This method sets the Number of Packages to be
     * send to the Amazon Request
     * @param int $i
     * @return boolean|void
     */
    protected function setNumberOfPackages($i)
    {
        if (!is_int($i)) {
            $this->log("Number of Packages should be an int.", 'Warning');
            return false;
        }

        $this->options['NumberOfPackages'] = $i;
    }

    /**
     * Sends a request to Amazon to Get the package labels.
     *
     *
     * @return boolean <b>TRUE</b> if success, <b>FALSE</b> if something goes wrong
     */
    public function fetchLabels()
    {
        if (!array_key_exists('ShipmentId', $this->options)) {
            $this->log("ShipmentId must be set in order to make a package labels", 'Warning');
            return false;
        }
        if (!array_key_exists('PageType', $this->options)) {
            $this->log("PageType must be set in order to make a package labels", 'Warning');
            return false;
        }

        $url = $this->urlbase . $this->urlbranch;

        $query = $this->genQuery();

        $path = $this->options['Action'] . 'Result';
        if ($this->mockMode) {
            $xml = $this->fetchMockFile()->$path->InboundShipmentPlans;
        } else {
            $response = $this->sendRequest($url, array('Post' => $query));

            if (!$this->checkResponse($response)) {
                return false;
            }
            $xml = simplexml_load_string($response['body'])->$path->TransportDocument;
            $this->pdfDocument = $xml->PdfDocument;
            $this->checksum = $xml->CheckSum;
        }
    }

    /**
     * Get the pdf document
     *
     * @param $path
     */
    public function savePdfDocumentZip($path)
    {
        if (!$this->pdfDocument) {
            return false;
        }
        try {
            $zipBase64 = base64_decode($this->pdfDocument);

            $file = $path . uniqid() . '.zip';

            file_put_contents($file, $zipBase64);
            $this->log("Successfully saved Zip PDF Document for Shipment " . $this->options['ShipmentId'] . " at $path");
            return true;
        } catch (Exception $e) {
            $this->log("Unable to save Zip PDF Document for Shipment " . $this->options['ShipmentId'] . " at $path: $e", 'Urgent');
        }
        return false;
    }
}

?>
