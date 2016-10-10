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
                $mail->cc[strtolower($cc->mailbox.'@'.$cc->host)] = isset($cc->personal) ? ImapMailboxUtils::decodeMimeStr($cc->personal, $serverEncoding) : null;
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
                $mail->replyTo[strtolower($replyTo->mailbox.'@'.$replyTo->host)] = isset($replyTo->personal) ? ImapMailboxUtils::decodeMimeStr($replyTo->personal, $serverEncoding) : null;
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
        return $partStructure->ifid ? trim($partStructure->id, ' <>') : (isset($params['filename']) || isset($params['name']) ? mt_rand().mt_rand() : null);
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
}
