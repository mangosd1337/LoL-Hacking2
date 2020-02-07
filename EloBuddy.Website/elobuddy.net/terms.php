<?php

require_once './init.php';

\IPS\Dispatcher\Front::i();


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
  #ipsLayout_contentArea{
    padding: 0px;
  }
</style>
<div class='ipsBox ipsPad' style="margin: -10px -11px 0px -10px;">
  <h2 class="ipsType_sectionTitle ipsType_reset ipsType_medium" style="margin-left: -15px;margin-right: -15px;margin-top: -15px;">
    Terms & Conditions
  </h2>
	<p class='ipsType_normal'>
    This Privacy Policy governs the manner in which WE DOMINATE LEAGUE SpA collects, uses, maintains and discloses information collected from users (each, a "User") of the https://elobuddy.net/ website ("Site"). This privacy policy applies to the Site and all products and services offered by WE DOMINATE LEAGUE SpA.
      <br>

      <h2>Personal identification information</h2>
      We may collect personal identification information from Users in a variety of ways, including, but not limited to, when Users visit our site, register on the siteplace an order and in connection with other activities, services, features or resources we make available on our Site. Users may be asked for, as appropriate, name, email address, mailing address,
      <br><br>
      Users may, however, visit our Site anonymously.
      <br><br>
      We will collect personal identification information from Users only if they voluntarily submit such information to us. Users can always refuse to supply personally identification information, except that it may prevent them from engaging in certain Site related activities.

      <h2>Non-personal identification information</h2>
      We may collect non-personal identification information about Users whenever they interact with our Site. Non-personal identification information may include the browser name, the type of computer and technical information about Users means of connection to our Site, such as the operating system and the Internet service providers utilized and other similar information.

      <h2>Web browser cookies</h2>
      Our Site may use "cookies" to enhance User experience. User's web browser places cookies on their hard drive for record-keeping purposes and sometimes to track information about them. User may choose to set their web browser to refuse cookies, or to alert you when cookies are being sent. If they do so, note that some parts of the Site may not function properly.

      <h2>How we use collected information</h2>
      WE DOMINATE LEAGUE SpA collects and uses Users personal information for the following purposes:
      <br>
      <strong>To improve customer service</strong>
      <br>
      Your information helps us to more effectively respond to your customer service requests and support needs.
      <br>
      <strong>To personalize user experience</strong>
      <br>
      We may use information in the aggregate to understand how our Users as a group use the services and resources provided on our Site.
      <br>
      <strong>To improve our Site</strong>
      <br>
      We continually strive to improve our website offerings based on the information and feedback we receive from you.
      <br>
      <strong>To process transactions</strong>
      <br>
      We may use the information Users provide about themselves when placing an order only to provide service to that order. We do not share this information with outside parties except to the extent necessary to provide the service.
      <br>
      <strong>To administer a content, promotion, survey or other Site feature</strong>
      <br>
      To send Users information they agreed to receive about topics we think will be of interest to them.
      <br>
      <strong>To send periodic emails</strong>
      <br>
      The email address Users provide for order processing, will only be used to send them information and updates pertaining to their order. It may also be used to respond to their inquiries, and/or other requests or questions. If User decides to opt-in to our mailing list, they will receive emails that may include company news, updates, related product or service information, etc. If at any time the User would like to unsubscribe from receiving future emails, we include detailed unsubscribe instructions at the bottom of each email or User may contact us via our Site.

      <h2>How we protect your information</h2>
      We adopt appropriate data collection, storage and processing practices and security measures to protect against unauthorized access, alteration, disclosure or destruction of your personal information, username, password, transaction information and data stored on our Site.
      <br>
      Sensitive and private data exchange between the Site and its Users happens over a SSL secured communication channel and is encrypted and protected with digital signatures. 

      <h2>Links to and from other websites</h2>
      Throughout this Website you may find links to third party websites. The provision of a link to such a website does not mean that we endorse that website. If you visit any website via a link on this Website you do so at your own risk.<br /> Any party wishing to link to this website is entitled to do so provided that the conditions below are observed:<br /> &nbsp;&nbsp;&nbsp;(a) &nbsp;you do not seek to imply that we are endorsing the services or products of another party unless this has been agreed with us in writing;<br /> &nbsp;&nbsp;&nbsp;(b) &nbsp;you do not misrepresent your relationship with this website; and<br/> &nbsp;&nbsp;&nbsp;(c) &nbsp;the website from which you link to this Website does not contain offensive or otherwise  controversial content or, content that infringes any intellectual property rights or other rights of a third party.<br /> By linking to this Website in breach of our terms, you shall indemnify us for any loss or damage suffered to this Website as a result of such linking.
      
      
      <h2>Termination</h2>
      We can stop our service without a warning.<br>
      We can terminate or suspend your account, for any reason whatsover.<br>

      <h2>Refund and Cancellation Policy</h2>
      No refunds can be made.
      
      <h2>Changes to this privacy policy</h2>
      WE DOMINATE LEAGUE SpA has the discretion to update this privacy policy at any time. When we do, revise the updated date at the bottom of this page,. We encourage Users to frequently check this page for any changes to stay informed about how we are helping to protect the personal information we collect. You acknowledge and agree that it is your responsibility to review this privacy policy periodically and become aware of modifications.

      <h2>Your acceptance of these terms</h2>
      By using this Site, you signify your acceptance of this policy and terms of service. If you do not agree to this policy, please do not use our Site. Your continued use of the Site following the posting of changes to this policy will be deemed your acceptance of those changes.
      
      <h2>Waiver</h2>
      If you breach these Conditions of Use and we take no action, we will still be entitled to use our rights and remedies in any other situation where you breach these Conditions of Use.
      
      <h2>Governing law</h2>
      These Terms and Conditions shall be governed by and construed in accordance with the law of  and you hereby submit to the exclusive jurisdiction of the  courts.

      
      <h2>Contacting us</h2>
      If you have any questions about this Privacy Policy, the practices of this site, or your dealings with this site, please contact us at:
      <br>
      <a href="https://elobuddy.net">elobuddy.net</a>
      <br>
      support@elobuddy.net

      

    
	</p>

</div>
EOF;

$template = \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( 'Terms and Conditions', \IPS\Output::i()->output);
\IPS\Output::i()->sendOutput( $template );
