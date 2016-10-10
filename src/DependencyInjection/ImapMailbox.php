<?php

namespace Fi\ImapBundle\DependencyInjection;

/**
 * @see https://github.com/barbushin/php-imap
 *
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
/*
 * Sintassi per parametri di office 365 e mailbox del comune
 * parameters:
  #Tipo casella mailbox:
  mailbox: '{imap.comune.intranet:143/novalidate-cert}INBOX'
  mailuser: 'username'
  mailpassword: 'password'

  #Tipo casella office365:
  # Personale
  mailbox: '{outlook.office365.com:993/imap/ssl/authuser=d99999@comune.fi.it}'
  mailuser: 'd99999@comune.fi.it'
  mailpassword: 'xxxxxxxx'

  #   Condivisa, dove l'account condiviso è imaptestoffice365
  #   mailbox: '{outlook.office365.com:993/imap/ssl/authuser=d99999@comune.fi.it/user=imaptestoffice365}'
  mailuser: 'd99999@comune.fi.it'
  mailpassword: 'xxxxxxxx'

 *
 * Esempio:
  // IMAP must be enabled in Google Mail Settings
  define('GMAIL_EMAIL', 'some@gmail.com');
  define('GMAIL_PASSWORD', '*********');
  define('ATTACHMENTS_DIR', dirname(__FILE__) . '/attachments');
  $mailbox = new ImapMailbox('{imap.gmail.com:993/imap/ssl}INBOX', GMAIL_EMAIL, GMAIL_PASSWORD, ATTACHMENTS_DIR, 'utf-8');
 */
class ImapMailbox
{
    protected $imapPath;
    protected $login;
    protected $password;
    protected $serverEncoding;
    protected $imapopenoptions;

    /**
     * IMAP MailBox.
     *
     * @param string $imapPath        imap mailbox <br/>ex.
     *                                <br/>Casella personale: {outlook.office365.com:993/imap/ssl/authuser=d99999@comune.fi.it}INBOX
     *                                <br/>Casella condivisa: {outlook.office365.com:993/imap/ssl/authuser=d99999@comune.fi.it/user=nomecasella}INBOX
     *                                <br/>Casella mailbox: {imap.comune.intranet:143/novalidate-cert}INBOX
     * @param string $login           imap username
     * @param string $password        imap password
     * @param string $serverEncoding  imap server encoding (default UTF-8)
     * @param int    $imapopenoptions imap open options (default OP_READONLY)
     */
    public function __construct($imapPath, $login, $password, $serverEncoding = 'UTF-8', $imapopenoptions = 'OP_READONLY' /* OP_READONLY */)
    {
        $this->imapPath = $imapPath;
        $this->login = $login;
        $this->password = $password;
        $this->imapopenoptions = $imapopenoptions;
        $this->serverEncoding = $serverEncoding;
    }

    /**
     * Get IMAP mailbox connection stream.
     *
     * @param bool $forceConnection Initialize connection if it's not initialized
     *
     * @return null|resource
     */
    protected function disconnect()
    {
        $imapStream = ImapStreamUtils::getImapStream($this->imapPath, $this->login, $this->password, constant($this->imapopenoptions), false);
        if ($imapStream && is_resource($imapStream)) {
            imap_close($imapStream, CL_EXPUNGE);
        }
    }

    /**
     * Get information about the current mailbox.
     *
     * Returns the information in an object with following properties:
     * Date - current system time formatted according to RFC2822
     * Driver - protocol used to access this mailbox: POP3, IMAP, NNTP
     * Mailbox - the mailbox name
     * Nmsgs - number of mails in the mailbox
     * Recent - number of recent mails in the mailbox
     *
     * @return stdClass
     */
    public function checkMailbox()
    {
        return imap_check(ImapStreamUtils::getImapStream($this->imapPath, $this->login, $this->password, constant($this->imapopenoptions)));
    }

    /**
     * Creates a new mailbox specified by mailbox.
     *
     * @return bool
     */
    public function createMailbox()
    {
        return imap_createmailbox(ImapStreamUtils::getImapStream($this->imapPath, $this->login, $this->password, constant($this->imapopenoptions)), imap_utf7_encode($this->imapPath));
    }

    /**
     * Gets status information about the given mailbox.
     *
     * This function returns an object containing status information.
     * The object has the following properties: messages, recent, unseen, uidnext, and uidvalidity.
     *
     * @return stdClass | FALSE if the box doesn't exist
     */
    public function statusMailbox()
    {
        return imap_status(ImapStreamUtils::getImapStream($this->imapPath, $this->login, $this->password, constant($this->imapopenoptions)), $this->imapPath, SA_ALL);
    }

    /**
     * Gets listing the folders.
     *
     * This function returns an object containing listing the folders.
     * The object has the following properties: messages, recent, unseen, uidnext, and uidvalidity.
     *
     * @return array listing the folders
     */
    public function getListingFolders()
    {
        $folders = imap_list(ImapStreamUtils::getImapStream($this->imapPath, $this->login, $this->password, constant($this->imapopenoptions)), $this->imapPath, '*');
        foreach ($folders as $key => $folder) {
            $folder = str_replace($this->imapPath, '', imap_utf7_decode($folder));
            $folders[$key] = $folder;
        }

        return $folders;
    }

    /**
     * This function performs a search on the mailbox currently opened in the given IMAP stream.
     * For example, to match all unanswered mails sent by Mom, you'd use: "UNANSWERED FROM mom".
     * Searches appear to be case insensitive. This list of criteria is from a reading of the UW
     * c-client source code and may be incomplete or inaccurate (see also RFC2060, section 6.4.4).
     *
     * @param string $criteria String, delimited by spaces, in which the following keywords are allowed. Any multi-word arguments (e.g. FROM "joey smith") must be quoted. Results will match all criteria entries.
     *                         ALL - return all mails matching the rest of the criteria
     *                         ANSWERED - match mails with the \\ANSWERED flag set
     *                         BCC "string" - match mails with "string" in the Bcc: field
     *                         BEFORE "date" - match mails with Date: before "date"
     *                         BODY "string" - match mails with "string" in the body of the mail
     *                         CC "string" - match mails with "string" in the Cc: field
     *                         DELETED - match deleted mails
     *                         FLAGGED - match mails with the \\FLAGGED (sometimes referred to as Important or Urgent) flag set
     *                         FROM "string" - match mails with "string" in the From: field
     *                         KEYWORD "string" - match mails with "string" as a keyword
     *                         NEW - match new mails
     *                         OLD - match old mails
     *                         ON "date" - match mails with Date: matching "date"
     *                         RECENT - match mails with the \\RECENT flag set
     *                         SEEN - match mails that have been read (the \\SEEN flag is set)
     *                         SINCE "date" - match mails with Date: after "date"
     *                         SUBJECT "string" - match mails with "string" in the Subject:
     *                         TEXT "string" - match mails with text "string"
     *                         TO "string" - match mails with "string" in the To:
     *                         UNANSWERED - match mails that have not been answered
     *                         UNDELETED - match mails that are not deleted
     *                         UNFLAGGED - match mails that are not flagged
     *                         UNKEYWORD "string" - match mails that do not have the keyword "string"
     *                         UNSEEN - match mails which have not been read yet
     *
     * @return array Mails ids
     */
    public function searchMailbox($criteria = 'ALL')
    {
        $mailsIds = imap_search(ImapStreamUtils::getImapStream($this->imapPath, $this->login, $this->password, constant($this->imapopenoptions)), $criteria, SE_UID, $this->serverEncoding);

        return $mailsIds ? $mailsIds : array();
    }

    /**
     * Save mail body.
     *
     * @return bool
     */
    public function saveMail($mailId, $filename = 'email.eml')
    {
        return imap_savebody(ImapStreamUtils::getImapStream($this->imapPath, $this->login, $this->password, constant($this->imapopenoptions)), $filename, $mailId, '', FT_UID);
    }

    /**
     * Marks mails listed in mailId for deletion.
     *
     * @return bool
     */
    public function deleteMail($mailId)
    {
        return imap_delete(ImapStreamUtils::getImapStream($this->imapPath, $this->login, $this->password, constant($this->imapopenoptions)), $mailId, FT_UID);
    }

    public function moveMail($mailId, $mailBox)
    {
        return imap_mail_move(ImapStreamUtils::getImapStream($this->imapPath, $this->login, $this->password, constant($this->imapopenoptions)), $mailId, $mailBox, CP_UID) && $this->expungeDeletedMails();
    }

    /**
     * Deletes all the mails marked for deletion by imap_delete(), imap_mail_move(), or imap_setflag_full().
     *
     * @return bool
     */
    public function expungeDeletedMails()
    {
        return imap_expunge(ImapStreamUtils::getImapStream($this->imapPath, $this->login, $this->password, constant($this->imapopenoptions)));
    }

    /**
     * Add the flag \Seen to a mail.
     *
     * @return bool
     */
    public function markMailAsRead($mailId)
    {
        return $this->setFlag(array($mailId), '\\Seen');
    }

    /**
     * Remove the flag \Seen from a mail.
     *
     * @return bool
     */
    public function markMailAsUnread($mailId)
    {
        return $this->clearFlag(array($mailId), '\\Seen');
    }

    /**
     * Add the flag \Flagged to a mail.
     *
     * @return bool
     */
    public function markMailAsImportant($mailId)
    {
        return $this->setFlag(array($mailId), '\\Flagged');
    }

    /**
     * Add the flag \Seen to a mails.
     *
     * @return bool
     */
    public function markMailsAsRead(array $mailId)
    {
        return $this->setFlag($mailId, '\\Seen');
    }

    /**
     * Remove the flag \Seen from some mails.
     *
     * @return bool
     */
    public function markMailsAsUnread(array $mailId)
    {
        return $this->clearFlag($mailId, '\\Seen');
    }

    /**
     * Add the flag \Flagged to some mails.
     *
     * @return bool
     */
    public function markMailsAsImportant(array $mailId)
    {
        return $this->setFlag($mailId, '\\Flagged');
    }

    /**
     * Causes a store to add the specified flag to the flags set for the mails in the specified sequence.
     *
     * @param array $mailsIds
     * @param $flag Flags which you can set are \Seen, \Answered, \Flagged, \Deleted, and \Draft as defined by RFC2060
     *
     * @return bool
     */
    public function setFlag(array $mailsIds, $flag)
    {
        return imap_setflag_full(ImapStreamUtils::getImapStream($this->imapPath, $this->login, $this->password, constant($this->imapopenoptions)), implode(',', $mailsIds), $flag, ST_UID);
    }

    /**
     * Cause a store to delete the specified flag to the flags set for the mails in the specified sequence.
     *
     * @param array $mailsIds
     * @param $flag Flags which you can set are \Seen, \Answered, \Flagged, \Deleted, and \Draft as defined by RFC2060
     *
     * @return bool
     */
    public function clearFlag(array $mailsIds, $flag)
    {
        return imap_clearflag_full(ImapStreamUtils::getImapStream($this->imapPath, $this->login, $this->password, constant($this->imapopenoptions)), implode(',', $mailsIds), $flag, ST_UID);
    }

    /**
     * Gets mails ids sorted by some criteria.
     *
     * Criteria can be one (and only one) of the following constants:
     * SORTDATE - mail Date
     * SORTARRIVAL - arrival date (default)
     * SORTFROM - mailbox in first From address
     * SORTSUBJECT - mail subject
     * SORTTO - mailbox in first To address
     * SORTCC - mailbox in first cc address
     * SORTSIZE - size of mail in octets
     *
     * @param int  $criteria
     * @param bool $reverse
     *
     * @return array Mails ids
     */
    public function sortMails($criteria = SORTARRIVAL, $reverse = true)
    {
        return imap_sort(ImapStreamUtils::getImapStream($this->imapPath, $this->login, $this->password, constant($this->imapopenoptions)), $criteria, $reverse, SE_UID);
    }

    /**
     * Get mail data.
     *
     * @param $mailId
     *
     * @return IncomingMail
     */
    public function getMail($mailId)
    {
        $head = imap_rfc822_parse_headers(imap_fetchheader(ImapStreamUtils::getImapStream($this->imapPath, $this->login, $this->password, constant($this->imapopenoptions)), $mailId, FT_UID));
        $errs = imap_errors();
        $mail = new IncomingMail();
        if ($errs) {
            $mail->id = -1;
            $mail->textPlain = $errs;
        } else {
            $mail->id = $mailId;

            $mail->date = ImapMailboxDetails::getMessageDate($head);
            $mail->subject = ImapMailboxDetails::getMessageSubject($head, $this->serverEncoding);
            $mail->fromName = ImapMailboxDetails::getMessageFromName($head, $this->serverEncoding);
            $mail->fromAddress = ImapMailboxDetails::getMessageFromAddress($head);
            ImapMailboxDetails::getMessageTo($head, $mail, $this->serverEncoding);
            ImapMailboxDetails::getMessageCc($head, $mail, $this->serverEncoding);
            ImapMailboxDetails::getMessageReplayTo($head, $mail, $this->serverEncoding);

            $this->getMessageContent($mailId, $head, $mail);
        }

        return $mail;
    }

    private function getMessageContent($mailId, $head, &$mail)
    {
        $mailStructure = imap_fetchstructure(ImapStreamUtils::getImapStream($this->imapPath, $this->login, $this->password, constant($this->imapopenoptions)), $mailId, FT_UID);
        $this->getMessageBodyContent($mailStructure, $head, $mail);
    }

    private function getMessageBodyContent($mailStructure, $head, &$mail)
    {
        $errs = imap_errors();
        if ($errs === false) {
            if (empty($mailStructure->parts)) {
                $this->initMailPart($mail, $mailStructure, 0);
            } else {
                foreach ($mailStructure->parts as $partNum => $partStructure) {
                    $this->initMailPart($mail, $partStructure, $partNum + 1);
                }
            }
        }
    }

    private function getMailPart($partStructure, &$mail, &$partNum)
    {
        if (empty($partStructure->parts)) {
            return;
        }
        foreach ($partStructure->parts as $subPartNum => $subPartStructure) {
            if ($partStructure->type == 2 && $partStructure->subtype == 'RFC822') {
                $this->initMailPart($mail, $subPartStructure, $partNum);
            } else {
                $this->initMailPart($mail, $subPartStructure, $partNum.'.'.($subPartNum + 1));
            }
        }
    }

    protected function initMailPart(IncomingMail $mail, $partStructure, $partNum)
    {
        $data = $partNum ? imap_fetchbody(ImapStreamUtils::getImapStream($this->imapPath, $this->login, $this->password, constant($this->imapopenoptions)), $mail->id, $partNum, FT_UID) : imap_body(ImapStreamUtils::getImapStream($this->imapPath, $this->login, $this->password, constant($this->imapopenoptions)), $mail->id, FT_UID);

        switch ($partStructure->encoding) {
            case 1:
                $data = imap_utf8($data);
                break;
            case 2:
                $data = imap_binary($data);
                break;
            case 3:
                $data = imap_base64($data);
                break;
            case 4:
                $data = imap_qprint($data);
                break;
        }

        $params = array();
        ImapMailboxUtils::setMessageParameters($params, $partStructure);
        $attachmentdata = $data;

        ImapMailboxUtils::setMessageEncoding($data, $this->serverEncoding);

        $this->setMessageAttachmensts($partStructure, $params, $data, $mail, $attachmentdata, $partNum);
    }

    protected function setMessageAttachmensts($partStructure, $params, $data, $mail, $attachmentdata, $partNum)
    {
        // attachments
        $attachmentId = ImapMailboxDetails::getAttachmentId($params, $partStructure);
        if ($attachmentId) {
            ImapMailboxDetails::buildMessageAttachment($attachmentId, $attachmentdata, $params, $partStructure, $mail, $this->serverEncoding);
        } elseif ($partStructure->type == 0 && $data) {
            ImapMailboxDetails::getMailBody($partStructure, $mail, $data);
        } elseif ($partStructure->type == 2 && $data) {
            $mail->textPlain .= trim($data);
        }

        $this->getMailPart($partStructure, $mail, $partNum);
    }

    /**
     * filter valid utf-8 byte sequences.
     *
     * take over all valid bytes, drop an invalid sequence until first
     * non-matching byte, start over at that byte.
     *
     * @param string $str
     *
     * @return string
     */
//function valid_utf8_bytes($str) {
//    $return = '';
//    $length = strlen($str);
//    $invalid = array_flip(array("\xEF\xBF\xBF" /* U-FFFF */, "\xEF\xBF\xBE" /* U-FFFE */));
//    for ($i = 0; $i < $length; $i++) {
//        $c = ord($str[$o = $i]);
//        if ($c < 0x80)
//            $n = 0;# 0bbbbbbb
//        elseif (($c & 0xE0) === 0xC0)
//            $n = 1;# 110bbbbb
//        elseif (($c & 0xF0) === 0xE0)
//            $n = 2;# 1110bbbb
//        elseif (($c & 0xF8) === 0xF0)
//            $n = 3;# 11110bbb
//        elseif (($c & 0xFC) === 0xF8)
//            $n = 4;# 111110bb
//        else
//            continue;# Does not match
//        for ($j = ++$n; --$j;) # n bytes matching 10bbbbbb follow ?
//            if (( ++$i === $length) || ((ord($str[$i]) & 0xC0) != 0x80))
//                continue 2
//                ;
//        $match = substr($str, $o, $n);
//        if ($n === 3 && isset($invalid[$match])) # test invalid sequences
//            continue;
//        $return .= $match;
//    }
//    return $return;
//}

    public function __destruct()
    {
        $this->disconnect();
    }
}
