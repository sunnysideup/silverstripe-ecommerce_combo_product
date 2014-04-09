<?php


/**
 * author: Nicolaas - modules [at] sunnysideup.co.nz

	 do not forget to add the modifier to the array of modifers, in case you want to use it:

	 in mysite/_config.php:
	 Config::inst()->update("Order", "modifiers", "CombinationProductModifier");

	 or BETTER, in mysite/_config/config.yml (or similar):

	Order:
		modifiers: [
			...
			CombinationProductModifier
		]

*/
