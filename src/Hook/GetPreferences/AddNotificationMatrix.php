<?php

namespace BlueSpice\EchoConnector\Hook\GetPreferences;

use BlueSpice\Hook\GetPreferences;
use MediaWiki\MediaWikiServices;
use Message;

class AddNotificationMatrix extends GetPreferences {

	/** @var array  */
	private $actions = [ 'create', 'edit' ];
	/** @var array|null */
	protected $categories = null;

	protected function doProcess() {
		$contLang = $this->getContext()->getLanguage();

		// Add namespace selection matrix
		$arrNamespaces = [];

		foreach ( $contLang->getFormattedNamespaces() as $ns => $title ) {
			if ( $ns > 0 ) {
				$arrNamespaces[ $title ] = $ns;
			} elseif ( $ns == 0 ) {
				$arrNamespaces[ Message::newFromKey( 'bs-ns_main' )->text() ] = $ns;
			}
		}

		$this->preferences[ 'notify-namespace-selection' ] = [
			'type' => 'checkmatrix',
			'section' => 'echo/namespace-notifications',
			// a system message (optional)
			'help-message' => 'tog-help-notify-namespace-selection',
			'rows' => $arrNamespaces,
			'columns' => $this->getColumns()
		];

		$cats = $this->getAvailableCategories();
		if ( count( $cats ) < 1 ) {
			return true;
		}
		$rows = [];
		foreach ( $cats as $cat ) {
			$rows[str_replace( '_', ' ', $cat )] = $cat;
		}
		$this->preferences[ 'notify-category-selection' ] = [
			'type' => 'checkmatrix',
			'section' => 'echo/category-notifications',
			'rows' => $rows,
			'columns' => $this->getColumns(),
		];

		return true;
	}

	/**
	 * @return array
	 */
	private function getColumns() {
		$columns = [];
		foreach ( $this->actions as $action ) {
			$columns[ wfMessage( $action )->text() ] = "page-" . $action;
		}
		return $columns;
	}

	/**
	 * @return array|null
	 */
	private function getAvailableCategories() {
		if ( $this->categories === null ) {
			$db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
				DB_REPLICA
			);
			$res = $db->select(
				'category',
				'cat_title',
				[],
				__METHOD__
			);
			$this->categories = [];
			foreach ( $res as $row ) {
				$this->categories[] = $row->cat_title;
			}
		}

		return $this->categories;
	}
}
