<table cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
        <td>
            <table cellspacing="0" cellpadding="0" border="0" width="600px">
                <tr>
                    <td width="20px">&nbsp;</td>
                    <td>
                        <img src="<?php //p(\OC::$server->getURLGenerator()->getAbsoluteURL(image_path('', 'logo-mail.gif'))); ?>" alt="<?php p($theme->getName()); ?>" />
                    </td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                </tr>
                <tr>
                    <td width="20px">&nbsp;</td>
                    <td style="font-weight:normal; font-size:0.8em; line-height:1.2em; font-family:verdana,'arial',sans;">
                        <?php if (empty($_['inviteLink'])) {
                            throw new Exception("No invite link available when generating email content");
                        } ?>
                        <p> -- English version below --</p>
                        <?php p($l->t('Hallo ' . $_['recipientName'] . ',')); ?>
                        <br>
                        U bent uitgenodigd om uw cloud IDs uit te wisselen met <?php echo $_['fromName']; ?>.
                        Als u deze uitnodiging accepteerd zult u onderling efficienter gefedereerd bestanden kunnen delen.<br>
                        <?php if (!empty($_['message'])) : ?>
                            <br>
                            Het volgende persoonlijke bericht is meegestuurd met deze uitnodiging:
                            <p style="margin-left: 2em;">
                                <?php
                                $lines = preg_split('/__LINE_BREAK__/', $_['message']);
                                foreach ($lines as $line) { ?>
                                    <?php p("$line"); ?><br>
                                <?php } ?>
                            </p>
                        <?php endif; ?>
                        <br>
                        Via de onderstaande link kunt u de uitnodiging accepteren of afwijzen.
                        Als u de uitnodiging accepteerd zullen de cloud IDs van u en <?php echo $_['fromName']; ?>, nodig voor het creëren van federated shares, onderling worden uitgewisseld.<br>
                        <br>
                        <a href="<?php p($_['inviteLink']); ?>">Klik om in te loggen in uw Research Drive instance.</a><br>
                        <br>
                        <strong><i>* Wanneer u de afzender van deze email niet kent of u bent niet bekend met Research Drive verzoeken wij u deze email te verwijderen.</i></strong><br>
                        <br>
                        Voor het verkrijgen van een Research Drive account neemt u contact op met de IT service helpdesk van uw organisatie.<br>
                        <br>
                        Op de <a href="https://wiki.surfnet.nl/display/RDRIVE">wikipagina</a> vindt u alle informatie over het gebruik van SURF Research Drive.<br>
                        Voor vragen en opmerkingen kunt u contact opnemen met de Research Drive helpdesk via <a href="mailto:servicedesk@surf.nl">servicedesk@surf.nl</a>.<br>
                        <br>
                        Met vriendelijke groet,<br>
                        SURF Research Drive team<br>
                        <br>
                        <hr>
                        <?php p($l->t('Hello ' . $_['recipientName'] . ',')); ?>
                        <br>
                        You have been invited to exchange cloud IDs with <?php echo $_['fromName']; ?>.
                        If you accept this invitation you will be able to create federated shares with each other more efficiently.<br>
                        <?php if (!empty($_['message'])) : ?>
                            <br>
                            The following personal message was send with the invitation:
                            <p style="margin-left: 2em;">
                                <?php
                                $lines = preg_split('/__LINE_BREAK__/', $_['message']);
                                foreach ($lines as $line) { ?>
                                    <?php p("$line"); ?><br>
                                <?php } ?>
                            </p>
                        <?php endif; ?>
                        <br>
                        Please follow the link below to either accept or decline this invitation.
                        If you accept this invitation, you and <?php echo $_['fromName']; ?> will exchange the federated cloud IDs which are needed to create federated shares.<br>
                        <br>
                        <a href="<?php p($_['inviteLink']); ?>">Click to log into your Research Drive instance.</a><br>
                        <br>
                        <strong><i>* If you do not know the sender of this email or are not familiar with Research Drive, please delete this email.</i></strong><br>
                        <br>
                        To obtain an account on Research Drive you should contact the IT service helpdesk within your organization.<br>
                        <br>
                        On the <a href="https://wiki.surfnet.nl/display/RDRIVE">wiki</a> you will find more information about the usage of SURF Research Drive.<br>
                        For questions or remarks, you can contact your Research Drive helpdesk via <a href="mailto:servicedesk@surf.nl">servicedesk@surf.nl</a>.<br>
                        <br>
                        Kind regards,<br>
                        SURF Research Drive team
                    </td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                </tr>
                <tr>
                    <td width="20px">&nbsp;</td>
                    <td style="font-weight:normal; font-size:0.8em; line-height:1.2em; font-family:verdana,'arial',sans;">
                        <?php print_unescaped($this->inc('html.mail.footer', ['app' => 'core'])); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>
</table>