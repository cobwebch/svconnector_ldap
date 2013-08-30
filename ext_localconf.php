<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
	$_EXTKEY,
	// Service type
	'connector',
	// Service key
	'tx_svconnectorldap_service',
	// Service configuration
	array(

		'title' => 'LDAP connector',
		'description' => 'Connector service for LDAP servers',

		'subtype' => 'ldap',

		'available' => TRUE,
		'priority' => 50,
		'quality' => 50,

		'os' => '',
		'exec' => '',

		'classFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(
			$_EXTKEY,
			'Classes/Service/Connector.php'
		),
		'className' => 'Cobweb\\SvconnectorLdap\\Service\\Connector',
	)
);
?>