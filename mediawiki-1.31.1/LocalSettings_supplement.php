<?php
define("NS_PORTAL", 100);
define("NS_PORTAL_TALK", 101);
define("NS_BOOK", 108);
define("NS_BOOK_TALK", 109);
define("NS_DRAFT", 118);
define("NS_DRAFT_TALK", 119);
define("NS_TIMEDTEXT", 710);
define("NS_TIMEDTEXT_TALK", 711);
define("NS_REVISION", 1000);
define("NS_REVISION_TALK", 1001);
$wgExtraNamespaces[NS_PORTAL] = "Revision";
$wgExtraNamespaces[NS_PORTAL_TALK] = "Revision_talk";   # underscore required
$wgExtraNamespaces[NS_BOOK] = "Book";
$wgExtraNamespaces[NS_BOOK_TALK] = "Book_talk";   # underscore required
$wgExtraNamespaces[NS_DRAFT] = "Draft";
$wgExtraNamespaces[NS_DRAFT_TALK] = "Draft_talk";   # underscore required
$wgExtraNamespaces[NS_TIMEDTEXT] = "TimedText";
$wgExtraNamespaces[NS_TIMEDTEXT_TALK] = "TimedText_talk";   # underscore required
$wgExtraNamespaces[NS_REVISION] = "Revision";
$wgExtraNamespaces[NS_REVISION_TALK] = "Revision_talk";   # underscore required
$wgSdUseTouch = false;
#$wgSdMinor = false;

$wgHooks['PageContentSaveComplete'][] = 'sdOnPageContentSaveComplete';						# SD
function sdOnPageContentSaveComplete( $article, $user, $content, $summary,					# SD
	$isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId ) {				# SD
	global $wgSdTimestamp;																	# SD
	global $wgSdUser;																		# SD
	global $wgSdUserId;																		# SD
	global $wgSdNamespace;																	# SD
	global $wgSdTitle;																		# SD
	global $wgSdPage;																		# SD
	global $wgSdTags;																		# SD
	global $wgSdRemoteRev;																	# SD
	global $wgSdDeleted;																	# SD
	global $wgSdSize;																		# SD
	global $wgSdTouch;																		# SD
	global $wgSdUseTouch;
	global $wgSdLocalRevId;
	global $wgSdMinor;
	$dbw = wfGetDB( DB_MASTER );															# SD
	$vars = array();																		# SD
	$pageVars = array();																	# SD
	if( isset( $wgSdTags ) ) {
		$csvTags = '';
		$firstOne = true;
		#file_put_contents ( "/var/www/html/testdata.txt", $wgSdTags );
		$wgSdTags = explode( '|', $wgSdTags );
		if ( is_array( $wgSdTags ) ) {
			$dbw->delete(
					'change_tag',
					array( 'ct_rev_id' => $revision->getId() )
			);
			foreach ( $wgSdTags as $wgSdTag ) {	
				$dbw->insert(
					'change_tag',
					array(
						'ct_rev_id' => $revision->getId(),
						'ct_tag' => $wgSdTag
					)
				);
				if ( $firstOne ) {
					$firstOne = false;
				} else {
					$csvTags .= ',';
				}
				$csvTags .= $wgSdTag;
			}
			if ( $csvTags ) {
				$dbw->delete(
					'tag_summary',
					array( 'ts_rev_id' => $revision->getId() )
				);
				$dbw->insert(
					'tag_summary',
					array(
						'ts_rev_id' => $revision->getId(),
						'ts_tags' => $csvTags
					)
				);
			}
		}
		#file_put_contents ( "/var/www/html/testdata.txt", implode(" ",$wgSdTags) );
		#file_put_contents ( "/var/www/html/testdata.txt", $wgSdTags );
	}
	if( isset( $wgSdTimestamp ) ) {															# SD
		$vars['rev_timestamp'] = $wgSdTimestamp;											# SD
	}																						# SD*/
	if( isset( $wgSdUser ) ) {																# SD
		$vars['rev_user'] = 0;																# SD
		$vars['rev_user_text'] = $wgSdUser;													# SD
		$vars['rev_remote_user'] = $wgSdUserId;												# SD
	}																						# SD
	if( isset( $wgSdNamespace ) ) {															# SD
		$vars['rev_remote_namespace'] = $wgSdNamespace;										# SD
		#$pageVars['page_remote_namespace'] = $wgSdNamespace;								# SD
	}																						# SD
	if( isset( $wgSdTitle ) ) {																# SD
		$remoteTitle = Title::newFromText( $wgSdTitle );									# SD
		$unprefixedTitle = $remoteTitle->getDBkey();										# SD
		$vars['rev_remote_title'] = $unprefixedTitle;										# SD
	}																						# SD
	if( isset( $wgSdPage ) ) {																# SD
		$vars['rev_remote_page'] = $wgSdPage;												# SD
	}																						# SD
	if( isset( $wgSdRemoteRev ) ) {															# SD
		$vars['rev_remote_rev'] = $wgSdRemoteRev;											# SD
	}																						# SD
	if( isset( $wgSdMinor ) && $wgSdMinor ) {												# SD
		$vars['rev_minor_edit'] = 1;														# SD
		#$pageVars['page_remote_namespace'] = $wgSdNamespace;								# SD
	}																						# SD
	/*if( isset( $wgTouched ) ) {																# SD
		$pageVars['page_touched'] = $wgTouched;												# SD
	}																						# SD*//*
	if( isset( $wgSdDeleted ) ) {															# SD
		$vars['rev_deleted'] = $wgSdDeleted;												# SD
		$vars['rev_len'] = $wgSdSize;														# SD
	}																						# SD
	die( 'foo' );
	if ( !$vars ) {																			# SD
		return true;																		# SD
	}																				*/		# SD
	$dbw->update( 'revision', $vars, array( 'rev_id' => $revision->getId() ) );				# SD
	#$dbw->update( 'revision', $vars, array( 'rev_id' => $wgSdLocalRevId ) );				# SD
	/*if ( $pageVars ) {
		$dbw->update( 'page', $pageVars, array( 'page_id' => $revision->getPage() ) );				# SD
	}*/
	# Disable this because we don't know what we're doing
	/*if ( $wgSdTouch && $wgSdUseTouch ) {
		$res = $dbw->select(
			array( 'pagelinks' ),
			array( 'pl_from' ),
			array(
				'pl_namespace' => $wgSdNamespace,
				'pl_title' => $unprefixedTitle
			)
		);
		#$contents = '';
		foreach ( $res as $row ) {
			$contents .= $row->pl_from . "\n";
			$dbw->update(
				'page',
				#array( 'page_touched' => '20181212121212' ),
				array( 'page_touched' => $revision->getTimestamp() ),
				#array( 'page_touched' => $dbw->timestamp() ),
				array( 'page_id' => $row->pl_from )
				#array( '1=1' )
			);
		}*/
		#file_put_contents ( "/var/www/html/testdata.txt", $contents );
	#}
	#$dbw->update( 'revision', array( 'rev_user_text' => 'Foo' ), array( 'rev_id' => $revision->getId() ) );				# SD
	return true;																			
}

# For when you click on blue links
$wgHooks['InitializeArticleMaybeRedirect'][] = 'onInitializeArticleMaybeRedirect';
function onInitializeArticleMaybeRedirect( &$title, &$request, &$ignoreRedirect, &$target, &$article ) {
	if ( $title->getNamespace() !== 0 ) {
		return;
	}
	$dbr = wfGetDB( DB_REPLICA );
	$pageId = $dbr->selectField(
		'revision',
		'rev_page',
		array(
			  'rev_remote_namespace' => $title->getNamespace(),
			  'rev_remote_title' => $title->getDBkey()
		), __METHOD__,
		array( 'ORDER BY' => 'rev_timestamp DESC' )
	);
	if ( !$pageId ) {
		return;
	}
	$newTitle = Title::newFromID( $pageId );
	$target = $newTitle->getCanonicalURL();
	return;
}

# Links will appear blue
$wgHooks['HtmlPageLinkRendererBegin'][] = 'sdOnHtmlPageLinkRendererBegin';
function sdOnHtmlPageLinkRendererBegin( $linkRenderer, $target, &$text, &$extraAttribs, &$query,
	&$ret ) {
	$title = Title::newFromLinkTarget( $target );
	$dbr = wfGetDB( DB_REPLICA );
	#$nonUnderscored = str_replace( '_', ' ', $title->getPrefixedText() );
	if ( !$title->isKnown() ) {
		$exists = $dbr->selectField(
			'revision',
			'rev_id',
			array(
				'rev_remote_namespace' => $title->getNamespace(),
				'rev_remote_title' => $title->getDBkey()
			)
		);
		
		if ( $exists ) {
			$html = HtmlArmor::getHtml( $text );
			#$newTitle = Title::newFromText( $title->getPrefixedText() );
			$extraAttribs['href'] = $title->getCanonicalURL();
			$ret = Html::rawElement( 'a', $extraAttribs, $html );
			return false;
		}
	}
	return true;
}

# Purge extension
$wgHooks['SkinTemplateNavigation::Universal'][] = 'PurgeActionExtension::contentHook';
class PurgeActionExtension {
	public static function contentHook( $skin, array &$content_actions ) {
		global $wgRequest, $wgUser;
		// Use getRelevantTitle if present so that this will work on some special pages
		$title = method_exists( $skin, 'getRelevantTitle' ) ?
			$skin->getRelevantTitle() : $skin->getTitle();
		if ( $title->getNamespace() !== NS_SPECIAL && $wgUser->isAllowed( 'purge' ) ) {
			$action = $wgRequest->getText( 'action' );
			$content_actions['actions']['purge'] = array(
				'class' => $action === 'purge' ? 'selected' : false,
				'text' => 'Purge',
				'href' => $title->getLocalUrl( 'action=purge' )
			);
		}
		return true;
	}
}

# "Incoming" tab
$wgHooks['SkinTemplateNavigation::Universal'][] = 'IncomingActionExtension::contentHook';
class IncomingActionExtension {
	public static function contentHook( $skin, array &$content_actions ) {
		global $wgRequest, $wgUser;
		// Use getRelevantTitle if present so that this will work on some special pages
		$title = method_exists( $skin, 'getRelevantTitle' ) ?
			$skin->getRelevantTitle() : $skin->getTitle();
		if ( $title->getNamespace() !== NS_REVISION ) {
			return true;
		}
		if ( $title->getNamespace() !== NS_SPECIAL && $wgUser->isAllowed( 'purge' ) ) {
			$action = $wgRequest->getText( 'action' );
			$specialTitle = Title::newFromText( 'Special:IncomingLinks/' . $title->getPrefixedText() );
			$content_actions['actions']['incoming'] = array(
				'class' => false,
				'text' => 'Incoming',
				'href' => $specialTitle->getLocalUrl()
			);
		}
		return true;
	}
}

# Template transclusion
$wgHooks['BeforeParserFetchTemplateAndtitle'][] = 'onBeforeParserFetchTemplateAndtitle';
function onBeforeParserFetchTemplateAndtitle( $parser, $title, &$skip, &$id ) {
	$dbr = wfGetDB( DB_REPLICA );
	$revId = $dbr->selectField(
		'revision',
		'rev_id',
		array(
			  'rev_remote_namespace' => $title->getNamespace(),
			  'rev_remote_title' => $title->getDBkey(),
		), __METHOD__,
		array( 'ORDER BY' => 'rev_timestamp DESC' )
	);
	if ( !$revId ) {
		return;
	}
	$id = $revId;
	#$title = $rev->getTitle();
	return;
}

# Displaytitle
$wgHooks['ParserBeforeStrip'][] = 'onParserBeforeStrip';
function onParserBeforeStrip( &$parser, &$text, &$strip_state ) {
	$title = $parser->getTitle();
	if ( $title->getNamespace() != NS_REVISION ) {
		return true;
	}
	$dbr = wfGetDB( DB_REPLICA );
	$row = $dbr->selectRow(
		'revision',
		array( 'rev_remote_namespace', 'rev_remote_title' ),
		array( 'rev_remote_rev' => $title->getDBkey() )
	);
	if ( !$row ) {
		return true;
	}
	$titleValue = new TitleValue( intval( $row->rev_remote_namespace ), $row->rev_remote_title );
	$newTitle = Title::newFromTitleValue( $titleValue );
	$displayTitle = $parser->getOutput()->getDisplayTitle();
	$parser->mOutput->setDisplayTitle( 'Revision:' . str_replace( '_', ' ', $title->getDBkey() )
		. ' (' . str_replace( '_', ' ', $newTitle->getPrefixedText() ) . ')' );
	return true;
}

$wgGroupPermissions['sysop']['deleterevision'] = true;

$wgImportSources = array(
      'wikipedia'
);

$wgShowSQLErrors = true;
$wgDebugDumpSql  = true;
$wgShowDBErrorBacktrace = true;
$wgShowExceptionDetails = true;
$wgDebugLogFile = "/var/www/html/error.log";
