<?php

return [
  [
    'id' => 1,
    'parentId' => 0,
    'position' => 0,
    'label' => 'Hauptordner',
    'type' => 'folder',
    'query' => null,
    'public' => 1,
    'childCount' => 2,
    'referenceCount' => 22,
  ],
  [
    'id' => 2,
    'parentId' => 0,
    'position' => 1,
    'label' => 'Mülleimer',
    'type' => 'trash',
  ],
  [
    'id' => 3,
    'parentId' => 1,
    'position' => 0,
    'label' => 'Reference Management Software',
    'public' => 1,
    'opened' => 1,
    'createdBy' => 'admin',
    'childCount' => 1,
    'referenceCount' => 7,
  ],
  [
    'id' => 4,
    'parentId' => 1,
    'position' => 1,
    'label' => 'Zotero',
    'type' => 'search',
    'searchfolder' => 1,
    'query' => 'title contains zotero',
    'public' => 1,
    'opened' => 1,
    'createdBy' => 'admin',
  ],
  [
    'id' => 5,
    'parentId' => 3,
    'position' => 0,
    'label' => 'Foo',
    'public' => 1,
    'opened' => 1,
    'createdBy' => 'admin',
  ],
];