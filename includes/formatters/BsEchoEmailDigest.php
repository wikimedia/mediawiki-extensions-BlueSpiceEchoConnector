<?php

class BsEchoEmailDigest extends EchoEmailDigest {

    /**
     * Build the intro component
     * @return string
     */
    public function buildIntro () {
	$message = EchoEmailMode::message (
			'echo-email-batch-body-intro-' . $this->digestMode, $this->user
		)->params ( $this->user->getRealName () );

	return $this->decorator->decorateIntro ( $message );
    }

    /**
     * {@inheritDoc}
     */
    public function getTextTemplate () {
	return <<< EOF
%%intro%%

%%digestList%%

%%action%%

%%footer%%

EOF;
    }

    /**
     * {@inheritDoc}
     */
    public function getHTMLTemplate () {
	$alignStart = $this->lang->alignStart ();

	return <<< EOF
<html><head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<style>
		@media only screen and (max-width: 480px){
			table[id="email-container"]{max-width:600px !important; width:100% !important;}
		}
	</style>
</head><body>
<table cellspacing="0" cellpadding="0" border="0" width="100%" align="center" lang="{$this->lang->getCode ()}" dir="{$this->lang->getDir ()}">
<tr>
	<td bgcolor="#E6E7E8"><center>
		<br /><br />
		<table cellspacing="0" cellpadding="0" border="0" width="600" id="email-container">
			<tr>
				<td bgcolor="#FFFFFF" width="5%">&nbsp;</td>
				<td bgcolor="#FFFFFF" width="6%">&nbsp;</td>
				<td bgcolor="#FFFFFF" width="79%" style="line-height:40px;">&nbsp;</td>
				<td bgcolor="#FFFFFF" width="10%">&nbsp;</td>
			</tr>
			<tr>
				<td bgcolor="#FFFFFF" rowspan="2">&nbsp;</td>
				<td bgcolor="#FFFFFF" rowspan="2">&nbsp;</td>
				<td bgcolor="#FFFFFF" align="center" style="font-family: Arial, Helvetica, sans-serif; font-size:13px; line-height:20px; color:#6D6E70; text-align: center;">%%intro%%</td>
				<td bgcolor="#FFFFFF" rowspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td bgcolor="#FFFFFF" align="{$alignStart}" style="font-family: Arial, Helvetica, sans-serif; line-height: 20px; font-weight: 600;">
					<table cellspacing="0" cellpadding="0" border="0" width="100%">
						<tr>
							<td bgcolor="#FFFFFF" align="{$alignStart}" style="font-family: Arial, Helvetica, sans-serif; font-size:13px; color: #58585B; padding-top: 25px;">
								%%digestList%%
							</td>
						</tr>
					</table>
					<br /><br />
				</td>
			</tr>
			<tr>
				<td bgcolor="#FFFFFF">&nbsp;</td>
				<td bgcolor="#FFFFFF">&nbsp;</td>
				<td bgcolor="#FFFFFF" style="line-height:60px;" align="center">%%action%%</td>
				<td bgcolor="#FFFFFF">&nbsp;</td>
			</tr>
			<tr>
				<td bgcolor="#FFFFFF">&nbsp;</td>
				<td bgcolor="#FFFFFF">&nbsp;</td>
				<td bgcolor="#FFFFFF" style="line-height:40px;">&nbsp;</td>
				<td bgcolor="#FFFFFF">&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td align="{$alignStart}" style="font-family: Arial, Helvetica, sans-serif; font-size:10px; line-height:13px; color:#6D6E70; padding: 10px 20px;"><br />
					%%footer%%
					<br /><br />
				</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td colspan="4">&nbsp;</td>
			</tr>
		</table>
		<br><br></center>
	</td>
</tr>
</table>
</body></html>
EOF;
    }

}
