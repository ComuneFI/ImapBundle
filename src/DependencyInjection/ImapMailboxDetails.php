<?php

namespace Fi\ImapBundle\DependencyInjection;

class ImapMailboxDetails
{
    public static function getMessageDate($head)
    {
        return date('Y-m-d H:i:s', isset($head->date) ? strtotime($head->date) : time());
    }

    public static function getMessageFromAddress($head)
    {
        return strtolower($head->from[0]->mailbox.'@'.$head->from[0]->host);
    }

    public static function getMessageFromName($head, $serverEncoding)
    {
        return isset($head->from[0]->personal) ? ImapMailboxUtils::decodeMimeStr($head->from[0]->personal, $serverEncoding) : null;
    }

    public static function getMessageSubject($head, $serverEncoding)
    {
        return isset($head->subject) ? ImapMailboxUtils::decodeMimeStr($head->subject, $serverEncoding) : null;
    }

    public static function getMessageCc($head, &$mail, $serverEncoding)
    {
        if (isset($head->cc)) {
            foreach ($head->cc as $cc) {
                $ccdetail = isset($cc->personal) ? ImapMailboxUtils::decodeMimeStr($cc->personal, $serverEncoding) : null;
                $mail->cc[strtolower($cc->mailbox.'@'.$cc->host)] = $ccdetail;
            }
        }
    }

    public static function getMessageTo($head, &$mail, $serverEncoding)
    {
        if (isset($head->to)) {
            $toStrings = array();
            foreach ($head->to as $to) {
                if (!empty($to->mailbox) && !empty($to->host)) {
                    $toEmail = strtolower($to->mailbox.'@'.$to->host);
                    $toName = isset($to->personal) ? ImapMailboxUtils::decodeMimeStr($to->personal, $serverEncoding) : null;
                    $toStrings[] = $toName ? "$toName <$toEmail>" : $toEmail;
                    $mail->to[$toEmail] = $toName;
                }
            }
            $mail->toString = implode(', ', $toStrings);
        }
    }

    public static function getMessageReplayTo($head, &$mail, $serverEncoding)
    {
        if (isset($head->reply_to)) {
            foreach ($head->reply_to as $replyTo) {
                $replaytodetail = isset($replyTo->personal) ? ImapMailboxUtils::decodeMimeStr($replyTo->personal, $serverEncoding) : null;
                $mail->replyTo[strtolower($replyTo->mailbox.'@'.$replyTo->host)] = $replaytodetail;
            }
        }
    }

    public static function getMailBody($partStructure, &$mail, $data)
    {
        if (strtolower($partStructure->subtype) == 'plain') {
            $mail->textPlain .= $data;
        } else {
            $mail->textHtml .= $data;
        }
    }

    public static function setMaildetail(&$mail, $serverEncoding)
    {
        if (isset($mail->subject)) {
            $mail->subject = ImapMailboxUtils::decodeMimeStr($mail->subject, $serverEncoding);
        }
        if (isset($mail->from)) {
            $mail->from = ImapMailboxUtils::decodeMimeStr($mail->from, $serverEncoding);
        }
        if (isset($mail->to)) {
            $mail->to = ImapMailboxUtils::decodeMimeStr($mail->to, $serverEncoding);
        }
    }

    public static function getAttachmentId($params, $partStructure)
    {
        $detauil = (isset($params['filename']) || isset($params['name']) ? mt_rand().mt_rand() : null);

        return $partStructure->ifid ? trim($partStructure->id, ' <>') : $detauil;
    }

    public static function buildMessageAttachment($attachmentId, $attachmentdata, $params, $partStructure, &$mail, $serverEncoding)
    {
        if (empty($params['filename']) && empty($params['name'])) {
            $fileName = $attachmentId.'.'.strtolower($partStructure->subtype);
        } else {
            $fileName = !empty($params['filename']) ? $params['filename'] : $params['name'];
            $fileName = ImapMailboxUtils::decodeMimeStr($fileName, $serverEncoding);
            $fileName = ImapMailboxUtils::decodeRFC2231($fileName, $serverEncoding);
        }
        $attachment = new IncomingMailAttachment();
        $attachment->id = $attachmentId;
        $attachment->name = $fileName;
        $attachment->contents = $attachmentdata;
        $mail->addAttachment($attachment);
    }

    /**
     * Fetch mail headers for listed mails ids.
     *
     * Returns an array of objects describing one mail header each. The object will only define a property if it exists. The possible properties are:
     * subject - the mails subject
     * from - who sent it
     * to - recipient
     * date - when was it sent
     * message_id - Mail-ID
     * references - is a reference to this mail id
     * in_reply_to - is a reply to this mail id
     * size - size in bytes
     * uid - UID the mail has in the mailbox
     * msgno - mail sequence number in the mailbox
     * recent - this mail is flagged as recent
     * flagged - this mail is flagged
     * answered - this mail is flagged as answered
     * deleted - this mail is flagged for deletion
     * seen - this mail is flagged as already read
     * draft - this mail is flagged as being a draft
     *
     * @param array $mailsIds
     *
     * @return array
     */
    public function getMailsInfo($stram, array $mailsIds)
    {
        $mails = imap_fetch_overview($stram, implode(',', $mailsIds), FT_UID);
        if (is_array($mails) && count($mails)) {
            foreach ($mails as &$mail) {
                self::setMaildetail($mail, $this->serverEncoding);
            }
        }

        return $mails;
    }

    /**
     * Get information about the current mailbox.
     *
     * Returns an object with following properties:
     * Date - last change (current datetime)
     * Driver - driver
     * Mailbox - name of the mailbox
     * Nmsgs - number of messages
     * Recent - number of recent messages
     * Unread - number of unread messages
     * Deleted - number of deleted messages
     * Size - mailbox size
     *
     * @return object Object with info | FALSE on failure
     */
    public function getMailboxInfo($stream)
    {
        return imap_mailboxmsginfo($stream);
    }

    /**
     * Get mails count in mail box.
     *
     * @return int
     */
    public function countMails($stream)
    {
        return imap_num_msg($stream);
    }

    /**
     * Retrieve the quota settings per user.
     *
     * @return array - FALSE in the case of call failure
     */
    public static function getQuota($stream)
    {
        return imap_get_quotaroot($stream, 'INBOX');
    }

    /**
     * Return quota limit in KB.
     *
     * @return int - FALSE in the case of call failure
     */
    public static function getQuotaLimit()
    {
        $quota = $this->getQuota();
        if (is_array($quota)) {
            $quota = $quota['STORAGE']['limit'];
        }

        return $quota;
    }

    /**
     * Return quota usage in KB.
     *
     * @return int - FALSE in the case of call failure
     */
    public static function getQuotaUsage()
    {
        $quota = $this->getQuota();
        if (is_array($quota)) {
            $quota = $quota['STORAGE']['usage'];
        }

        return $quota;
    }
}
