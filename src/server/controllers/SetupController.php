<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
   2007-2018 Christian Boulanger

   License:
   LGPL: http://www.gnu.org/licenses/lgpl.html
   EPL: http://www.eclipse.org/org/documents/epl-v10.php
   See the LICENSE file in the project's top-level directory for details.

   Authors:
   * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\controllers;


use app\models\BibliographicDatasource;
use app\models\Datasource;
use Yii;
use yii\db\Exception;
use Stringy\Stringy;
use app\models\Schema;
use lib\components\MigrationException;
use lib\dialog\{
  Error as ErrorDialog, Confirm, Login, Popup
};
use lib\exceptions\{
  RecordExistsException, SetupException, UserErrorException
};
use lib\components\ConsoleAppHelper as Console;
use lib\Module;
use yii\helpers\Html;


/**
 * Setup controller. Needs to be the first controller called
 * by the application after loading
 */
class SetupController extends \app\controllers\AppController
{
  /**
   * The name of the default datasource schema.
   * Initial value of the app.datasource.baseschema preference.
   * @todo remove
   */
  const DATASOURCE_DEFAULT_SCHEMA = BibliographicDatasource::SCHEMA_ID;

  /**
   * The name of the default bibliographic datasource class.
   * Initial value of the app.datasource.baseclass preference.
   * @todo remove
   */
  const DATASOURCE_DEFAULT_CLASS = \app\models\BibliographicDatasource::class;

  /**
   * @inheritDoc
   *
   * @var array
   */
  protected $noAuthActions = ["setup", "version", "setup-version","continue","finish"];

  protected $errors = [];

  protected $messages = [];

  /**
   * Whether we have an ini file
   */
  protected $hasIni;

  /**
   * Whether we have a db connection
   */
  protected $hasDb;

  /**
   * Whether the user has confirmed to run the migrations
   */
  protected $migrationConfirmed = false;

  /**
   * Whether this is a new install of bibliograph without existing data.
   * @var int
   */
  protected $isNewInstallation;

  /**
   * Whether we upgrade from legacy version v2
   * @var 
   */
  protected $isV2Upgrade = false;


  /**
   * Whether one of the modules was upgraded
   * @var bool
   */
  protected $moduleUpgrade = false;

  //-------------------------------------------------------------
  // ACTIONS
  //-------------------------------------------------------------  

  /**
   * Returns the application verision as per package.json
   */
  public function actionVersion()
  {
    return Yii::$app->utils->version;
  }

  /**
   * Called by the confirm dialog
   * @see actionSetup()
   */
  public function actionConfirmMigrations()
  {
    $this->migrationConfirmed = true;
    return $this->actionSetup();
  }

  /**
   * The setup action. Is called as first server method from the client
   */
  public function actionSetup()
  {
    return $this->_setup();
  }

  /**
   * A setup a specific version of the application. This is mainly for testing.
   * @param string $upgrade_to (optional) The version to upgrade from.
   * @param string $upgrade_from (optional) The version to upgrade to.
   */
  public function actionSetupVersion($upgrade_to, $upgrade_from = null)
  {
    if (!YII_ENV_TEST) {
      throw new \BadMethodCallException('setup/setup-version can only be called in test mode.');
    }
    return $this->_setup($upgrade_to, $upgrade_from);
  }

  /**
   * The setup action. Is called as first server method from the client
   * @param string $upgrade_to (optional) The version to upgrade to. You should not set
   * this parameter unless you know what you are doing.
   * @param string $upgrade_from (optional) The version to upgrade from.You should not set
   * this parameter unless you know what you are doing.
   * @return array
   */
  protected function _setup($upgrade_to = null, $upgrade_from = null)
  {
    // this throws if ini file doesn't exist
    $this->checkIfIniFileExists();
    if (!$upgrade_from) {
      try {
        $upgrade_from = Yii::$app->config->getKey('app.version');
      } catch (\InvalidArgumentException $e) {
        // upgrading from version 2 where the config key doesn't exist
        $upgrade_from = "2.x";
        $this->isV2Upgrade = true;
      } catch (\yii\db\Exception $e) {
        // no tables exist yet, this is the first run of a fresh installation
        $upgrade_from = "0.0.0";
        $this->isNewInstallation = true;
      } catch (yii\base\InvalidConfigException $e) {
        // this happens deleting the tables in the database during development
        // @todo 
        $upgrade_from = "0.0.0";
        $this->isNewInstallation = true;
      }
    }
    if (!$upgrade_to) {
      $upgrade_to = Yii::$app->utils->version;
    }
    // @todo validate

    // if application version has changed or first run, run setup methods
    Yii::debug("Data version: $upgrade_from, code version: $upgrade_to.", __METHOD__);
    // visual marker in log file
    Yii::debug(
      "\n\n\n" . str_repeat("*", 80) . "\n\n BIBLIOGRAPH SETUP\n\n" . str_repeat("*", 80) . "\n\n\n",
      'marker'
    );
    // if version has changed and we're not in a test, run setup asynchronously to give the user a better feedback
    $runAsync = false;
    if ($upgrade_to !== $upgrade_from) {
      Yii::info("Application version has changed from '$upgrade_from' to '$upgrade_to'.");
      $runAsync = !YII_ENV_TEST;
    }
    if( $runAsync) {
      Yii::debug("Running async setup...", __METHOD__);
      return $this->runSetupMethodsAsync($upgrade_from, $upgrade_to);
    }
    // run methods. If any of them returns a fatal error, abort and alert the user
    // if any non-fatal errors occur, collect them and display them to the user at the
    // end, then consider setup unsuccessful
    Yii::debug("Running sync setup...", __METHOD__);
    $success = $this->runSetupMethodsSync($upgrade_from, $upgrade_to);
    if ($success) {
      $upgrade_from = $upgrade_to;
      Yii::info("Setup of version '$upgrade_from' finished successfully.");
      if (!Yii::$app->config->keyExists('app.version')) {
        // createKey( $key, $type, $customize=false, $default=null, $final=false )
        Yii::$app->config->createKey('app.version', 'string', false, null, false);
      }
      Yii::$app->config->setKeyDefault('app.version', $upgrade_from);
    } else {
      $message = "Setup of version '$upgrade_to' failed.";
      $data = [
        'errors'   => $this->errors,
        'messages' => $this->messages
      ];
      Yii::warning($message);
      Yii::debug($data, __METHOD__);
      return $data;
    }
    // notify client that setup it done
    $this->dispatchClientMessage("ldap.enabled", Yii::$app->config->getIniValue("ldap.enabled"));
    $this->dispatchClientMessage("bibliograph.setup.done"); // @todo rename
    // return errors and messages
    return [
      'errors' => $this->errors,
      'messages' => $this->messages
    ];
  }

  /**
   * Run all existing setup methods (methods of this class that have the prefix 'setup')
   * synchroneously (i.e. for tests).
   * Each method must be executable regardless of application setup state and will be called
   * with the same parameters as this method. The must return one of these value types:
   *
   * - false : do nothing
   * - [ 'fatalError'] => "Fatal error message" ] This will end the setup process and alert a
   *   message to the user
   * - [ 'error' => "Error message", 'message' => "Result message' ] This will let the setup
   *   process continue. Messages are stored in the 'messages' member property, errors in the
   *   'error' member property of this object
   *
   * @param string $upgrade_from
   *    The current version of the application as stored in the database
   * @param string $upgrade_to The version in package.json, i.e. of the code, which can be
   *    higher than the
   * @return bool
   */
  protected function runSetupMethodsSync($upgrade_from, $upgrade_to)
  {
    // compile list of setup methods
    foreach (\get_class_methods($this) as $method) {
      if ( starts_with($method,"setup")) {
        Yii::debug("Calling method '$method'...", __METHOD__);
        try {
          $result = $this->$method($upgrade_from, $upgrade_to);
        } catch (SetupException $e) {
          ErrorDialog::create($e->getMessage());
          Yii::error("Setup exception: " . $e->getMessage());
          // @todo deal with diagnostic output
          return false;
        } catch (\Exception $e) {
          ErrorDialog::create($e->getMessage());
          Yii::error($e);
          return false;
        }
        if (!$result) {
          Yii::debug("Skipping method '$method'...", __METHOD__);
          continue;
        }
        // @todo replace with SetupException
        if (isset($result['fatalError'])) {
          $fatalError = $result['fatalError'];
          Yii::error($fatalError);
          ErrorDialog::create($fatalError);
          return false;
        }
        if (isset($result['error'])) {
          foreach ( (array) $result['error'] as $error) {
            $this->errors[] = "$method: $error";
          }
        }
        if (isset($result['message'])) {
          $this->messages = array_merge( $this->messages, (array) $result['message']);
        }
      }
    }
    if (count($this->errors)) {
      Yii::warning("Setup finished with errors:");
      Yii::warning($this->errors);
      $msg = Html::tag('b', Yii::t('setup', 'Setup failed. Please fix the following problems:'));
      ErrorDialog::create(\implode('<br>', $this->errors));
      return false;
    }
    // Everything seems to be ok
    Yii::debug("Setup finished successfully.", __METHOD__);
    Yii::debug($this->messages, __METHOD__);
    return true;
  }

  /**
   * Run all existing setup methods asychronously, using a popup to indicate progress
   *
   * @param string $upgrade_from
   *    The current version of the application as stored in the database
   * @param string $upgrade_to The version in package.json, i.e. of the code, which can be
   *    higher than the
   * @return array Diagnostic data
   */
  protected function runSetupMethodsAsync($upgrade_from, $upgrade_to)
  {
    $testMethods = array_filter(\get_class_methods($this), function($method){
      return starts_with($method,"setup");
    });
    $steps = count($testMethods);
    $shelfId = $this->shelve($testMethods, $upgrade_from, $upgrade_to, [], [], 1, $steps);
    (new Popup())
      ->setMessage(Yii::t(self::CATEGORY, "Starting setup..."))
      ->setRoute("setup/continue")
      ->setParams([$shelfId])
      ->sendToClient();
    return [
      'message' => "Started 1/$steps setup methods"
    ];
  }

  /**
   * Execute next setup method
   * @param string $shelfId
   * @return string diagnostic message
   */
  public function actionContinue($dummy,$shelfId)
  {
    list( $methods, $upgrade_from, $upgrade_to, $errors, $messages, $step, $steps) = $this->unshelve($shelfId);
    $method = array_shift($methods);
    Yii::debug("Calling test method '$method'...", __METHOD__);
    try {
      $result = $this->$method($upgrade_from, $upgrade_to);
    } catch (SetupException $e) {
      ErrorDialog::create($e->getMessage());
      Yii::error("Setup exception: " . $e->getMessage());
      // @todo deal with diagnostic output
      return $e->getMessage();
    } catch (\Exception $e) {
      ErrorDialog::create($e->getMessage());
      Yii::error($e);
      return $e->getMessage();
    }
    $message="";
    if (!$result) {
      Yii::debug("Skipping method '$method'...", __METHOD__);
    } else {
      // @todo replace with SetupException
      if (isset($result['fatalError'])) {
        $fatalError = $result['fatalError'];
        Yii::error($fatalError);
        ErrorDialog::create($fatalError);
        return $fatalError;
      }
      if (isset($result['error'])) {
        foreach ( (array) $result['error'] as $error) {
          $errors[] = "$method: $error";
        }
      }
      if (isset($result['message'])) {
        $message = $result['message'];
        $messages = array_merge( $messages, (array) $message );
      }
    }
    $newShelfId = $this->shelve($methods, $upgrade_from, $upgrade_to, $errors, $messages, ++$step, $steps);
    if (!$message){
      $message = Yii::t(self::CATEGORY, "Setting up application...");
    } elseif( is_array($message) ){
      $message = $message[0];
    }
    $route = count($methods) ? "setup/continue" : "setup/finish";
    (new Popup())
      ->setMessage( $message . "  ($step/$step)")
      ->setRoute($route)
      ->setParams([$newShelfId])
      ->sendToClient();
    return $message;
  }

  /**
   * @param string $shelfId
   * @return string Diagnostic message
   */
  public function actionFinish($shelfId)
  {
    list( $methods, $upgrade_from, $upgrade_to, $errors, $messages, $step, $steps) = $this->unshelve($shelfId);
    if ( is_array($errors) and count($errors)) {
      Yii::warning("Setup finished with errors:");
      Yii::warning($errors);
      $msg = Html::tag('b', Yii::t('setup', 'Setup failed. Please fix the following problems:'));
      ErrorDialog::create(\implode('<br>', $this->errors));
      return "Setup finished with errors";
    }
    // Everything seems to be ok
    Yii::debug("Setup finished successfully.", __METHOD__);
    Yii::debug($messages, __METHOD__);

    // notify client that setup it done
    $this->dispatchClientMessage("ldap.enabled", Yii::$app->config->getIniValue("ldap.enabled"));
    $this->dispatchClientMessage("bibliograph.setup.done"); // @todo rename

    Popup::hide();

    return "Setup finished successfully.";
  }

  //-------------------------------------------------------------
  // HELPERS
  //-------------------------------------------------------------  

  /**
   * Returns the names of all tables in the current database
   * @return array
   * @throws \yii\db\Exception If no connection can be established
   */
  protected function tables()
  {
    static $tables = null;
    if (is_null($tables)) {
      $dbConnect = \Yii:: $app->get('db');
      if (!($dbConnect instanceof \yii\db\Connection)) {
        throw new \yii\db\Exception('Cannot establish db connection');
      }
      $tables = $dbConnect->schema->getTableNames();
      if (!count($tables)) {
        Yii::debug("Database {$dbConnect->dsn} does not contain any tables." . implode(", ", $tables));
        return [];
      }
      Yii::debug("Tables in the database {$dbConnect->dsn}: " . implode(", ", $tables));
    }
    return $tables;
  }

  /**
   * Checks if one or more tables exist.
   * @param string|array $tableName A table name or an array of table names
   * @return bool If (all) table(s) exists in the schema
   * @throws \yii\db\Exception If no connection can be established
   */
  protected function tableExists($tableName)
  {
    $tables = $this->tables();
    if (is_array($tableName)) {
      return count(\array_diff($tableName, $tables)) == 0;
    }
    return \in_array($tableName, $tables);
  }

  /**
   * Recursively deletes files and subfolders in a given folder
   * @param string $dir
   */
  protected function emptyDir(string $dir) {
    if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (is_dir($dir."/".$object))
            $this->emptyDir($dir."/".$object);
          else
            unlink($dir."/".$object);
        }
      }
    }
  }

  //-------------------------------------------------------------
  // SETUP METHODS
  //-------------------------------------------------------------

  /**
   * Deletes the file cache on version change
   * @return boolean
   * @throws \Exception
   */
  protected function setupDeleteFileCache($upgrade_from,$upgrade_to){
    if( $upgrade_from !== $upgrade_to ){
      Yii::debug("Deleting file cache ...", __METHOD__);
      $this->emptyDir( __DIR__ . "/../runtime/cache" );
    }
    return false;
  }


  /**
   * Check if an ini file exists
   *
   * @return array
   * @throws UserErrorException
   */
  protected function checkIfIniFileExists()
  {
    $this->hasIni = file_exists(APP_CONFIG_FILE);
    if (!$this->hasIni) {
      if (YII_ENV_PROD) {
        throw new UserErrorException(Yii::t('setup', 'Cannot run in production mode without ini file.'));
      } else {
        throw new UserErrorException( "Wizard not implemented yet. Please add ini file as per installation instructions.");
      }
    }
  }

  /**
   * Check if the file permissions are correct
   *
   * @return array
   */
  protected function setupCheckFilePermissions()
  {
    if( ! $this->isNewInstallation ) return false;

    $config_dir = Yii::getAlias('@app/config');
    if (!$this->hasIni and YII_ENV_DEV and !\is_writable($config_dir)) {
      return [
        'error' => Yii::t('setup', "The configuration directory needs to be writable in order to create an .ini file: {config_dir}.", [
          'config_dir' => $config_dir
        ])
      ];
    }
    if (YII_ENV_PROD and \is_writable($config_dir)) {
      return [
        'error' => Yii::t('setup', "The configuration directory must not be writable in production mode {config_dir}.", [
          'config_dir' => $config_dir
        ])
      ];
    }

    // OK
    return [
      'message' => 'File permissions ok.'
    ];
  }

  /**
   * Check if we have an admin email address
   *
   * @return array
   */
  protected function setupCheckAdminEmail()
  {
    $adminEmail = Yii::$app->config->getIniValue("email.admin");
    if (!$adminEmail) {
      return [
        'error' => Yii::t('setup', "Missing administrator email in app.conf.toml.")
      ];
    }
    return [
      'message' => Yii::t('setup', 'Admininstrator email exists.')
    ];
  }

  /**
   * Check if we have a database connection
   *
   * @return array|boolean
   */
  protected function setupCheckDbConnection()
  {
    if (!Yii::$app->db instanceof \yii\db\Connection) {
      return [
        'fatalError' => Yii::t('setup', 'No database connection. ')
      ];
    }
    try {
      Yii::$app->db->open();
    } catch (\yii\db\Exception $e) {
      return [
        'fatalError' => Yii::t('setup', 'Cannot connect to database: {error}', [
          'error' => $e->errorInfo
        ])
      ];
    }
    $this->hasDb = true;
    return [
      'message' => 'Database connection ok.'
    ];
  }

  /**
   * Run migrations
   * @param $upgrade_from
   * @param $upgrade_to
   * @return array|false
   * @throws \Exception
   */
  protected function setupDoMigrations($upgrade_from, $upgrade_to)
  {
    if( $upgrade_from == $upgrade_to ) return false;

    $expectTables = explode(",",
      "data_Config,data_Datasource,data_Group,data_Messages,data_Permission,data_Role,data_Session,data_User,data_UserConfig," .
      "join_Datasource_Group,join_Datasource_Role,join_Datasource_User,join_Group_User,join_Permission_Role,join_User_Role");
    $allTablesExist = $this->tableExists($expectTables);
    if ($allTablesExist) {
      Yii::debug("All relevant v2 tables exist.", 'migrations', __METHOD__);
    } else {
      $missingTables = \array_diff($expectTables, $this->tables());
      if (count(\array_diff($expectTables, $missingTables)) == 0) {
        Yii::debug("None of the relevant v2 tables exist.", 'migrations', __METHOD__);
      } else {
        // only some exist, this cannot currently be migrated or repaired
        Yii::error("Cannot upgrade from v2, since the following tables are missing: " . implode(", ", $missingTables));
        return [
          'fatalError' => Yii::t('setup', 'Invalid database setup. Please contact the adminstrator.')
        ];
      }
    }
    // fresh installation
    if ($upgrade_from == "0.0.0") {
      $message = Yii::t('setup', 'Found empty database');
    } // if this is an upgrade from a v2 installation, manually add migration history
    elseif ($upgrade_from == "2.x") {
      if (!$allTablesExist) {
        return [
          'fatalError' => Yii::t('setup', 'Cannot update from Bibliograph v2 data: some tables are missing.')
        ];
      }
      Yii::info('Found Bibliograph v2.x data in database. Adding migration history.', 'migrations');
      // set migration history to match the existing data
      try {
        $output = Console::runAction('migrate/mark', ["app\\migrations\\data\\m180105_075933_join_User_RoleDataInsert"]);
      } catch (MigrationException $e) {
        return [
          'fatalError' => Yii::t('setup', 'Migrating data from Bibliograph v2 failed.')
        ];
      }
      if ($output->contains('migration history is set') or
        $output->contains('Nothing needs to be done')) {
        $message = Yii::t('setup', 'Migrated data from Bibliograph v2');
      } else {
        return [
          'fatalError' => Yii::t('setup', 'Migrating data from Bibliograph v2 failed.')
        ];
      }
    } else {
      $message = Yii::t('setup', 'Found data for version {version}', [
        'version' => $upgrade_from
      ]);
    }

    // run new migrations
    try {
      $output = Console::runAction('migrate/new');
    } catch (MigrationException $e) {
      return [
        'fatalError' => "migrate/new failed",
        'consoleOutput' => $e->consoleOutput
      ];
    }
    if ($output->contains('up-to-date')) {
      Yii::debug('No new migrations.', 'migrations', __METHOD__);
      $message = Yii::t('setup', "No updates to the databases.");
    } else {
      // unless this is a fresh installation, require admin login
      /** @var \app\models\User $activeUser */
      $activeUser = Yii::$app->user->identity;
      // if the current version is >= 3.0.0 and no user is logged in, show a login screen
      if (version_compare($upgrade_from, "3.0.0", ">=") and (!$activeUser or !$activeUser->hasRole('admin'))) {
        $message = Yii::t('setup', "The application needs to be upgraded from '{oldversion}' to '{newversion}'. Please log in as administrator.", [
          'oldversion' => $upgrade_from,
          'newversion' => $upgrade_to
        ]);
        Login::create($message, "setup", "setup");
        return [
          "abort" => "Login required."
        ];
      };

      // unless we're in test mode, let the admin confirm 
      if (version_compare($upgrade_from, "3.0.0", ">=") and !$this->migrationConfirmed and !YII_ENV_TEST) {
        $message = Yii::t('setup', "The database must be upgraded. Confirm that you have made a database backup and now are ready to run the upgrade."); // or face eternal damnation.
        Confirm::create($message, null, "setup", "setup-confirm-migration");
        return [
          "abort" => "Admin needs to confirm the migrations"
        ];
      }

      // run all migrations 
      Yii::debug("Applying migrations...", "migrations", __METHOD__);
      try {
        $output = Console::runAction("migrate/up");
      } catch (MigrationException $e) {
        $output = $e->consoleOutput;
      }
      if ($output->contains('Migrated up successfully')) {
        Yii::debug("Migrations successfully applied.", "migrations", __METHOD__);
        $message .= Yii::t('setup', ' and applied new migrations for version {version}', [
          'version' => $upgrade_to
        ]);
      } else {
        return [
          'fatalError' => Yii::t('setup', 'Initializing database failed.'),
          'consoleOutput' => $output
        ];
      }
    }
    return [
      'message' => $message
    ];
  }

  /**
   * Create inital preference keys/values.
   * @todo modules!
   * @return array
   */
  protected function setupPreferences($upgrade_from, $upgrade_to)
  {
    if( $upgrade_from == $upgrade_to ) return false;
    $prefs = require Yii::getAlias('@app/config/prefs.php');
    foreach ($prefs as $key => $value) {
      try{
        Yii::$app->config->createKeyIfNotExists(
          $key,
          $value['type'],
          isset($value['customize']) ? isset($value['customize']) :null,
          $value['default'],
          isset($value['final']) ? isset($value['final']) :null
        );
      } catch( \InvalidArgumentException $e ) {
        throw new SetupException("Creating config key '$key' failed.");
      }
    }
    return ['message' => Yii::t('setup','Configuration values were created.')];
  }

  /**
   * Create initial schema(s)
   * @return array
   */
  protected function setupSchemas()
  {
    $schemaClass = Yii::$app->config->getPreference('app.datasource.baseclass');
    $schemaExists = false;
    try {
      Schema::register(self::DATASOURCE_DEFAULT_SCHEMA, $schemaClass, [
        'protected' => 1
      ] );
    } catch ( RecordExistsException $e) {
      $schemaExists = true;
    } catch (\ReflectionException $e) {
      throw new SetupException("Invalid schema class '$schemaClass" );
    }
    return [
      'message' => $schemaExists ?
        Yii::t('setup', 'Standard schema existed.') :
        Yii::t('setup', 'Created standard schema.')
    ];
  }

  /**
   * Setup datasources
   * @todo change name
   * @return array|boolean
   */
  protected function setupDatasources()
  {
    // only create example databases if this is a new installation
    $datasources = !$this->isNewInstallation ? [] : [
      'datasource1' => [
        'config' => [
          'title' => "Example Database 1",
          'description' => "This database is publically visible"
        ],
        'roles' => ['anonymous', 'user']
      ],
      'datasource2' => [
        'config' => [
          'title' => "Example Database 2",
          'description' => "This database is visible only for logged-in users"
        ],
        'roles' => ['user']
      ]
    ];

    // Import
    $datasources = array_merge( $datasources, [
      'bibliograph_import' => [
        'config' => [
          'title' => "Import",
          'hidden' => 1,
          'description' => "This database is used for importing data"
        ],
        'roles' => ['user']
      ],
    ]);

    $count = 0;
    $found = 0;
    foreach ($datasources as $name => $data) {
      if (\app\models\Datasource::findByNamedId($name)) {
        $found++;
        continue;
      }
      try {
        $datasource = Yii::$app->datasourceManager->create($name);
      } catch (\Exception $e) {
        Yii::error($e);
        throw new SetupException("Could not create datasource '$name':" . $e->getMessage(), null, $e);
      }
      $datasource->setAttributes($data['config']);
      try {
        $datasource->save();
      } catch (Exception $e) {
        Yii::error("Error saving datasource '$name':" . $e->getMessage());
      }
      foreach ((array)$data['roles'] as $roleId) {
        $datasource->link('roles', \app\models\Role::findByNamedId($roleId));
      }
      $count++;
    }

    return [
      'message' => $found == $count ?
        Yii::t('setup', 'Datasources already existed.') :
        Yii::t('setup', 'Created datasources.')
    ];
  }

  /**
   * @return array
   */
  protected function setupModules()
  {
    $errors = [];
    $messages = [];
    $this->moduleUpgrade = false;
    foreach( Yii::$app->modules as $id => $info){
      /** @var Module $module */
      $module = Yii::$app->getModule($id);
      if( ! $module instanceof \lib\Module ) continue;
      if( $module->version === $module->installedVersion ){
        Yii::debug("Module $module->id ($module->name) is already at version $module->version ...");
        $messages[] = "Module '$module->id' already installed.";
        continue;
      }
      Yii::debug("Installing module $module->id $module->version", __METHOD__);
      try{
        $enabled = $module->install();
        if( $enabled ){
          $messages[] = "Installed module '{$module->id}'.";
          $this->moduleUpgrade = true;
        } else {
          $errors = array_merge($errors, $module->errors );
        }
      } catch (\Exception $e) {
        Yii::error($e);
        $errors[] = "Installing module '{$module->id}' failed: " . $e->getMessage();
      }
    }
    return [
      'message' => $messages,
      'error' => $errors
    ];
  }

  /**
   * Migrates datasources
   * @return array|boolean
   * @throws \Exception
   */
  protected function setupDatasourceMigrations($upgrade_from,$upgrade_to)
  {
    if( ($this->isNewInstallation or $upgrade_from === $upgrade_to) and ! $this->moduleUpgrade ) {
      Yii::debug("New installation or no new versions, skipping datasource migration.", __METHOD__);
      return false;
    }
    $migrated = [];
    $failed = [];
    /** @var Schema[] $schemas */
    $schemas = Schema::find()->all();
    foreach( $schemas as $schema ){
      try {
        // upgrade old datasources that do not have a 'migration' table yet
        /** @var \app\models\BibliographicDatasource $datasource */
        foreach ($schema->datasources as $datasource) {
          $markerClass = null;
          switch( $schema->namedId ) {
            case "bibliograph_datasource":
            case "bibliograph_extended":
              $markerClass = "M180301071642_Update_table_data_Reference_add_fullext_index";
              break;
          }
          $prefix = $datasource->namedId . "_";
          if( ! $datasource->prefix ){
            $datasource->prefix = $prefix;
            $datasource->save();
          }
          $migration_table_exists = $datasource::getDb()->getTableSchema( $prefix . "migration");
          if( $markerClass and ! $migration_table_exists ){
            Yii::debug("Initializing migrating for datasource table '$datasource->namedId', schema '$schema->namedId'...", __METHOD__);
            $migrationNamespace = Datasource::getInstanceFor($datasource->namedId)->migrationNamespace;
            $fqn = "$migrationNamespace\\$markerClass";
            $params_mark = [
              $fqn,
              'migrationNamespaces' => $migrationNamespace,
            ];
            $db = $datasource->getConnection();
            Yii::debug("Marking datasource '{$datasource->namedId}' with '$fqn'...", __METHOD__);
            Console::runAction('migrate/mark', $params_mark, null, $db);
            $migrated[] = $schema->namedId;
          }
        }
        // run schema migrations
        $count = Yii::$app->datasourceManager->migrate($schema);
        if( $count > 0 ){
          $migrated[]= $schema->namedId;
        }
      } catch (MigrationException $e) {
        $timestamp = time();
        $failedMsg = $schema->namedId . ": " . $e->getMessage() . " ($timestamp)";
        $failed[] = $failedMsg;
        Yii::error( $failedMsg );
        Yii::error((string) $e->consoleOutput);
      }
      // other errors will not be caught
    }
    return [
      'message' => count($migrated)
        ? Yii::t('setup','Migrated schema(s) {schemas}.', [
          'schemas' => implode(", ", array_unique($migrated) )
        ])
        : Yii::t('setup', "No schema migrations necessary."),
      'error' => count($failed) ? Yii::t('setup','Migrating schema(s) failed: {errinfo}', ['errinfo' => implode(", ", $failed)]) : null
    ];
  }

  /**
   * Check the LDAP connection
   *
   * @return array
   */
  protected function setupLdapConnection()
  {
    $ldap = Yii::$app->ldapAuth->checkConnection();
    $message = $ldap['enabled'] ?
      Yii::t('setup', 'LDAP authentication is enabled') :
      Yii::t('setup', 'LDAP authentication is not enabled.');
    $message .= ($ldap['enabled'] and $ldap['connection']) ?
      Yii::t('setup', ' and a connection has successfully been established.') :
      $ldap['enabled'] ? Yii::t('setup', ', but trying to establish a connection failed with the error: {error}', [
        'error' => $ldap['error']
      ]) : "";
    if ($ldap['enabled'] and $ldap['error']) {
      $result = ['error' => $message];
    } else {
      $result = ['message' => $message];
    }
    return $result;
  }
}