CREATE TABLE IF NOT EXISTS llx_c_samlconnector_idp_type
(
  rowid			integer AUTO_INCREMENT	PRIMARY KEY,
  code			varchar(32)				NOT NULL,
  libelle		varchar(128)			NOT NULL,
  img_path		varchar(255)			NOT NULL,
  active		tinyint DEFAULT 1		NOT NULL
)
ENGINE = innodb;
