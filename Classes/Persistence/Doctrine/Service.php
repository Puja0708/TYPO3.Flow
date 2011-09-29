<?php
namespace TYPO3\FLOW3\Persistence\Doctrine;

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
 * Service class for tasks related to Doctrine
 *
 * @scope singleton
 */
class Service {

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @var array
	 */
	public $output = array();

	/**
	 * @inject
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * Injects the FLOW3 settings, the persistence part is kept
	 * for further use.
	 *
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings['persistence'];
	}

	/**
	 * Validates the metadata mapping for Doctrine, using the SchemaValidator
	 * of Doctrine.
	 *
	 * @return array
	 */
	public function validateMapping() {
		$result = array();
		try {
			$validator = new \Doctrine\ORM\Tools\SchemaValidator($this->entityManager);
			$result = $validator->validateMapping();
		} catch (\Exception $exception) {
			$result[] = $exception->getMessage();
		}
		return $result;
	}

	/**
	 * Creates the needed DB schema using Doctrine's SchemaTool. If tables already
	 * exist, this will thow an exception.
	 *
	 * @param string $outputPathAndFilename A file to write SQL to, instead of executing it
	 * @return string
	 */
	public function createSchema($outputPathAndFilename = NULL) {
		$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
		if ($outputPathAndFilename === NULL) {
			$schemaTool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
		} else {
			file_put_contents($outputPathAndFilename, implode(PHP_EOL, $schemaTool->getCreateSchemaSql($this->entityManager->getMetadataFactory()->getAllMetadata())));
		}
	}

	/**
	 * Updates the DB schema using Doctrine's SchemaTool. The $safeMode flag is passed
	 * to SchemaTool unchanged.
	 *
	 * @param boolean $safeMode
	 * @param string $outputPathAndFilename A file to write SQL to, instead of executing it
	 * @return string
	 */
	public function updateSchema($safeMode = TRUE, $outputPathAndFilename = NULL) {
		$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
		if ($outputPathAndFilename === NULL) {
			$schemaTool->updateSchema($this->entityManager->getMetadataFactory()->getAllMetadata(), $safeMode);
		} else {
			file_put_contents($outputPathAndFilename, implode(PHP_EOL, $schemaTool->getUpdateSchemaSql($this->entityManager->getMetadataFactory()->getAllMetadata(), $safeMode)));
		}
	}

	/**
	 * Compiles the Doctrine proxy class code using the Doctrine ProxyFactory.
	 *
	 * @return void
	 */
	public function compileProxies() {
		$proxyFactory = $this->entityManager->getProxyFactory();
		$proxyFactory->generateProxyClasses($this->entityManager->getMetadataFactory()->getAllMetadata());
	}

	/**
	 * Returns information about which entities exist and possibly if their
	 * mapping information contains errors or not.
	 *
	 * @return array
	 */
	public function getEntityStatus() {
		$entityClassNames = $this->entityManager->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
		$info = array();
		foreach ($entityClassNames as $entityClassName) {
			try {
				$this->entityManager->getClassMetadata($entityClassName);
				$info[$entityClassName] = TRUE;
			} catch (\Doctrine\ORM\Mapping\MappingException $e) {
				$info[$entityClassName] = $e->getMessage();
			}
		}

		return $info;
	}

	/**
	 * Run DQL and return the result as-is.
	 *
	 * @param string $dql
	 * @param integer $hydrationMode
	 * @param integer $firstResult
	 * @param integer $maxResult
	 * @return mixed
	 */
	public function runDql($dql, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT, $firstResult = NULL, $maxResult = NULL) {
		$query = $this->entityManager->createQuery($dql);
		if ($firstResult !== NULL){
			$query->setFirstResult($firstResult);
		}
		if ($maxResult !== NULL) {
			$query->setMaxResults($maxResult);
		}

		return $query->execute(array(), constant($hydrationMode));
	}

	/**
	 * Return the configuration needed for Migrations.
	 *
	 * @return \Doctrine\DBAL\Migrations\Configuration\Configuration
	 */
	protected function getMigrationConfiguration() {
		$this->output = array();
		$that = $this;
		$outputWriter = new \Doctrine\DBAL\Migrations\OutputWriter(
			function ($message) use ($that) {
				$that->output[] = $message;
			}
		);

		$configuration = new \Doctrine\DBAL\Migrations\Configuration\Configuration($this->entityManager->getConnection(), $outputWriter);
		$configuration->setMigrationsNamespace('TYPO3\FLOW3\Persistence\Doctrine\Migrations');
		$configuration->setMigrationsDirectory(\TYPO3\FLOW3\Utility\Files::concatenatePaths(array(FLOW3_PATH_DATA, 'DoctrineMigrations')));
		$configuration->setMigrationsTableName('flow3_doctrine_migrationstatus');

		$configuration->createMigrationTable();

		$databasePlatformName = ucfirst($this->entityManager->getConnection()->getDatabasePlatform()->getName());
		foreach ($this->packageManager->getActivePackages() as $package) {
			$configuration->registerMigrationsFromDirectory(
				\TYPO3\FLOW3\Utility\Files::concatenatePaths(array(
					 $package->getPackagePath(),
					 'Migrations',
					 $databasePlatformName
				))
			);
		}

		return $configuration;
	}

	/**
	 * Returns the current migration status formatted as plain text.
	 *
	 * @return string
	 */
	public function getMigrationStatus() {
		$configuration = $this->getMigrationConfiguration();

		$currentVersion = $configuration->getCurrentVersion();
		if ($currentVersion) {
			$currentVersionFormatted = $configuration->formatVersion($currentVersion) . ' ('.$currentVersion.')';
		} else {
			$currentVersionFormatted = 0;
		}
		$latestVersion = $configuration->getLatestVersion();
		if ($latestVersion) {
			$latestVersionFormatted = $configuration->formatVersion($latestVersion) . ' ('.$latestVersion.')';
		} else {
			$latestVersionFormatted = 0;
		}
		$executedMigrations = $configuration->getNumberOfExecutedMigrations();
		$availableMigrations = $configuration->getNumberOfAvailableMigrations();
		$newMigrations = $availableMigrations - $executedMigrations;

		$output = "\n == Configuration\n";

		$info = array(
			'Name'                  => $configuration->getName() ? $configuration->getName() : 'Doctrine Database Migrations',
			'Database Driver'       => $configuration->getConnection()->getDriver()->getName(),
			'Database Name'         => $configuration->getConnection()->getDatabase(),
			'Configuration Source'  => $configuration instanceof \Doctrine\DBAL\Migrations\Configuration\AbstractFileConfiguration ? $configuration->getFile() : 'manually configured',
			'Version Table Name'    => $configuration->getMigrationsTableName(),
			'Migrations Namespace'  => $configuration->getMigrationsNamespace(),
			'Migrations Target Directory'  => $configuration->getMigrationsDirectory(),
			'Current Version'       => $currentVersionFormatted,
			'Latest Version'        => $latestVersionFormatted,
			'Executed Migrations'   => $executedMigrations,
			'Available Migrations'  => $availableMigrations,
			'New Migrations'        => $newMigrations
		);
		foreach ($info as $name => $value) {
			$output .= '    >> ' . $name . ': ' . str_repeat(' ', 50 - strlen($name)) . $value . "\n";
		}

		if ($migrations = $configuration->getMigrations()) {
			$output .= "\n == Migration Versions\n";
			foreach ($migrations as $version) {
				$status = $version->isMigrated() ? 'migrated' : "not migrated\n";
				$output .= '    >> ' . $configuration->formatVersion($version->getVersion()) . ' (' . $version->getVersion() . ')' . str_repeat(' ', 30 - strlen($name)) . $status . "\n";
			}
		}

		return $output;
	}

	/**
	 * Execute all new migrations, up to $version if given.
	 *
	 * If $outputPathAndFilename is given, the SQL statements will be written to the given file instead of executed.
	 *
	 * @param string $version The version to migrate to
	 * @param string $outputPathAndFilename A file to write SQL to, instead of executing it
	 * @param boolean $dryRun Whether to do a dry run or not
	 * @return string
	 */
	public function executeMigrations($version = NULL, $outputPathAndFilename = NULL, $dryRun = FALSE) {
		$configuration = $this->getMigrationConfiguration();
		$migration = new \Doctrine\DBAL\Migrations\Migration($configuration);

		if ($outputPathAndFilename !== NULL) {
			$migration->writeSqlFile($outputPathAndFilename, $version);
		} else {
			$migration->migrate($version, $dryRun);
		}
		return strip_tags(implode(PHP_EOL, $this->output));
	}

	/**
	 * Execute a single migration in up or down direction. If $path is given, the
	 * SQL statements will be writte to the file in $path instead of executed.
	 *
	 * @param string $version The version to migrate to
	 * @param string $direction
	 * @param string $outputPathAndFilename A file to write SQL to, instead of executing it
	 * @param boolean $dryRun Whether to do a dry run or not
	 * @return string
	 */
	public function executeMigration($version, $direction = 'up', $outputPathAndFilename = NULL, $dryRun = FALSE) {
		$version = $this->getMigrationConfiguration()->getVersion($version);

		if ($outputPathAndFilename !== NULL) {
			$version->writeSqlFile($outputPathAndFilename, $direction);
		} else {
			$version->execute($direction, $dryRun);
		}
		return strip_tags(implode(PHP_EOL, $this->output));
	}

	/**
	 * Add a migration version to the migrations table or remove it.
	 *
	 * This does not execute any migration code but simply records a version
	 * as migrated or not.
	 *
	 * @param string $version The version to add or remove
	 * @param boolean $markAsMigrated
	 * @return void
	 * @throws \Doctrine\DBAL\Migrations\MigrationException
	 * @throws \LogicException
	 */
	public function markAsMigrated($version, $markAsMigrated) {
		$configuration = $this->getMigrationConfiguration();

		if ($configuration->hasVersion($version) === FALSE) {
			throw \Doctrine\DBAL\Migrations\MigrationException::unknownMigrationVersion($version);
		}

		$version = $configuration->getVersion($version);
		if ($markAsMigrated === TRUE && $configuration->hasVersionMigrated($version) === TRUE) {
			throw new \LogicException(sprintf('The version "%s" already exists in the version table.', $version));
		}

		if ($markAsMigrated === FALSE && $configuration->hasVersionMigrated($version) === FALSE) {
			throw new \LogicException(sprintf('The version "%s" does not exists in the version table.', $version));
		}

		if ($markAsMigrated === TRUE) {
			$version->markMigrated();
		} else {
			$version->markNotMigrated();
		}
	}
	/**
	 * Generates a new migration file and returns the path to it.
	 *
	 * If $diffAgainstCurrent is TRUE, it generates a migration file with the
	 * diff between current DB structure and the found mapping metadata.
	 *
	 * Otherwise an empty migration skeleton is generated.
	 *
	 * @param boolean $diffAgainstCurrent
	 * @return string Path to the new file
	 */
	public function generateMigration($diffAgainstCurrent = TRUE) {
		$configuration = $this->getMigrationConfiguration();
		$up = NULL;
		$down = NULL;

		if ($diffAgainstCurrent === TRUE) {
			$connection = $this->entityManager->getConnection();
			$platform = $connection->getDatabasePlatform();
			$metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

			if (empty($metadata)) {
				return 'No mapping information to process.';
			}

			$tool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);

			$fromSchema = $connection->getSchemaManager()->createSchema();
			$toSchema = $tool->getSchemaFromMetadata($metadata);
			$up = $this->buildCodeFromSql($configuration, $fromSchema->getMigrateToSql($toSchema, $platform));
			$down = $this->buildCodeFromSql($configuration, $fromSchema->getMigrateFromSql($toSchema, $platform));

			if (!$up && !$down) {
				return 'No changes detected in your mapping information.';
			}
		}

		return $this->writeMigrationClassToFile($configuration, $up, $down);
	}

	/**
	 * @param \Doctrine\DBAL\Migrations\Configuration\Configuration $configuration
	 * @param string $up
	 * @param string $down
	 * @return string
	 * @throws \RuntimeException
	 */
	protected function writeMigrationClassToFile(\Doctrine\DBAL\Migrations\Configuration\Configuration $configuration, $up, $down) {
		$namespace = $configuration->getMigrationsNamespace();
		$className = 'Version' . date('YmdHis');
		$up = $up === NULL ? '' : "\n		" . implode("\n		", explode("\n", $up));
		$down = $down === NULL ? '' : "\n		" . implode("\n		", explode("\n", $down));

		$path = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($configuration->getMigrationsDirectory(), $className . '.php'));
		try {
			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively(dirname($path));
		} catch (\TYPO3\FLOW3\Utility\Exception $exception) {
			throw new \RuntimeException(sprintf('Migration target directory "%s" does not exist.', dirname($path)), 1303298536, $exception);
		}

		$code = <<<EOT
<?php
namespace $namespace;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class $className extends AbstractMigration {

	/**
	 * @param Schema \$schema
	 * @return void
	 */
	public function up(Schema \$schema) {
			// this up() migration is autogenerated, please modify it to your needs$up
	}

	/**
	 * @param Schema \$schema
	 * @return void
	 */
	public function down(Schema \$schema) {
			// this down() migration is autogenerated, please modify it to your needs$down
	}
}

?>
EOT;
		file_put_contents($path, $code);

		return $path;
	}

	/**
	 * Returns PHP code for a migration file that "executes" the given
	 * array of SQL statements.
	 *
	 * @param \Doctrine\DBAL\Migrations\Configuration\Configuration $configuration
	 * @param array $sql
	 * @return string
	 */
	protected function buildCodeFromSql(\Doctrine\DBAL\Migrations\Configuration\Configuration $configuration, array $sql) {
		$currentPlatform = $configuration->getConnection()->getDatabasePlatform()->getName();
		$code = array(
			"\$this->abortIf(\$this->connection->getDatabasePlatform()->getName() != \"$currentPlatform\");", "",
		);
		foreach ($sql as $query) {
			if (strpos($query, $configuration->getMigrationsTableName()) !== FALSE) {
				continue;
			}
			$code[] = "\$this->addSql(\"$query\");";
		}
		return implode("\n", $code);
	}

}

?>