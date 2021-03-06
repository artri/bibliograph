/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Returns the form layout for the given reference type and
 * datasource
 * 
 * @see app\controllers\HelpController
 * @file HelpController.php
 */
qx.Class.define("rpc.Help",
{ 
  type: 'static',
  statics: {
    /**
     * Returns the html for the search help text
     * 
     * @param datasource {String} 
     * @return {Promise}
     * @see HelpController::actionSearch
     */
    search : function(datasource){
      qx.core.Assert.assertString(datasource);
      return qx.core.Init.getApplication().getRpcClient("help").send("search", [datasource]);
    }
  }
});