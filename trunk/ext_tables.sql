#
# Table structure for table 'be_users'
#
CREATE TABLE be_users (
    tx_sduconnect_sdusecuritykey varchar(255) NOT NULL default '',
);

#
# Table structure for table 'tx_sduconnect_accountsettings'
#
CREATE TABLE tx_sduconnect_accountsettings (
  uid int(10)  NOT NULL auto_increment,
  settings tinytext,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  settingsName varchar(255) NOT NULL default '',
  PRIMARY KEY  (uid),
  UNIQUE KEY settingsName (settingsName)
);

# 
# Table structure for table 'tx_sduconnect_organisation'
# 
CREATE TABLE tx_sduconnect_organisation (
  organisation_id int(10) NOT NULL auto_increment,
  collectionId int(10) NOT NULL default '0',
  title varchar(255) NOT NULL default '',
  searchstring varchar(255) NOT NULL default '',
  visit_address_street varchar(255) NOT NULL default '',
  visit_address_number varchar(255) NOT NULL default '',
  visit_address_zip_code varchar(255) NOT NULL default '',
  visit_address_city varchar(255) NOT NULL default '',
  post_address_street varchar(255) NOT NULL default '',
  post_address_number varchar(255) NOT NULL default '',
  post_address_po_box varchar(255) NOT NULL default '',
  post_address_zip_code varchar(255) NOT NULL default '',
  post_address_city varchar(255) NOT NULL default '',
  country varchar(255) NOT NULL default '',
  phone_number varchar(255) NOT NULL default '',
  fax_number varchar(255) NOT NULL default '',
  email varchar(255) NOT NULL default '',
  url varchar(255) NOT NULL default '',
  logo varchar(255) NOT NULL default '',
  remark text,
  tags tinytext,
  enabled tinyint(1) NOT NULL default '1',
  created_at datetime default NULL,
  modified_at datetime default NULL,
  imported_at datetime default NULL,
  is_protected tinyint(1) default '0',
  PRIMARY KEY  (organisation_id)
);
