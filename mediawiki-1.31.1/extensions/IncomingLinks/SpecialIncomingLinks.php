<?php
class SpecialIncomingLinks extends SpecialPage {
	function __construct() {
		parent::__construct( 'IncomingLinks' );
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
		$par = str_replace( '_', ' ', $par );
		$dbr = wfGetDB( DB_REPLICA );
		$title = Title::newFromText( $par );
		if ( $title->getNamespace() === NS_REVISION && $title->exists() ) {
			$row = $dbr->selectRow(
				'revision',
				array( 'rev_remote_namespace', 'rev_remote_title' ),
				array( 'rev_page' => $title->getArticleID() )
			);
			$remoteTitleValue = new TitleValue( intval( $row->rev_remote_namespace), $row->rev_remote_title );
			$remoteTitle = Title::newFromTitleValue( $remoteTitleValue );
			$par = $remoteTitle->getPrefixedText();
			$title = Title::newFromText( $par );
		}
		#$res = $dbr->selectSQLText(
		$res = $dbr->select(
			array( 'pagelinks', 'revision', 'tag_summary' ),
			array( 'rev_id', 'rev_remote_rev', 'rev_timestamp', 'rev_user_text',
				'rev_len',  'rev_comment', 'rev_deleted', 'rev_remote_namespace',
				'rev_remote_title', 'ts_tags' ),
			array(
				'pl_namespace' => $title->getNamespace(),
				'pl_title' => $title->getDBkey()#,
				#'pl_from=rev_page'
			),
			__METHOD__,
			array(
				'ORDER BY' => array( 'rev_remote_namespace ASC', 'rev_remote_title ASC', 'rev_timestamp DESC' ),
				#'ORDER BY' => 'rev_remote_namespace ASC, rev_remote_title ASC, rev_timestamp DESC',
				'LIMIT' => 500
			), array(
				'revision' => array(
					'INNER JOIN', 'pl_from=rev_page' ),
				'tag_summary' => array(
					'LEFT JOIN', 'rev_id=ts_rev_id' )
			)
		);
		#die ( $res );
		$wikitext = "==[[$par]]==\n";
		$empty = true;
		$previousTitleText = '';
		foreach ( $res as $row ) {
			$empty = false;
			$titleValue = new TitleValue( intval( $row->rev_remote_namespace ), $row->rev_remote_title );
			$title = Title::newFromTitleValue( $titleValue );
			$titleText = $title->getPrefixedText();
			if ( $titleText != $previousTitleText ) {
				if ( $previousTitleText ) {
					$wikitext .= "|-\n|}\n";
				}
				$wikitext .= '===[[' . $titleText . "]]===\n";
				$wikitext .= '{| class=' . '"' . 'wikitable' . '"' ."\n|-\n"
					. "!Date/time\n!User\n!Length\n!Comment\n!Tags\n";
			}
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
			$wikitext .= "|" . $row->rev_len . "\n";
			if ( $row->rev_deleted & (1 << 1) ) {
				$wikitext .= "|<s>''(Edit summary removed)''</s>\n";
			} else {
				$thisComment = str_replace( '+', '<nowiki>+</nowiki>', $row->rev_comment );
				$thisComment = str_replace( '|', '<nowiki>|</nowiki>', $row->rev_comment );
				$wikitext .= "|" . $thisComment . "\n";
			}
			$wikitext .= "|" . $row->ts_tags . "\n";
			$previousTitleText = $titleText;
		}
		if ( $empty ) {
			$output->addWikiText( "No incoming links to page '''[[$par]]'''" . " were found" );
			return;
		}
		#$output->addWikiText ( '<nowiki>' . $wikitext . '</nowiki>' );
		$output->addWikiText ( $wikitext );
	}
}
