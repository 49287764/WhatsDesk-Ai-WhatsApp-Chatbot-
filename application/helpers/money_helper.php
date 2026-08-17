<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Format an amount for display: whole numbers show no decimals
 * (e.g. "Rs. 450"), fractional amounts keep two decimals (e.g. "$29.99").
 * Used everywhere prices appear so currencies like rupees look natural.
 */
if ( ! function_exists('money_fmt'))
{
	function money_fmt($amount)
	{
		$amount = (float)$amount;
		return ($amount == floor($amount)) ? number_format($amount, 0) : number_format($amount, 2);
	}
}
