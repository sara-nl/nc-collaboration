<?php if (empty($_['inviteLink'])) {
    throw new Exception("No invite link available when generating email content");
} ?>
-- English version below --

<?php p($l->t('Hallo,')); ?>

U bent uitgenodigd om uw cloud IDs uit te wisselen met <?php echo $_['fromName']; ?>.
Als u deze uitnodiging accepteerd zult u onderling efficienter gefedereerd bestanden kunnen delen.
<?php if (!empty($_['message'])) : ?>
Het volgende persoonlijke bericht is meegestuurd met deze uitnodiging:

    <?php
    $lines = preg_split('/__LINE_BREAK__/', $_['message']);
    foreach ($lines as $line) { ?>
        <?php p("    $line\n"); ?>
    <?php } ?>
<?php endif; ?>

Via de onderstaande link kunt u de uitnodiging accepteren of afwijzen. Als u de uitnodiging accepteerd zullen de cloud IDs van u en <?php echo $_['fromName']; ?>, nodig voor het creëren van federated shares, onderling worden uitgewisseld.

<?php echo "    " . html_entity_decode($_['inviteLink']) . "\n"; ?>

* Wanneer u de afzender van deze email niet kent of u bent niet bekend met Research Drive verzoeken wij u deze email te verwijderen.

Op de wikipagina (https://wiki.surfnet.nl/display/RDRIVE) vindt u alle informatie over het gebruik van SURF Research Drive.
Voor vragen en opmerkingen kunt u contact opnemen met de Research Drive helpdesk via servicedesk@surf.nl

Met vriendelijke groet,
SURF Research Drive team

------------------------------------------------------------------------------------------------------------
<?php p($l->t('Hello,')); ?>

You have been invited to exchange cloud IDs with <?php echo $_['fromName']; ?>.
If you accept this invitation you will be able to create federated shares with each other more efficiently.
<?php if (!empty($_['message'])) : ?>
The following personal message was send with the invitation:

    <?php
    $lines = preg_split('/__LINE_BREAK__/', $_['message']);
    foreach ($lines as $line) { ?>
        <?php p("    $line\n"); ?>
    <?php } ?>
<?php endif; ?>

Please follow the link below to either accept or decline this invitation. If you accept this invitation, you and <?php echo $_['fromName']; ?> will exchange the federated cloud IDs which are needed to create federated shares.

<?php echo "    " . html_entity_decode($_['inviteLink']) . "\n"; ?>

* If you do not know the sender of this email or are not familiar with Research Drive, please delete this email.

On the wiki (https://wiki.surfnet.nl/display/RDRIVE) you will find more information about the usage of SURF Research Drive.
For questions or remarks, you can contact your Research Drive helpdesk via servicedesk@surf.nl

Kind regards,
SURF Research Drive team


<?php print_unescaped($this->inc('plain.mail.footer', ['app' => 'core'])); ?>