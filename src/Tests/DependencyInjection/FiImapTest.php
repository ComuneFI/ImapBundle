<?php

namespace Fi\ImapBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Fi\ImapBundle\DependencyInjection\ImapMailbox;

class FiImapTest extends KernelTestCase
{
    private $container;

    public function setUp()
    {
        self::bootKernel();

        $this->container = self::$kernel->getContainer();
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function testImap()
    {
        /* $indirizzomail = $this->getContainer()->getParameter('imapconnectionstring');
          $utentemail = $this->getContainer()->getParameter('imapusername');
          $passwordmail = $this->getContainer()->getParameter('imappassword');

          $mailbox = new ImapMailbox($indirizzomail, $utentemail, $passwordmail, 'UTF-8');

          $arraymessaggi = array();
          $mailsIds = $mailbox->searchMailBox('ALL');
          if (!$mailsIds) {
          //Gestire come si vuole il fatto che non ci sono messaggi nella casella di posta
          throw new ImapMailboxException('Nessun messaggio trovato nella casella');
          } else {
          foreach ($mailsIds as $mailId) {
          $ok = true;
          try {
          //@var $mail \Fi\ImapBundle\DependencyInjection\IncomingMail
          $mail = $mailbox->getMail($mailId);

          if (!$mail) {
          $arraymessaggi[$mailId] = "** Errore parse headers del messaggio con ID $mailId";
          $ok = false;
          }
          } catch (Exception $ex) {
          $arraymessaggi[$mailId] = "** Messaggio con caratteri errati - MailId $mailId ** Eccezione ".$ex->getTraceAsString();
          $ok = false;
          }
          if ($ok === true) {
          $arraymessaggi[$mailId]['id'] = $mail->id;
          $arraymessaggi[$mailId]['subject'] = $mail->subject;
          $arraymessaggi[$mailId]['bodytext'] = trim($mail->textPlain);
          $arraymessaggi[$mailId]['bodyhtml'] = trim($mail->textHtml);
          $arraymessaggi[$mailId]['fromname'] = $mail->fromName;
          $arraymessaggi[$mailId]['fromaddress'] = $mail->fromAddress;
          $arraymessaggi[$mailId]['date'] = \DateTime::createFromFormat('Y-m-d H:i:s', $mail->date);
          $arraymessaggi[$mailId]['replyto'] = $mail->replyTo;
          $arraymessaggi[$mailId]['cc'] = $mail->cc;
          $arraymessaggi[$mailId]['to'] = $mail->to;
          $arraymessaggi[$mailId]['attachments'] = $mail->getAttachments();
          }
          }
          // assert that your calculator added the numbers correctly!
          //var_dump($arraymessaggi[66]); exit;
          $this->assertGreaterThanOrEqual(0, count($arraymessaggi));
          } */
    }
}
