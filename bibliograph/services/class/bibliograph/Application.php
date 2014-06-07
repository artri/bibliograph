<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import( "qcl_application_Application" );
qcl_import( "qcl_locale_Manager" );
qcl_import("bibliograph_ApplicationCache");
qcl_import("qcl_util_system_Lock");

/**
 * Main application class
 */
class bibliograph_Application
  extends qcl_application_Application
{

  /**
   * The id of the application, usually the namespace
   * @var string
   */
  protected $applicationId = "bibliograph";

  /**
   * The descriptive name of the application
   * @var string
   */
  protected $applicationName = "Bibliograph: Online Bibliographic Data Manager";

  /**
   * The version of the application
   * @var string
   */
  protected $applicationVersion = "2.1";

  /**
   * The path to the application ini-file
   * @var string
   */
  protected $iniPath = "config/bibliograph.ini.php";

  /**
   * The default datasource schema used by the application
   * @var string
   */
  protected $defaultSchema = "bibliograph.schema.bibliograph2";


  /**
   * A map with model types as keys and the path to xml files containing
   * the model data as values.
   * The order of importing matters!
   * @var array
   */
  protected $initialDataMap = array(
    'user'        => "bibliograph/data/User.xml",
    'role'        => "bibliograph/data/Role.xml",
    'permission'  => "bibliograph/data/Permission.xml",
    'config'      => "bibliograph/data/Config.xml"
   );

  /**
   * Returns the singleton instance of this class. Note that
   * qcl_application_Application::getInstance() stores the
   * singleton instance for global access
   * @return bibliograph_Application
   */
  static public function getInstance()
  {
    return parent::getInstance();
  }

  /**
   * Getter for map needed for loading the initial data
   * @return array
   */
  public function getInitialDataMap()
  {
    return $this->initialDataMap;
  }

  /**
   * Starts the application, performing on-the-fly database setup if necessary.
   */
  public function main()
  {

    parent::main();

    /*
     * log request
     */
    if( $this->getLogger()->isFilterEnabled( BIBLIOGRAPH_LOG_APPLICATION ) )
    {
      $request = qcl_server_Request::getInstance();
      $this->log( sprintf(
        "Starting Bibliograph service: %s.%s( %s ) ...",
        $request->getService(), $request->getMethod(), json_encode($request->getParams())
      ), BIBLIOGRAPH_LOG_APPLICATION );
    }

    /*
     * Clear internal caches. This is only necessary during development
     * as long as you modify the properties of models.
     */
    //qcl_data_model_db_ActiveRecord::resetBehaviors();


    /*
     * Check if this is the first time the application is called, or if
     * the application backend is currently being configured
     */
    $cache = bibliograph_ApplicationCache::getInstance();
    if ( ! $cache->get("setup") )
    {
      /*
       * no, then deny all requests except the one for the "setup" service
       */
      if ( $this->getServerInstance()
                ->getRequest()
                ->getService() != "bibliograph.setup" )
      {
        throw new qcl_server_ServiceException("Server busy. Setup in progress.",null,true);
      }

      /*
       * Load initial access model data into models, then go to setup service
       */
      if ( ! $cache->get("dataImported") )
      {
        $this->log("Importing data ....", QCL_LOG_SETUP );
        $this->importInitialData( $this->getInitialDataMap() );
      }
      $cache->set( "dataImported", true );
    }


    /*
     * initialize locale manager
     */
    qcl_locale_Manager::getInstance();

    /**
     * Register the services provided by this application
     */
    $this->registerServices( include( dirname(__FILE__) . "/routes.php" ) );

    /**
     * Plugins
     */
    $this->pluginPath = dirname(__FILE__) . "/plugin/";

  }

  /**
   * Overridden to create config key
   * @see qcl_application_Application#createDatasource($namedId, $data)
   */
  function createDatasource( $namedId, $data= array() )
  {
    $datasource = parent::createDatasource( $namedId, $data );

    /*
     * create config keys for the datasource
     * @todo generalize this
     * @todo check that setup calls this
     */
    $configModel = $this->getApplication()->getConfigModel();
    $key = "datasource.$namedId.fields.exclude";
    $configModel->createKeyIfNotExists( $key, QCL_CONFIG_TYPE_LIST, false, array() );

    return $datasource;
  }

  /**
   * Overridden to skip authentication completely for selected services.
   * @return bool
   */
  public function skipAuthentication()
  {
    $request = $this->getServerInstance()->getRequest();
    $service = $request->getService() . "." . $request->getMethod();
    switch( $service )
    {
      case "bibliograph.setup.setup":
        return true;
      default:
        return false;
    }
  }

  /**
   * Overridden to allow anonymous users to access services.
   * @return bool
   */
  public function isAnonymousAccessAllowed()
  {
    //$request = $this->getServerInstance()->getRequest();
    //$service = $request->getService() . "." . $request->getMethod();
    return true;
  }
}
?>