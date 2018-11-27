<?php
class SpecialRevisionList extends SpecialPage {
	function __construct() {
		parent::__construct( 'RevisionList' );
	}

	function execute( $par ) {
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();
		$param = $request->getText( 'param' );
		if ( !$par ) {
			$output->addWikiText( 'Please use a parameter' );
			return;
		}
		#$par = str_replace( '_', ' ', $par );
		$dbr = wfGetDB( DB_REPLICA );
		$title = Title::newFromText( $par );
		if ( $title->getNamespace() === NS_REVISION && $title->exists() ) {
			$row = $dbr->selectRow(
				'revision',
				array( 'rev_id', 'rev_remote_namespace', 'rev_remote_title' ),
				array( 'rev_page' => $title->getArticleID() )
			);
			#die( $row->rev_id );
			$remoteTitleValue = new TitleValue( intval( $row->rev_remote_namespace), $row->rev_remote_title );
			$remoteTitle = Title::newFromTitleValue( $remoteTitleValue );
			$par = $remoteTitle->getPrefixedText();
			$title = Title::newFromText( $par );
		}
		$res = $dbr->select(
			array( 'revision', 'tag_summary' ),
			array( 'rev_id', 'rev_remote_rev', 'rev_timestamp', 'rev_minor_edit', 'rev_user_text',
				'rev_len',  'rev_comment', 'rev_deleted', 'ts_tags' ),
			array(
				'rev_remote_namespace' => $title->getNamespace(),
				'rev_remote_title' => $title->getDBkey()
			), __METHOD__,
			array( 'ORDER BY' => 'rev_timestamp DESC', 'LIMIT' => 500 ),
			array( 'tag_summary' => array( 'LEFT JOIN', 'rev_id=ts_rev_id' ) )
		);
		$wikitext = "==[[$par]]==\n";
		$wikitext .= '{| class=' . '"' . 'wikitable' . '"' ."\n|-\n"
			. "!Date/time\n!User\n!m\n!Length\n!Change\n!Comment\n!Tags\n";
		$empty = true;
		$lengths = array();
		foreach ( $res as $row ) {
			$lengths[ $row->rev_id ] = $row->rev_len;
			if ( isset( $lastRevId ) ) {
				$lengths[ $lastRevId ] -= $row->rev_len;
			}
			$lastRevId = $row->rev_id;
		}
		foreach ( $lengths as $key => $length ) {
			if ( $length > 0 ) {
				$lengths[$key] = '<span style="color:green;">+' . $lengths[$key] . '</span>';
			}
			if ( $length < 0 ) {
				$lengths[$key] = '<span style="color:red;">' . $lengths[$key] . '</span>';
			}
			if ( $length <= -500 || $length >= 500 ) {
				$lengths[$key] = "'''" . $lengths[$key] . "'''";
			}
			$lengths[$key] = '<div style="text-align: right;">' . $lengths[$key];
			$lengths[$key] .= '</div>';
		}
		foreach ( $res as $row ) {
			$empty = false;
			$wikitext .= "|-\n|";
			if ( $row->rev_deleted & (1 << 0) ) {
				$wikitext .= '<s>';
			}
			$wikitext .= "[[Revision:" . $row->rev_remote_rev . '|' . $row->rev_timestamp . "]]";
			if ( $row->rev_deleted & (1 << 0) ) {
				$wikitext .= '</s>';
			}
			$wikitext .= "\n";
			if ( $row->rev_deleted & (1 << 2) ) {
				$wikitext .= "|<s>''(Username or IP removed)‎''</s>\n";
			} else {
				$wikitext .= "|[[User:" . $row->rev_user_text . '|' . $row->rev_user_text . "]]" .
					' <sup>([[User talk:' .
					$row->rev_user_text . "|Talk]]) ([[Special:Contributions/" .
					$row->rev_user_text . "|Contribs]])</sup>\n";
			}
			$wikitext .= '|';
			if ( $row->rev_minor_edit ) {
				$wikitext .= "'''m'''";
			}
			$wikitext .= "\n|" . $row->rev_len;
			$wikitext .= "\n|" . $lengths[$row->rev_id] . "\n";
			if ( $row->rev_deleted & (1 << 1) ) {
				$wikitext .= "|<s>''(Edit summary removed)''</s>\n";
			} else {
				$thisComment = str_replace( '+', '<nowiki>+</nowiki>', $row->rev_comment );
				$thisComment = str_replace( '|', '<nowiki>|</nowiki>', $row->rev_comment );
				$wikitext .= "|" . $thisComment . "\n";
			}
			$wikitext .= "|" . $row->ts_tags . "\n";
		}
		if ( $empty ) {
			$output->addWikiText( "Page '''[[$par]]'''" . " not found" );
			return;
		} else {
			$wikitext .= "|-\n|}";
		}
		$output->addWikiText ( $wikitext );
	}
}