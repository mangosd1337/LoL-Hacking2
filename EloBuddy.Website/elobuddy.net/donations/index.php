<?php

require_once './init.php';

\IPS\Dispatcher\Front::i();


$member = \IPS\Member::loggedIn();
$csrfKey = \IPS\Session::i()->csrfKey;

if(!$member->member_id)
{
  header('Location: /');
  exit;
}



\IPS\Output::i()->output .= <<<EOF
<style>.cWidgetContainer {display:none;}
  #ipsLayout_mainArea{
  	min-height: 0px;
  }
  .eb_logo{
  	margin-top: -300px;
  }
  #secondaryFooter{
  	display: none;
  }
  nav.ipsBreadcrumb{
    display: none;
  }
</style>
<div class='ipsBox ipsPad' style="margin-top: -10px;">
  <h2 class="ipsType_sectionTitle ipsType_reset ipsType_medium" style="margin-left: -15px;margin-right: -15px;margin-top: -15px;">
    Donate
  </h2>
	<p class='ipsType_normal'>
    <strong>
    Help elobuddy by donating blah blah....
    </strong>;
	</p>
	<form accept-charset="utf-8" class="ipsForm ipsForm_vertical" action="https://www.elobuddy.net/clients/donations/1-/" method="post" enctype="multipart/form-data" data-ipsform="">
    <input type="hidden" name="donate_submitted" value="1">
    <input type="hidden" name="csrfKey" value="{$csrfKey}">
    <label class="ipsFieldRow_label" for="donate_amount">
      Amount to donate <span class="ipsFieldRow_required">Required</span>
    </label><br>
    <input type="number" name="donate_amount" size="5" aria-required="true" value="0" class="ipsField_short" min="0.01" step="any">
    <br><br>
    <button type="submit" class="ipsButton ipsButton_primary" tabindex="2" accesskey="s" role="button">Donate</button>
  </form>
</div>
EOF;

$template = \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( 'title', \IPS\Output::i()->output);
\IPS\Output::i()->sendOutput( $template );
