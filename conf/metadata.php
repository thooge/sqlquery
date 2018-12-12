<?php
/**
 * Options for the sqlquery plugin
 *
 * @author George Pirogov <i1557@yandex.ru>
 */

$meta['type'] = array('multichoice', '_choices' => array('mysql', 'dblib'));
$meta['Host'] = array('string');
$meta['DB'] = array('string');
$meta['user'] = array('string');
$meta['password'] = array('string');
