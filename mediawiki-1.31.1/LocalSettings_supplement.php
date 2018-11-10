<?php
wfLoadExtension( 'Scribunto' );
$wgScribuntoDefaultEngine = 'luastandalone';

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
	$dbw = wfGetDB( DB_MASTER );															# SD
	$vars = array();																		# SD
	$pageVars = array();																	# SD
	if( isset( $wgSdTags ) ) {
		$csvTags = '';
		$firstOne = true;
		$wgSdTags = explode( '|', $wgSdTags );
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
		$dbw->insert(
			'tag_summary',
			array(
				'ts_rev_id' => $revision->getId(),
				'ts_tags' => $csvTags
			)
		);	
	}
	if( isset( $wgSdTimestamp ) ) {															# SD
		$vars['rev_timestamp'] = $wgSdTimestamp;											# SD
	}																						# SD
	if( isset( $wgSdUser ) ) {																# SD
		$vars['rev_user'] = 0;																# SD
		$vars['rev_user_text'] = $wgSdUser;													# SD
		$vars['rev_remote_user'] = $wgSdUserId;												# SD
	}																						# SD
	if( isset( $wgSdNamespace ) ) {															# SD
		$vars['rev_remote_namespace'] = $wgSdNamespace;										# SD
		$pageVars['page_remote_namespace'] = $wgSdNamespace;								# SD
	}																						# SD
	if( isset( $wgSdTitle ) ) {																# SD
		$vars['rev_remote_pfx_title'] = $wgSdTitle;											# SD
		$remoteTitle = Title::newFromText( $wgSdTitle );									# SD
		$unprefixedTitle = $remoteTitle->getDBkey();										# SD
		$pageVars['page_remote_title'] = $unprefixedTitle;									# SD
	}																						# SD
	if( isset( $wgSdPage ) ) {																# SD
		$vars['rev_remote_page'] = $wgSdPage;												# SD
	}																						# SD
	if( isset( $wgSdRemoteRev ) ) {															# SD
		$vars['rev_remote_rev'] = $wgSdRemoteRev;											# SD
	}																						# SD
	if ( !$vars ) {																			# SD
		return true;																		# SD
	}																						# SD
	$dbw->update( 'revision', $vars, array( 'rev_id' => $revision->getId() ) );				# SD
	if( $pageVars ) {																		# SD
		$dbw->update( 'page', $pageVars, array( 'page_latest' => $revision->getId() ) );	# SD
	}																						# SD
	return true;																			# SD
}																							# SD

$wgHooks['InitializeArticleMaybeRedirect'][] = 'onInitializeArticleMaybeRedirect';
function onInitializeArticleMaybeRedirect( &$title, &$request, &$ignoreRedirect, &$target, &$article ) {
	if ( $title->getNamespace() !== 0 ) {
		return;
	}
	$dbr = wfGetDB( DB_REPLICA );
	$pageId = $dbr->selectField(
		'revision',
		'rev_page',
		array( 'rev_remote_pfx_title' => $title->getPrefixedText() ),
		__METHOD__,
		array( 'ORDER BY' => 'rev_timestamp DESC' )
	);
	if ( !$pageId ) {
		return;
	}
	$newTitle = Title::newFromID( $pageId );
	$target = $newTitle->getCanonicalURL();
	return;
}

$wgHooks['HtmlPageLinkRendererBegin'][] = 'sdOnHtmlPageLinkRendererBegin';
function sdOnHtmlPageLinkRendererBegin( $linkRenderer, $target, &$text, &$extraAttribs, &$query,
	&$ret ) {
	$title = Title::newFromLinkTarget( $target );
	$dbr = wfGetDB( DB_REPLICA );
	$nonUnderscored = str_replace( '_', ' ', $title->getPrefixedText() );
	if ( !$title->isKnown() ) {
		$exists = $dbr->selectField(
			'revision',
			'rev_id',
			array( 'rev_remote_pfx_title' => $nonUnderscored )
		);
		
		if ( $exists ) {
			$html = HtmlArmor::getHtml( $text );
			$newTitle = Title::newFromText( $title->getPrefixedText() );
			$extraAttribs['href'] = $newTitle->getCanonicalURL();
			$ret = Html::rawElement( 'a', $extraAttribs, $html );
			return false;
		}
	}
	return true;
}

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

$wgHooks['BeforeParserFetchTemplateAndtitle'][] = 'onBeforeParserFetchTemplateAndtitle';
function onBeforeParserFetchTemplateAndtitle( $parser, $title, &$skip, &$id ) {
	$dbr = wfGetDB( DB_REPLICA );
	$revId = $dbr->selectField(
		'revision',
		'rev_id',
		array( 'rev_remote_pfx_title' => $title->getPrefixedText() ),
		__METHOD__,
		array( 'ORDER BY' => 'rev_timestamp DESC' )
	);
	if ( !$revId ) {
		return;
	}
	$id = $revId;
	#$title = $rev->getTitle();
	return;
}

$wgShowSQLErrors = true;
$wgDebugDumpSql  = true;
$wgShowDBErrorBacktrace = true;
$wgShowExceptionDetails = true;
$wgDebugLogFile = "$IP/error.log";
