<?php
namespace Craft;

/**
 *
 *
 * @author    Make with Morph. <support@makewithmorph.com>
 * @copyright Copyright (c) 2015, Luke Holder.
 * @license   http://makewithmorph.com/market/license Market License Agreement
 * @see       http://makewithmorph.com
 * @package   craft.plugins.market.controllers
 * @since     0.1
 */
class Market_PlansController extends Market_BaseController
{

	public function init()
	{
		parent::init();
	}

	public function actionIndex()
	{
		$plans = craft()->market_plans->getPlans();
		$this->renderTemplate('market/plans/index', compact('plans'));
	}
} 