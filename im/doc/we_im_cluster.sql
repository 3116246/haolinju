-- 创建数据库
create database we_im DEFAULT CHARACTER SET  utf8;

-- 建用户
GRANT ALL PRIVILEGES ON we_im.* TO we_im@'%' IDENTIFIED BY "we_im"; 
GRANT ALL PRIVILEGES ON we_im.* TO we_rep@'%' IDENTIFIED BY "we_rep";

-- 建日志文件组、表空间
CREATE LOGFILE GROUP lg_we_im_1
  ADD UNDOFILE 'undo_we_im_1.log'
  ENGINE NDBCLUSTER;
ALTER LOGFILE GROUP lg_we_im_1
  ADD UNDOFILE 'undo_we_im_2.log'
  ENGINE NDBCLUSTER;
  
CREATE TABLESPACE ts_we_im_1
  ADD DATAFILE 'data_we_im_1.dat'
  USE LOGFILE GROUP lg_we_im_1
  INITIAL_SIZE 2147483648
  ENGINE NDBCLUSTER;
ALTER TABLESPACE ts_we_im_1
  ADD DATAFILE 'data_we_im_2.dat'
  INITIAL_SIZE 2147483648
  ENGINE NDBCLUSTER;

-- 建表
use we_im;

CREATE TABLE users (
    username varchar(50) CHARACTER SET latin1 PRIMARY KEY,
    password varchar(200) NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;

CREATE TABLE last (
    username varchar(50) CHARACTER SET latin1 PRIMARY KEY,
    seconds text NOT NULL,
    state text NOT NULl
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;


CREATE TABLE rosterusers (
    username varchar(50) CHARACTER SET latin1 NOT NULL,
    jid varchar(50) CHARACTER SET latin1 NOT NULL,
    nick varchar(50) NOT NULL,
    subscription character(1) NOT NULL,
    ask character(1) NOT NULL,
    askmessage text NOT NULL,
    server character(1) NOT NULL,
    subscribe text NOT NULL,
    type text,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    primary key (username, jid)
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;

CREATE INDEX i_rosteru_jid ON rosterusers(jid);

CREATE TABLE rostergroups (
    username varchar(50) CHARACTER SET latin1 NOT NULL,
    jid varchar(50) CHARACTER SET latin1 NOT NULL,
    grp text NOT NULL
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;

CREATE INDEX pk_rosterg_user_jid ON rostergroups(username, jid);

CREATE TABLE sr_group (
    name varchar(50) NOT NULL,
    opts text NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;

CREATE TABLE sr_user (
    jid varchar(50) CHARACTER SET latin1 NOT NULL,
    grp varchar(50) CHARACTER SET latin1 NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;

CREATE UNIQUE INDEX i_sr_user_jid_group ON sr_user(jid, grp);
CREATE INDEX i_sr_user_jid ON sr_user(jid);
CREATE INDEX i_sr_user_grp ON sr_user(grp);

CREATE TABLE spool (
    username varchar(50) CHARACTER SET latin1 NOT NULL,
    xml text NOT NULL,
    seq BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;

CREATE INDEX i_despool USING BTREE ON spool(username);


CREATE TABLE vcard (
    username varchar(50) CHARACTER SET latin1 PRIMARY KEY,
    vcard mediumtext NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;

CREATE TABLE vcard_xupdate (
    username varchar(50) CHARACTER SET latin1 PRIMARY KEY,
    hash text NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;

CREATE TABLE vcard_search (
    username varchar(50) CHARACTER SET latin1 NOT NULL,
    lusername varchar(50) CHARACTER SET latin1 PRIMARY KEY,
    fn text NOT NULL,
    lfn varchar(100) NOT NULL,
    family text NOT NULL,
    lfamily varchar(50) NOT NULL,
    given text NOT NULL,
    lgiven varchar(50) NOT NULL,
    middle text NOT NULL,
    lmiddle varchar(50) NOT NULL,
    nickname text NOT NULL,
    lnickname varchar(50) NOT NULL,
    bday text NOT NULL,
    lbday varchar(50) CHARACTER SET latin1 NOT NULL,
    ctry text NOT NULL,
    lctry varchar(50) NOT NULL,
    locality text NOT NULL,
    llocality varchar(50) NOT NULL,
    email text NOT NULL,
    lemail varchar(100) NOT NULL,
    orgname text NOT NULL,
    lorgname varchar(50) NOT NULL,
    orgunit text NOT NULL,
    lorgunit varchar(50) NOT NULL
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;

CREATE INDEX i_vcard_search_lfn       ON vcard_search(lfn);
CREATE INDEX i_vcard_search_lfamily   ON vcard_search(lfamily);
CREATE INDEX i_vcard_search_lgiven    ON vcard_search(lgiven);
CREATE INDEX i_vcard_search_lmiddle   ON vcard_search(lmiddle);
CREATE INDEX i_vcard_search_lnickname ON vcard_search(lnickname);
CREATE INDEX i_vcard_search_lbday     ON vcard_search(lbday);
CREATE INDEX i_vcard_search_lctry     ON vcard_search(lctry);
CREATE INDEX i_vcard_search_llocality ON vcard_search(llocality);
CREATE INDEX i_vcard_search_lemail    ON vcard_search(lemail);
CREATE INDEX i_vcard_search_lorgname  ON vcard_search(lorgname);
CREATE INDEX i_vcard_search_lorgunit  ON vcard_search(lorgunit);

CREATE TABLE privacy_default_list (
    username varchar(50) CHARACTER SET latin1 PRIMARY KEY,
    name varchar(250) NOT NULL
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;

CREATE TABLE privacy_list (
    username varchar(50) CHARACTER SET latin1 NOT NULL,
    name varchar(250) NOT NULL,
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;

CREATE UNIQUE INDEX i_privacy_list_username_name USING BTREE ON privacy_list (username, name);

CREATE TABLE privacy_list_data (
    id bigint,
    t character(1) NOT NULL,
    value text NOT NULL,
    action character(1) NOT NULL,
    ord NUMERIC NOT NULL,
    match_all boolean NOT NULL,
    match_iq boolean NOT NULL,
    match_message boolean NOT NULL,
    match_presence_in boolean NOT NULL,
    match_presence_out boolean NOT NULL
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;

CREATE TABLE private_storage (
    username varchar(50) CHARACTER SET latin1 NOT NULL,
    namespace varchar(250) NOT NULL,
    data text NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;

CREATE UNIQUE INDEX i_private_storage_username_namespace USING BTREE ON private_storage(username, namespace);

-- Not tested in mysql
CREATE TABLE roster_version (
    username varchar(50) CHARACTER SET latin1 PRIMARY KEY,
    version text NOT NULL
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;

-- To update from 1.x:
-- ALTER TABLE rosterusers ADD COLUMN askmessage text AFTER ask;
-- UPDATE rosterusers SET askmessage = '';
-- ALTER TABLE rosterusers ALTER COLUMN askmessage SET NOT NULL;

CREATE TABLE pubsub_node (
  host varchar(20),
  node varchar(120),
  parent varchar(120),
  type text,
  nodeid bigint auto_increment primary key
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;
CREATE INDEX i_pubsub_node_parent ON pubsub_node(parent);
CREATE UNIQUE INDEX i_pubsub_node_tuple ON pubsub_node(host, node);

CREATE TABLE pubsub_node_option (
  nodeid bigint,
  name text,
  val text
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;
CREATE INDEX i_pubsub_node_option_nodeid ON pubsub_node_option(nodeid);
-- ALTER TABLE `pubsub_node_option` ADD FOREIGN KEY (`nodeid`) REFERENCES `pubsub_node` (`nodeid`) ON DELETE CASCADE;

CREATE TABLE pubsub_node_owner (
  nodeid bigint,
  owner text
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;
CREATE INDEX i_pubsub_node_owner_nodeid ON pubsub_node_owner(nodeid);
-- ALTER TABLE `pubsub_node_owner` ADD FOREIGN KEY (`nodeid`) REFERENCES `pubsub_node` (`nodeid`) ON DELETE CASCADE;

CREATE TABLE pubsub_state (
  nodeid bigint,
  jid varchar(60) CHARACTER SET latin1,
  affiliation character(1),
  subscriptions text,
  stateid bigint auto_increment primary key
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;
CREATE INDEX i_pubsub_state_jid ON pubsub_state(jid);
CREATE UNIQUE INDEX i_pubsub_state_tuple ON pubsub_state(nodeid, jid);
-- ALTER TABLE `pubsub_state` ADD FOREIGN KEY (`nodeid`) REFERENCES `pubsub_node` (`nodeid`) ON DELETE CASCADE;

CREATE TABLE pubsub_item (
  nodeid bigint,
  itemid varchar(36) CHARACTER SET latin1,
  publisher text,
  creation text,
  modification text,
  payload text
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;
CREATE INDEX i_pubsub_item_itemid ON pubsub_item(itemid);
CREATE UNIQUE INDEX i_pubsub_item_tuple ON pubsub_item(nodeid, itemid);
-- ALTER TABLE `pubsub_item` ADD FOREIGN KEY (`nodeid`) REFERENCES `pubsub_node` (`nodeid`) ON DELETE CASCADE;

CREATE TABLE pubsub_subscription_opt (
  subid varchar(32),
  opt_name varchar(32),
  opt_value text
) TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;
CREATE UNIQUE INDEX i_pubsub_subscription_opt ON pubsub_subscription_opt(subid, opt_name);

CREATE TABLE muc_room (
    name varchar(50) CHARACTER SET latin1 NOT NULL,
    host varchar(50) CHARACTER SET latin1 NOT NULL,
    opts text NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;

CREATE UNIQUE INDEX i_muc_room_name_host USING BTREE ON muc_room(name, host);

CREATE TABLE muc_registered (
    jid varchar(50) CHARACTER SET latin1 NOT NULL,
    host varchar(50) CHARACTER SET latin1 NOT NULL,
    nick varchar(50) NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;

CREATE INDEX i_muc_registered_nick USING BTREE ON muc_registered(nick);
CREATE UNIQUE INDEX i_muc_registered_jid_host USING BTREE ON muc_registered(jid, host);

CREATE TABLE irc_custom (
    jid varchar(50) CHARACTER SET latin1 NOT NULL,
    host varchar(50) CHARACTER SET latin1 NOT NULL,
    data text NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;

CREATE UNIQUE INDEX i_irc_custom_jid_host USING BTREE ON irc_custom(jid, host);

CREATE TABLE motd (
    username varchar(50) CHARACTER SET latin1 PRIMARY KEY,
    xml text,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8 TABLESPACE ts_we_im_1 STORAGE DISK ENGINE NDBCLUSTER;
