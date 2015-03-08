<?php

/*
 * 
 * unbounce a campaign, commandline only
 * 
 * purpose: remove all traces of bounces to a certain campaign
 * 
 * usage: phplist -mlistclearer -punbounce id=XX
 */

ob_end_flush();

if (!defined('PHPLISTINIT')) die();

$campaignID = sprintf('%d',$_GET['id']);

if (empty($campaignID)) {
  print clineUsage('id=CAMPAIGNID');
}

cl_output(s('Unbouncing campaign %d',$campaignID));

$count['bounces removed'] = 0;
$count['subscribers unblacklisted'] = 0;
$bouncesReq = Sql_Query(sprintf('select user,bounce,message from %s where message = %d',$GLOBALS['tables']['user_message_bounce'],$campaignID));
while ($bounce = Sql_Fetch_Assoc($bouncesReq)) {
  if (isBlackListedId($bounce['user'])) {
    unblacklist($bounce['user']);
    $count['subscribers unblacklisted']++;
  }
  Sql_Query(sprintf('delete from %s where id = %d',$GLOBALS['tables']['bounce'],$bounce['bounce']));
  $count['bounces removed']++;
  Sql_Query(sprintf('delete from %s where user = %d and message = %d and bounce = %d',$GLOBALS['tables']['user_message_bounce'],$bounce['user'],$bounce['message'],$bounce['bounce']));
}
cl_output(s('%d subscribers unblacklisted, %d bounces removed',$count['subscribers unblacklisted'],$count['bounces removed']));
