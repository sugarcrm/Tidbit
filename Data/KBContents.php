<?php
$GLOBALS['dataTool']['KBContents']['kbdocument_id'] = array('related' => array('module' => 'KBDocuments'));
$GLOBALS['dataTool']['KBContents']['kbarticle_id'] = array('related' => array('module' => 'KBArticles'));
$GLOBALS['dataTool']['KBContents']['team_id'] = array('related' => array('module' => 'Teams'));
$GLOBALS['dataTool']['KBContents']['category_id'] = array('related' => array('module' => 'Categories'));
$GLOBALS['dataTool']['KBContents']['active_rev'] =  array('value' => 1);
$GLOBALS['dataTool']['KBContents']['revision'] =  array('value' => 1);
$GLOBALS['dataTool']['KBContents']['language'] = array('list' => 'lang_array');
$GLOBALS['dataTool']['KBContents']['kbdocument_body'] = array('gibberish' => 20);
$GLOBALS['dataTool']['KBContents']['kbsapprover_id'] = array('related' => array('module' => 'Users'));
$GLOBALS['dataTool']['KBContents']['kbscase_id'] = array('related' => array('module' => 'Cases'));
$GLOBALS['dataTool']['KBContents']['description'] = array('value' => '');

$maxVotes = empty($GLOBALS['modules']['Users']) ? 1 : $GLOBALS['modules']['Users'];
$GLOBALS['dataTool']['KBContents']['useful'] =  array('range' => array('min' => 0, 'max' => $maxVotes));
$GLOBALS['dataTool']['KBContents']['notuseful'] =  array('range' => array('min' => 0, 'max' => $maxVotes));
