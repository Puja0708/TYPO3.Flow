<?php

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Bootstrap for the command line
 */

if (PHP_SAPI !== 'cli') {
	echo(sprintf("The FLOW3 command line script or sub process was executed with a '%s' PHP binary. Make sure that you specified a CLI capable PHP binary in your PATH or FLOW3's Settings.yaml.", PHP_SAPI) . PHP_EOL);
	exit(1);
}

if (isset($argv[1]) && ($argv[1] === 'typo3.flow3:core:setfilepermissions' || $argv[1] === 'flow3:core:setfilepermissions' || $argv[1] === 'core:setfilepermissions')) {
	if (DIRECTORY_SEPARATOR !== '/') {
		exit('The core:setfilepermissions command is only available on UNIX platforms.' . PHP_EOL);
	}
	array_shift($argv);
	array_shift($argv);
	$returnValue = 0;
	system(__DIR__ . '/setfilepermissions.sh ' . implode($argv, ' '), $returnValue);
	exit($returnValue);
} elseif (isset($argv[1]) && ($argv[1] === 'typo3.flow3:core:migrate' || $argv[1] === 'flow3:core:migrate' || $argv[1] === 'core:migrate')) {
	array_shift($argv);
	array_shift($argv);
	require(__DIR__ . '/migrate.php');
} else {
	require(__DIR__ . '/../Classes/Core/Bootstrap.php');

	$context = trim(getenv('FLOW3_CONTEXT'), '"\' ') ?: 'Development';
	$_SERVER['FLOW3_ROOTPATH'] = trim(getenv('FLOW3_ROOTPATH'), '"\' ') ?: '';

	$bootstrap = new \TYPO3\FLOW3\Core\Bootstrap($context);
	$bootstrap->run();
}

?>