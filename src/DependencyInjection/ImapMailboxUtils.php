<?php

namespace Fi\ImapBundle\DependencyInjection;

class ImapMailboxUtils
{
    private $serverEncoding;

    public function __construct($serverEncoding)
    {
        $this->serverEncoding = $serverEncoding;
    }

    public function getMessageDate($head)
    {
        return date('Y-m-d H:i:s', isset($head->date) ? strtotime($head->date) : time());
    }

    public function getMessageFromAddress($head)
    {
        return strtolower($head->from[0]->mailbox.'@'.$head->from[0]->host);
    }

    public function getMessageFromName($head)
    {
        return isset($head->from[0]->personal) ? self::decodeMimeStr($head->from[0]->personal, $this->serverEncoding) : null;
    }

    public function getMessageSubject($head)
    {
        return isset($head->subject) ? self::decodeMimeStr($head->subject, $this->serverEncoding) : null;
    }

    public function getMessageCc($head, &$mail)
    {
        if (isset($head->cc)) {
            foreach ($head->cc as $cc) {
                $mail->cc[strtolower($cc->mailbox.'@'.$cc->host)] = isset($cc->personal) ? self::decodeMimeStr($cc->personal, $this->serverEncoding) : null;
            }
        }
    }

    public function getMessageTo($head, &$mail)
    {
        if (isset($head->to)) {
            $toStrings = array();
            foreach ($head->to as $to) {
                if (!empty($to->mailbox) && !empty($to->host)) {
                    $toEmail = strtolower($to->mailbox.'@'.$to->host);
                    $toName = isset($to->personal) ? self::decodeMimeStr($to->personal, $this->serverEncoding) : null;
                    $toStrings[] = $toName ? "$toName <$toEmail>" : $toEmail;
                    $mail->to[$toEmail] = $toName;
                }
            }
            $mail->toString = implode(', ', $toStrings);
        }
    }

    public function getMessageReplayTo($head, &$mail)
    {
        if (isset($head->reply_to)) {
            foreach ($head->reply_to as $replyTo) {
                $mail->replyTo[strtolower($replyTo->mailbox.'@'.$replyTo->host)] = isset($replyTo->personal) ? self::decodeMimeStr($replyTo->personal, $this->serverEncoding) : null;
            }
        }
    }

    public static function decodeMimeStr($string, $charset = 'utf-8')
    {
        $newString = '';
        $elements = imap_mime_header_decode($string);
        for ($i = 0; $i < count($elements); ++$i) {
            if ($elements[$i]->charset == 'default') {
                $elements[$i]->charset = 'iso-8859-1';
            }
            $newString .= iconv(strtoupper($elements[$i]->charset), $charset.'//IGNORE', $elements[$i]->text);
        }

        return $newString;
    }
}
