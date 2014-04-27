/**
    Generated by QxTransformer v.0.4.
    Author Christian Boulanger
**/

/*------------------------------------------------------------------------------

------------------------------------------------------------------------------*/
qx.Class.define("bibliograph.ui.main.FolderTreePanel",
{
  extend : qx.ui.container.Composite,
  construct : function()
  {
    this.base(arguments);
    this.__qxtCreateUI();
  },
  members : {
    __qxtCreateUI : function()
    {
      var qxVbox1 = new qx.ui.layout.VBox(null, null, null);
      var qxComposite1 = this;
      this.setLayout(qxVbox1)
      var qxMenuBar1 = new qx.ui.menubar.MenuBar();
      qxMenuBar1.setHeight(22);
      qxComposite1.add(qxMenuBar1);
      var titleLabel = new qx.ui.basic.Label(null);
      this.titleLabel = titleLabel;
      titleLabel.setPadding(3);
      titleLabel.setRich(true);
      qxMenuBar1.add(titleLabel);
      var treeWidget = new bibliograph.ui.folder.TreeViewUi();
      treeWidget.setShowColumnHeaders(true);
      treeWidget.setWidgetId("mainFolderTree");
      treeWidget.setWidth(200);
      treeWidget.setColumnHeaders([this.tr('Folders'), '#']);
      qxComposite1.add(treeWidget, {
        flex : 1
      });
      this.getApplication().bind("datasource", treeWidget, "datasource", {

      });
      treeWidget.bind("nodeId", this.getApplication(), "folderId", {

      });
      this.getApplication().bind("folderId", treeWidget, "nodeId", {

      });
      qx.core.Init.getApplication()
        .getAccessManager()
        .getPermissionManager()
        .create("folder.move").bind("state", treeWidget, "enableDragDrop");
        
      qx.event.message.Bus.getInstance().subscribe("bibliograph.userquery", function() {
        try {
          treeWidget.getTree().resetSelection();
        }catch (e) {
        }
      }, this)
    }
  }
});
