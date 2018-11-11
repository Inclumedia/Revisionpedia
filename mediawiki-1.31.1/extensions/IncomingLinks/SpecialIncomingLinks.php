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
			$par = $dbr->selectField(
				'revision',
				'rev_remote_pfx_title',
				array( 'rev_remote_rev' => $title->getDBkey() ) # Use page_title instead, since it's already indexed?
			);
		}
		$res = $dbr->select(
			# TODO: Add revision to this
			array( 'pagelinks', 'page' ),
			array( 'page_title', 'page_remote_namespace', 'page_remote_title' ), # TODO: Use revision fields
			array(
				'pl_namespace' => $title->getNamespace(),
				'pl_title' => $title->getDBkey(),
				'page_namespace' => NS_REVISION
			),
			__METHOD__,
			array( # TODO: Change this to use rev_remote_pfx_title, rev_timestamp, which are already indexed
				array( 'ORDER BY' => 'page_remote_namespace ASC, page_remote_remote_title ASC, page_title ASC' ),
				array( 'LIMIT' => 500 )
			), array( 'page' => array( 'INNER JOIN', array(
				'pl_from=page_id'
			) ) )
		);
		$wikitext = "==[[$par]]==\n";
		$empty = true;
		$previousTitle = '';
		foreach ( $res as $row ) {
			$empty = false;
			#die ( $row->pl_from );
			#die ( $row->page_remote_namespace );
			$titleValue = new TitleValue( intval( $row->page_remote_namespace ), $row->page_remote_title );
			#die('foo');
			$title = Title::newFromTitleValue( $titleValue );
			if ( $title != $previousTitle ) {
				$wikitext .= '*[[' . $title->getPrefixedText() . "]]\n";
			}
			$wikitext .= ':*[[Revision:' . $row->page_title . "]]\n";
		}
		if ( $empty ) {
			$output->addWikiText( "No incoming links to page '''[[$par]]'''" . " were found" );
			return;
		}
		$output->addWikiText ( $wikitext );
	}
}
