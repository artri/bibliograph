<?php

return [
  [

    'id' => 1,
    'namedId' => 'test',
    'created' => '2017-12-20 13:56:48',
    'modified' => '2017-12-20 14:56:48',
    'title' => 'Test Database',
    'description' => null,
    'schema' => 'bibliograph.schema.bibliograph2',
    'type' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'tests',
    'username' => 'root',
    'password' => null,
    'encoding' => 'utf-8',
    'prefix' => '',
    'resourcepath' => null,
    'active' => 1,
    'readonly' => 0,
    'hidden' => 0,
  ],
  [
    'id' => 2,
    'namedId' => 'test_extended',
    'created' => '2017-12-20 13:56:48',
    'modified' => '2017-12-20 14:56:48',
    'title' => 'Database 1',
    'description' => null,
    'schema' => 'bibliograph.schema.bibliograph2',
    'type' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'tests',
    'username' => 'root',
    'password' => null,
    'encoding' => 'utf-8',
    'prefix' => null,
    'resourcepath' => null,
    'active' => 1,
    'readonly' => 0,
    'hidden' => 0,
  ],
  [
    'id' => 3,
    'namedId' => 'setup',
    'created' => '2017-12-20 13:56:48',
    'modified' => '2017-12-20 14:56:48',
    'title' => null,
    'description' => null,
    'schema' => 'none',
    'type' => 'dummy',
    'host' => null,
    'port' => null,
    'database' => null,
    'username' => null,
    'password' => null,
    'encoding' => 'utf-8',
    'prefix' => null,
    'resourcepath' => null,
    'active' => 0,
    'readonly' => 0,
    'hidden' => 1,
  ],
  [
    'id' => 4,
    'namedId' => 'bibliograph_import',
    'created' => '2017-12-20 13:56:48',
    'modified' => '2017-12-20 14:56:48',
    'title' => null,
    'description' => null,
    'schema' => 'bibliograph.schema.bibliograph2',
    'type' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'tests',
    'username' => 'root',
    'password' => null,
    'encoding' => 'utf-8',
    'prefix' => null,
    'resourcepath' => null,
    'active' => 1,
    'readonly' => 0,
    'hidden' => 1,
  ],
  [
    'id' => 5,
    'namedId' => 'bibliograph_export',
    'created' => '2017-12-20 13:56:48',
    'modified' => '2017-12-20 14:56:48',
    'title' => null,
    'description' => null,
    'schema' => 'qcl.schema.filesystem.local',
    'type' => 'file',
    'host' => null,
    'port' => null,
    'database' => null,
    'username' => null,
    'password' => null,
    'encoding' => 'utf-8',
    'prefix' => null,
    'resourcepath' => '/tmp',
    'active' => 1,
    'readonly' => 0,
    'hidden' => 1,
  ],
  [
    'id' => 7,
    'namedId' => 'z3950_BVB01MCZ',
    'created' => '2017-12-20 14:00:17',
    'modified' => '2017-12-20 15:00:17',
    'title' => 'Bibliotheksverbund Bayern (BVB)/B3Kat',
    'description' => null,
    'schema' => 'bibliograph.schema.z3950',
    'type' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'tests',
    'username' => 'root',
    'password' => null,
    'encoding' => 'utf-8',
    'prefix' => null,
    'resourcepath' => '/Users/cboulanger/Code/bibliograph/bibliograph/plugins/z3950/services/class/z3950/servers/193.174.96.24-31310-BVBSR011.xml',
    'active' => 1,
    'readonly' => 0,
    'hidden' => 1,
  ],
  [
    'id' => 8,
    'namedId' => 'z3950_NEBIS',
    'created' => '2017-12-20 14:00:17',
    'modified' => '2017-12-20 15:00:17',
    'title' => 'Das Netzwerk von Bibliotheken und Informationsstellen in der Schweiz',
    'description' => null,
    'schema' => 'bibliograph.schema.z3950',
    'type' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'bibliograph',
    'username' => null,
    'password' => null,
    'encoding' => 'utf-8',
    'prefix' => null,
    'resourcepath' => '/Users/cboulanger/Code/bibliograph/bibliograph/plugins/z3950/services/class/z3950/servers/opac.nebis.ch-9909-NEBIS.xml',
    'active' => 1,
    'readonly' => 0,
    'hidden' => 1,
  ],
  [
    'id' => 9,
    'namedId' => 'z3950_stabikat',
    'created' => '2017-12-20 14:00:17',
    'modified' => '2017-12-20 15:00:17',
    'title' => 'Staatsbibliothek Berlin',
    'description' => null,
    'schema' => 'bibliograph.schema.z3950',
    'type' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'bibliograph',
    'username' => null,
    'password' => null,
    'encoding' => 'utf-8',
    'prefix' => null,
    'resourcepath' => '/Users/cboulanger/Code/bibliograph/bibliograph/plugins/z3950/services/class/z3950/servers/z3950.gbv.de-20010-stabikat.xml',
    'active' => 1,
    'readonly' => 0,
    'hidden' => 1,
  ],
  [
    'id' => 10,
    'namedId' => 'z3950_gvk',
    'created' => '2017-12-20 14:00:17',
    'modified' => '2017-12-20 15:00:17',
    'title' => 'Gemeinsamer Verbundkatalog - Bremen, Hamburg, Mecklenburg-Vorpommern, Niedersachsen, Sachsen-Anhalt,',
    'description' => null,
    'schema' => 'bibliograph.schema.z3950',
    'type' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'bibliograph',
    'username' => null,
    'password' => null,
    'encoding' => 'utf-8',
    'prefix' => null,
    'resourcepath' => '/Users/cboulanger/Code/bibliograph/bibliograph/plugins/z3950/services/class/z3950/servers/z3950.gbv.de-210-GVK-de.xml',
    'active' => 1,
    'readonly' => 0,
    'hidden' => 1,
  ],
  [
    'id' => 11,
    'namedId' => 'z3950_U-KBV90',
    'created' => '2017-12-20 14:00:17',
    'modified' => '2017-12-20 15:00:17',
    'title' => 'Kooperativer Bibliotheksverbund Berlin-Brandenburg (KOBV)',
    'description' => null,
    'schema' => 'bibliograph.schema.z3950',
    'type' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'bibliograph',
    'username' => null,
    'password' => null,
    'encoding' => 'utf-8',
    'prefix' => null,
    'resourcepath' => '/Users/cboulanger/Code/bibliograph/bibliograph/plugins/z3950/services/class/z3950/servers/z3950.kobv.de-9991-U-KBV90.xml',
    'active' => 1,
    'readonly' => 0,
    'hidden' => 1,
  ],
  [
    'id' => 12,
    'namedId' => 'z3950_voyager',
    'created' => '2017-12-20 14:00:17',
    'modified' => '2017-12-20 15:00:17',
    'title' => 'Library of Congress',
    'description' => null,
    'schema' => 'bibliograph.schema.z3950',
    'type' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'bibliograph',
    'username' => null,
    'password' => null,
    'encoding' => 'utf-8',
    'prefix' => null,
    'resourcepath' => '/Users/cboulanger/Code/bibliograph/bibliograph/plugins/z3950/services/class/z3950/servers/z3950.loc.gov-7090-voyager.xml',
    'active' => 1,
    'readonly' => 0,
    'hidden' => 1,
  ],
  [
    'id' => 13,
    'namedId' => 'backup_files',
    'created' => '2017-12-20 14:04:08',
    'modified' => '2017-12-20 15:04:08',
    'title' => null,
    'description' => 'Datasource containing backup files.',
    'schema' => 'qcl.schema.filesystem.local',
    'type' => 'file',
    'host' => null,
    'port' => null,
    'database' => null,
    'username' => null,
    'password' => null,
    'encoding' => 'utf-8',
    'prefix' => null,
    'resourcepath' => '/var/folders/nl/jy1l777x56s0wsj8x1d4my6m0000gn/T',
    'active' => 1,
    'readonly' => 0,
    'hidden' => 1,
  ],
  [
    'id' => 14,
    'namedId' => 'database3',
    'created' => '2017-12-21 11:40:26',
    'modified' => '2017-12-21 12:40:36',
    'title' => 'Sarah\'s Database',
    'description' => null,
    'schema' => 'bibliograph.schema.bibliograph2',
    'type' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'bibliograph',
    'username' => null,
    'password' => null,
    'encoding' => 'utf-8',
    'prefix' => null,
    'resourcepath' => null,
    'active' => 1,
    'readonly' => 0,
    'hidden' => 0,
  ],
];