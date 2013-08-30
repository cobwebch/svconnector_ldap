<?php
namespace Cobweb\SvconnectorLdap\Service;

/***************************************************************
*  Copyright notice
*
*  (c) 2013 Francois Suter (Cobweb) <typo3@cobweb.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service that reads XML feeds for the "svconnector_feed" extension.
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_svconnectorfeed
 *
 * $Id: class.tx_svconnectorfeed_sv1.php 65955 2012-09-10 19:49:38Z francois $
 */
class Connector extends \tx_svconnector_base {
	public $extKey = 'svconnector_ldap';	// The extension key.
	protected $extConf; // Extension configuration

	/**
	 * Verifies that the connection is functional
	 * In the case of this service, it is always the case
	 * It might fail for a specific file, but it is always available in general
	 *
	 * @return	boolean		TRUE if the service is available
	 */
	public function init() {
		parent::init();
		$this->lang->includeLLFile('EXT:' . $this->extKey . '/Resources/Private/Language/Connector.xlf');
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
		if (function_exists('ldap_connect')) {
			return TRUE;
		} else {

			if (TYPO3_DLOG || $this->extConf['debug']) {
				GeneralUtility::devLog(
					'PHP LDAP library is not available',
					$this->extKey,
					3
				);
			}
			return FALSE;
		}
	}

	/**
	 * Establishes the connection to the LDAP server
	 *
	 * @param array $parameters Connection parameters
	 * @return resource LDAP connection
	 * @throws \Exception
	 */
	protected function connectToLdapServer($parameters) {

		// Connect to the server and set communication options
		if (empty($parameters['host'])) {
			throw new \Exception('No LDAP host defined', 1377872114);
		}

		$port = (empty($parameters['port'])) ? 389 : intval($parameters['port']);
		$ldapConnection = @ldap_connect($parameters['host'], $port);
		if (!empty($parameters['protocol'])) {
			@ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, $parameters['protocol']) ;
		}

		// Try binding with the given user name and password (if any; not needed for public servers)
		$ldapResult = @ldap_bind(
			$ldapConnection,
			(empty($parameters['login'])) ? null : $parameters['login'],
			(empty($parameters['password'])) ? null : $this->extConf['password']
		);
		if ($ldapResult) {
			return $ldapConnection;
		} else {
			throw new \Exception('Could not connect to LDAP server', 1377871909);
		}
	}

	/**
	 * This method calls the query method and returns the result as is,
	 * i.e. the XML from the feed, but without any additional work performed on it
	 *
	 * @param	array	$parameters: parameters for the call
	 * @return	mixed	server response
	 */
	public function fetchRaw($parameters) {
		$result = $this->query($parameters);
			// Implement post-processing hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processRaw'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processRaw'] as $className) {
				$processor = GeneralUtility::getUserObj($className);
				$result = $processor->processRaw($result, $this);
			}
		}

		return $result;
	}

	/**
	 * This method calls the query and returns the results from the response as an XML structure
	 *
	 * @param	array	$parameters: parameters for the call
	 * @return	string	XML structure
	 */
	public function fetchXML($parameters) {
			// Get the data and convert it to XML
		$result = $this->query($parameters);
		$xml = GeneralUtility::array2xml_cs($result);
			// Implement post-processing hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processXML'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processXML'] as $className) {
				$processor = GeneralUtility::getUserObj($className);
				$xml = $processor->processXML($xml, $this);
			}
		}

		return $xml;
	}

	/**
	 * This method calls the query and returns the results from the response as a PHP array
	 *
	 * @param	array	$parameters: parameters for the call
	 *
	 * @return	array	PHP array
	 */
	public function fetchArray($parameters) {
		// Get the data from the file
		$result = $this->query($parameters);

		if (TYPO3_DLOG || $this->extConf['debug']) {
			GeneralUtility::devLog('Structured data', $this->extKey, -1, $result);
		}

		// Implement post-processing hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processArray'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processArray'] as $className) {
				$processor = GeneralUtility::getUserObj($className);
				$result = $processor->processArray($result, $this);
			}
		}
		return $result;
	}

	/**
	 * This method reads the content of the XML feed defined in the parameters
	 * and returns it as an array
	 *
	 * NOTE:    this method does not implement the "processParameters" hook,
	 *          as it does not make sense in this case
	 *
	 * @param array $parameters Parameters for the call
	 * @throws \Exception
	 * @return array Content of the feed
	 */
	protected function query($parameters) {

		if (TYPO3_DLOG || $this->extConf['debug']) {
			GeneralUtility::devLog('Call parameters', $this->extKey, -1, $parameters);
		}
		// Check mandatory parameters
		if (empty($parameters['search_base'])) {
			throw new \Exception('No base DN defined for search', 1377875840);
		}
		if (empty($parameters['search_base'])) {
			throw new \Exception('No search filter defined', 1377875868);
		}

		// Connect to the LDAP server. This may throw an exception but we let it bubble up.
		$ldapConnection = $this->connectToLdapServer($parameters);
		// Prepare the attributes parameters
		if (!empty($parameters['search_attributes'])) {
			$attributes = GeneralUtility::trimExplode(
				',',
				$parameters['search_attributes'],
				TRUE
			);
		} else {
			$attributes = array();
		}
		// Perform the search
		$ldapResult = @ldap_search(
			$ldapConnection,
			$parameters['search_base'],
			$parameters['search_filter'],
			$attributes,
			(empty($parameters['search_attributes_only'])) ? 0 : 1,
			(empty($parameters['search_size_limit'])) ? 0 : intval($parameters['search_size_limit']),
			(empty($parameters['search_time_limit'])) ? 0 : intval($parameters['search_time_limit']),
			(empty($parameters['search_deref'])) ? 0 : constant($parameters['search_deref'])
		);
		$data = @ldap_get_entries($ldapConnection, $ldapResult);

			// Process the result if any hook is registered
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processResponse'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processResponse'] as $className) {
				$processor = GeneralUtility::getUserObj($className);
				$data = $processor->processResponse($data, $this);
			}
		}

			// Return the result
		return $data;
	}
}

?>