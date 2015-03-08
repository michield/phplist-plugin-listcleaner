<?php

/**
 * list cleaner plugin
 * 
 * automatically modify the status of subscribers based on certain criteria
 * 
 * 
 * v.0.2 2013-08-26 - added "listcleaner_autoconfirm_lists" to allow auto-confirmation of members of lists
 * v 0.1 2013-08-19 - initial version
 * License GPLv3+
 */ 


class listcleaner extends phplistPlugin {
  public $name = "List cleaner plugin for phpList";
  public $coderoot = 'listcleaner/';
  public $version = "0.2";
  public $authors = 'Michiel Dethmers';
  public $enabled = 1;
  public $description = 'Automatically clean up list-membership';
  public $commandlinePluginPages= array (
    'unbounce'
  );

  private $autoconfirm = false;
  private $autoconfirmLists = array();
  private $forcehtml = false;
  private $removeblacklisted = false;

  public $settings = array(
    "listcleaner_autoconfirm" => array (
      'value' => true,
      'description' => 'Automatically mark subscribers confirmed',
      'type' => "boolean",
      'allowempty' => 1,
      'category'=> 'subscribers',
    ),
    "listcleaner_autoconfirm_lists" => array (
      'value' => ' ',
      'description' => 'Automatically mark subscribers confirmed who are member of these lists',
      'type' => "text",
      'allowempty' => 1,
      'category'=> 'subscribers',
    ),
    "listcleaner_forcehtml" => array (
      'value' => true,
      'description' => 'Automatically set subscribers to receive HTML',
      'type' => "boolean",
      'allowempty' => 1,
      'category'=> 'subscribers',
    ),
    "listcleaner_removeblacklisted" => array (
      'value' => false,  
      'description' => 'Remove blacklisted subscribers from lists',
      'type' => "boolean",
      'allowempty' => 1,
      'category'=> 'subscribers',
    ),
  );

  function __construct() {
    parent::phplistplugin();
    $this->autoconfirm = (boolean)getConfig('listcleaner_autoconfirm');
    $this->forcehtml = (boolean)getConfig('listcleaner_forcehtml');
    $this->removeblacklisted = (boolean)getConfig('listcleaner_removeblacklisted');
    $this->coderoot = dirname(__FILE__).'/listcleaner/';
    $lists = explode(',', getConfig('listcleaner_autoconfirm_lists'));
    foreach ($lists as $listID) {
      $this->autoconfirmLists[] = (int)$listID;
    }
  }

  function adminmenu() {
    return array(
    );
  }
  
  function upgrade($previous) {
    parent::upgrade($previous);
    return true;
  }
  
  function autoConfirm() {
    if ($this->autoconfirm) {
      Sql_Query(sprintf('update %s set confirmed = 1',$GLOBALS['tables']['user']));
    }
    if (sizeof($this->autoconfirmLists)) {
      Sql_Query(sprintf('update %s set confirmed = 1 where id in (select distinct userid from %s where listid in (%s))',$GLOBALS['tables']['user'],$GLOBALS['tables']['listuser'],join(',',$this->autoconfirmLists)));
    }
      
  }
  
  function sendReport($subject,$message) {
   # return true;
  }
  
  function forceHTML() {
    Sql_Query(sprintf('update %s set htmlemail = 1',$GLOBALS['tables']['user']));
  }
  
  function removeBlacklisted() {
    ## run across the blacklist table and mark profiles blacklisted
    $req = Sql_Query(sprintf('select email from %s',$GLOBALS['tables']['user_blacklist']));
    while ($row = Sql_Fetch_Assoc($req)) {
      Sql_Query(sprintf('update %s set blacklisted = 1 where email = "%s"',$GLOBALS['tables']['user'],$row['email']));
    }

    ## this would be nice, but Mysql doesn't take update tables being used in the select
    ## delete from phplist_listuser where userid in (select u.id from phplist_listuser lu, phplist_user_user u where lu.userid = u.id and u.blacklisted);

    $req = Sql_Query(sprintf('select lu.listid,u.id from %s lu, %s u where lu.userid = u.id and u.blacklisted',$GLOBALS['tables']['listuser'],$GLOBALS['tables']['user']));
    while ($row = Sql_Fetch_Assoc($req)) {
      Sql_Query(sprintf('delete from %s where userid = %d',$GLOBALS['tables']['listuser'],$row['id']));
    }
  }

  /* hook when the processqueue process is started */
  function processQueueStart() {
    $this->campaignStarted();
  }
  
  /* hook when a campaign is being processed */
  function campaignStarted($messagedata = array()) {
    if ($this->autoconfirm) {
      $this->autoConfirm();
    }
    if ($this->forcehtml) {
      $this->forceHTML();
    }
    if ($this->removeblacklisted) {
      $this->removeBlacklisted();
    }
  }

  /* hook when a campaign is placed in the queue */
  function messageQueued($messageid) {
    if ($this->autoconfirm) {
      $this->autoConfirm();
    }
    if ($this->forcehtml) {
      $this->forceHTML();
    }
    if ($this->removeblacklisted) {
      $this->removeBlacklisted();
    }
      
  }
  
}
