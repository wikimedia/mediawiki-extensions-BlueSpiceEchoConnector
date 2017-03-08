<?php

class BsEchoHTMLEmailFormatter extends EchoHTMLEmailFormatter {
	
	/**
	 * This method can be used to properly
	 * parse %%...%% params if any additional
	 * are set in template
	 */
	public function formatEmail() {
		return parent::formatEmail();
		
	}
}
