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
		$par = str_replace( '_', ' ', $par );
		$dbr = wfGetDB( DB_REPLICA );
		$title = Title::newFromText( $par );
		if ( $title->getNamespace() === NS_REVISION && $title->exists() ) {
			$par = $dbr->selectField(
				'revision',
				'rev_remote_pfx_title',
				array( 'rev_remote_rev' => $title->getDBkey() )
			);
		}
		$res = $dbr->select(
			array( 'revision', 'tag_summary' ),
			array( 'rev_id', 'rev_remote_rev', 'rev_timestamp', 'rev_user_text',
				'rev_len',  'rev_comment', 'ts_tags' ),
			array( 'rev_remote_pfx_title' => $par ),
			__METHOD__,
			array( 'ORDER BY' => 'rev_timestamp DESC', 'LIMIT' => 500 ),
			array( 'tag_summary' => array( 'LEFT JOIN', array(
				'rev_id=ts_rev_id' ) ) )
		);
		$wikitext = "==[[$par]]==\n";
		$wikitext .= '{| class=' . '"' . 'wikitable' . '"' ."\n|-\n"
			. "!Date/time\n!User\n!Length\n!Comment\n!Tags\n";
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
			$wikitext .= "|-\n";
			$wikitext .= "|[[Revision:" . $row->rev_remote_rev . '|' . $row->rev_timestamp . "]]\n";
			$wikitext .= "|[[User:" . $row->rev_user_text . '|' . $row->rev_user_text . "]]" .
				' <sup>([[User talk:' .
				$row->rev_user_text . "|Talk]]) ([[Special:Contributions/" .
				$row->rev_user_text . "|Contribs]])</sup>\n";
			$wikitext .= "|" . $lengths[$row->rev_id] . "\n";
			$wikitext .= "|" . $row->rev_comment . "\n";
			$wikitext .= "|" . $row->ts_tags . "\n";
		}
		if ( $empty ) {
			$output->addWikiText( "Page '''[[$par]]'''" . " not found" );
			return;
		}
		$wikitext .= "|-\n|}";
		$output->addWikiText ( $wikitext );
	}
}