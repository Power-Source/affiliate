<?php
function show_affiliate_admin_metabox_reports_affiliate_link() {
	if(function_exists('is_multisite') && is_multisite() && function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('affiliate/affiliate.php')) {
		// switch to use new option
		$siteurl = get_blog_option(1,'home');
		$affiliatelinkurl = aff_get_option( 'affiliatelinkurl', $siteurl );
	} else {
		// switch to use new option
		$siteurl = aff_get_option('home');
		$affiliatelinkurl = aff_get_option( 'affiliatelinkurl', $siteurl );
	}

	?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Affiliate Link URL', 'affiliate') ?></span></h3>
		<div class="inside">
			<table class="form-table">
			<tr>
				<th valign="top" scope="row"><?php _e('Link URL','affiliate') ?></th>
				<td valign="top">
					<input name="affiliatelinkurl" type="text" id="affiliatelinkurl" style="width: 50%" value="<?php 
						echo htmlentities(stripslashes($affiliatelinkurl),ENT_QUOTES, 'UTF-8') ?>" />
				</td>
			</tr>
			</table>
		</div>
	</div>
	<?php
}

function show_affiliate_admin_metabox_reports_monetary_precision() {
	$affiliatemonetaryprecision = aff_get_option('affiliatemonetaryprecision');
	?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Währungspräzision', 'affiliate')  ?></span></h3>
		<div class="inside">
			<table class="form-table">
			<tr>
				<th valign="top" scope="row"><?php _e('Zahl oder Dezimalstellen für gespeicherte Berechnungen','affiliate') ?></th>
				<td valign="top">
					<select name='affiliatemonetaryprecision'>
						<option value="2" <?php if(aff_get_option('affiliatemonetaryprecision', '2') == '2') echo ' selected="selected" ' ?>><?php _e('2 places - 0.00', 'affiliate') ?></option>

						<option value="4" <?php if(aff_get_option('affiliatemonetaryprecision', '2') == '4') echo 'selected="selected" ' ?>><?php _e('4 places - 0.0000', 'affiliate') ?></option>
					</select>
				</td>
			</tr>
			</table>
			<p><?php _e('Warnung: Das Ändern der Genauigkeit wirkt sich auf vorhandene Daten und Berechnungen aus.', 'affiliate')?></p>
		</div>
	</div>
	<?php
}


function show_affiliate_admin_metabox_reports_column_settings() {
	$headings = aff_get_option( 'affiliateheadings', array( __('Einzigartige Klicks','affiliate'), __('Registrierungen','affiliate'), __('Bezahlte Mitglieder','affiliate')) );
	?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Spalteneinstellungen', 'affiliate') ?></span></h3>
		<div class="inside">
			<table class="form-table">
				<tr>
					<th valign="top" scope="row"><?php _e('Einzigartige Klicks','affiliate') ?></th>
					<td valign="top">
						<input name="uniqueclicks" type="text" id="uniqueclicks" style="width: 50%" value="<?php echo htmlentities(stripslashes($headings[0]),ENT_QUOTES, 'UTF-8') ?>" />
					</td>
				</tr>
				<tr>
					<th valign="top" scope="row"><?php _e('Registrierungen','affiliate') ?></th>
					<td valign="top">
						<input name="signups" type="text" id="signups" style="width: 50%" value="<?php echo htmlentities(stripslashes($headings[1]),ENT_QUOTES, 'UTF-8') ?>" />
					</td>
				</tr>
				<tr>
					<th valign="top" scope="row"><?php _e('Bezahlte Mitgliedschaften','affiliate') ?></th>
					<td valign="top">
						<input name="paidmembers" type="text" id="paidmembers" style="width: 50%" value="<?php echo htmlentities(stripslashes($headings[2]),ENT_QUOTES, 'UTF-8') ?>" />
					</td>
				</tr>
			</table>
		</div>
	</div>
	<?php
}

function show_affiliate_admin_metabox_profile_text() {

		$settingstextdefault = __("<p>Wir lieben es, wenn Leute über uns sprechen, und noch mehr, wenn sie uns ihren Freunden empfehlen.</p>
<p>Als Dankeschön möchten wir etwas zurückgeben, weshalb wir dieses Partnerprogramm eingerichtet haben.</p>
<p>Um zu beginnen, aktiviere einfach die Links für Dein Konto und gib unten Deine PayPal-E-Mail-Adresse ein. Weitere Informationen zu unserem Partnerprogramm findest Du auf unserer Hauptseite.</p>", 'affiliate');

		$advsettingstextdefault = __("<p>Es gibt Zeiten, in denen Du Deinen Affiliate-Link lieber ausblenden möchtest oder einfach nicht an die Partnerprogramm-Referenz erinnern musst, statt sie am Ende unserer URL anzubringen.</p>
<p>Wenn dies der Fall ist, kannst Du die Haupt-URL der Seite eingeben, an die Du Anfragen von unten sendest, und wir werden die kniffligen Teile für Dich erledigen um Deine Partnerprogramm dennoch auszuwerten.</p>", 'affiliate');
		
	?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Profilseitentext', 'affiliate')  ?></span></h3>
		<div class="inside">
			<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Profiltext für Partnereinstellungen', 'affiliate') ?></th>
				<td><?php
					$args = array("textarea_name" => "affiliatesettingstext");
					wp_editor( stripslashes( aff_get_option('affiliatesettingstext', $settingstextdefault) ), "affiliatesettingstext", $args );
				?></td>
			</tr>
			</table>
	
			<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Profiltext für Partner mit erweiterten Einstellungen', 'affiliate') ?></th>
				<td><?php
					$args = array("textarea_name" => "affiliateadvancedsettingstext");
					wp_editor( stripslashes( aff_get_option('affiliateadvancedsettingstext', $advsettingstextdefault) ), "affiliateadvancedsettingstext", $args );
					?></td>
				</tr>
			</table>
		</div>
	</div>
	<?php
}

function show_affiliate_admin_metabox_settings_banner() {
	$banners = aff_get_option('affiliatebannerlinks');
	//echo "banners<pre>"; print_r($banners); echo "</pre>";
	if(is_array($banners)) {
		$banners = implode("\n", $banners);
	}

	?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Bannereinstellungen', 'affiliate')  ?></span></h3>
		<div class="inside">
			<table class="form-table">
			<tr>
				<th valign="top" scope="row"><?php _e('Banner aktivieren','affiliate') ?></th>
				<td valign="top">
					<select name='affiliateenablebanners'>
						<option value="yes" <?php if(aff_get_option('affiliateenablebanners', 'no') == 'yes') echo ' selected="selected" ' ?>><?php _e('Ja Bitte', 'affiliate') ?></option>

						<option value="no" <?php if(aff_get_option('affiliateenablebanners', 'no') == 'no') echo 'selected="selected" ' ?>><?php _e('Nein Danke', 'affiliate') ?></option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Bannerbild-URLs (eine pro Zeile)', 'affiliate') ?></th>
				<td>
					<textarea name="affiliatebannerlinks" id="affiliatebannerlinks" cols="60" rows="10"><?php echo stripslashes( $banners ) ?></textarea>
				</td>
			</tr>
			</table>
			
		</div>
	</div>
	<?php
}

function show_affiliate_admin_metabox_settings_approval() {
	?>
	<div class="postbox">
		<h3 class="hndle" style="cursor:auto;"><span><?php _e('Genehmigungseinstellungen', 'affiliate') ?></span></h3>
		<div class="inside">
			<p class="description">
				<?php _e('Wenn Du Auszahlungen an Partner verzögern möchtest, bis diese manuell genehmigt wurden, setze diese Option unten. Partner können weiterhin Leads generieren, während sie auf die Genehmigung warten.','affiliate');?>
			</p>
			<table class="form-table">
			<tr>
				<th valign="top" scope="row"><?php _e('Zahle nur an zugelassene Partner','affiliate') ?></th>
				<td valign="top">
					<select name='affiliateenableapproval'>
						<option value="yes" <?php if (aff_get_option('affiliateenableapproval', 'no') == 'yes') echo ' selected="selected" '; ?>><?php _e('Ja Bitte', 'affiliate') ?></option>

						<option value="no" <?php if (aff_get_option('affiliateenableapproval', 'no') == 'no') echo ' selected="selected" ' ?>><?php _e('Nein Danke', 'affiliate') ?></option>

					</select>
				</td>
			</tr>
			</table>

		</div>
	</div>
	<?php
}


function show_affiliate_admin_metabox_settings_paypal_masspay_currency() {
	global $affiliate_currencies, $affiliate_currencies_paypal_masspay;

	sort($affiliate_currencies_paypal_masspay);

	?>
	<div class="postbox">
		<h3 class='hndle'><span><?php _e('Für PayPal Masspay verwendete Währung', 'affiliate') ?></span></h3>
		<div class="inside">
			<span class="description"><?php echo sprintf(__('Diese Einstellung definiert die 3-stellige Währung, die für %s verwendet wird.', 'affiliate'), '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_batch-payment-format-outside" target="_blank">'. __('PayPal Masspay-Datei', 'affiliate') .'</a>') ?></span>
			
			
			
			<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Währung', 'mp') ?></th>
				<td>
					<select id="affiliate-currency-paypal-masspay" name="affiliate-currency-paypal-masspay">
					<?php
						foreach ($affiliate_currencies_paypal_masspay as $key) {
							if (isset($affiliate_currencies[$key])) {
								?><option value="<?php echo $key; ?>"<?php selected(aff_get_option('affiliate-currency-paypal-masspay', 'EUR'), $key); ?>><?php echo esc_attr($key) .' - '. esc_attr($affiliate_currencies[$key][0]) ?></option><?php
							}
						}
					?>
					</select>
				</td>
			</tr>
			</table>
		</div>
	</div>
	<?php
}
