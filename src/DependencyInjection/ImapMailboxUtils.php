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

    public static function setMessageParameters(&$params, $partStructure)
    {
        if (!empty($partStructure->parameters)) {
            foreach ($partStructure->parameters as $param) {
                $params[strtolower($param->attribute)] = $param->value;
            }
        }
        if (!empty($partStructure->dparameters)) {
            foreach ($partStructure->dparameters as $param) {
                $paramName = strtolower(preg_match('~^(.*?)\*~', $param->attribute, $matches) ? $matches[1] : $param->attribute);
                if (isset($params[$paramName])) {
                    $params[$paramName] .= $param->value;
                } else {
                    $params[$paramName] = $param->value;
                }
            }
        }
    }

    public static function isUrlEncoded($string)
    {
        $hasInvalidChars = preg_match('#[^%a-zA-Z0-9\-_\.\+]#', $string);
        $hasEscapedChars = preg_match('#%[a-zA-Z0-9]{2}#', $string);

        return !$hasInvalidChars && $hasEscapedChars;
    }

    public static function getMailBody($partStructure, &$mail, $data)
    {
        if (strtolower($partStructure->subtype) == 'plain') {
            $mail->textPlain .= $data;
        } else {
            $mail->textHtml .= $data;
        }
    }

    public static function getAttachmentId($params, $partStructure)
    {
        return $partStructure->ifid ? trim($partStructure->id, ' <>') : (isset($params['filename']) || isset($params['name']) ? mt_rand().mt_rand() : null);
    }

    public static function decodeRFC2231($string, $charset = 'utf-8')
    {
        if (preg_match("/^(.*?)'.*?'(.*?)$/", $string, $matches)) {
            $encoding = $matches[1];
            $data = $matches[2];
            if (self::isUrlEncoded($data)) {
                $string = iconv(strtoupper($encoding), $charset.'//IGNORE', urldecode($data));
            }
        }

        return $string;
    }

    public function setMessageEncoding(&$data, $serverEncoding)
    {
        $tipoencoding = mb_detect_encoding($data, 'auto', true);
        ini_set('mbstring.substitute_character', 'none');
        if (($tipoencoding == false) or ($tipoencoding == '') or (!(isset($tipoencoding)))) {
            // Non si sa che encoding hanno i dati... tentiamo di impostargliene uno noi
            $newdata = mb_convert_encoding($data, 'UTF-8');
        } else {
            if (mb_detect_encoding($data) != 'UTF-8') {
                $newdata = mb_convert_encoding($data, 'UTF-8', $tipoencoding);
            } else {
                $newdata = $data;
            }
        }
        $data = $newdata;

        if (($tipoencoding == false) or ($tipoencoding == '') or (!(isset($tipoencoding)))) {
            $tipoencoding = mb_detect_encoding($data, 'auto', true);
        }

        if (($tipoencoding == false) or ($tipoencoding == '') or (!(isset($tipoencoding)))) {
            // Non si sa che encoding hanno i dati... che si fa?
        } else {
            $isreallythatencodingtype = mb_detect_encoding($data, $tipoencoding, true);
            if ($isreallythatencodingtype === false) {
                // che si fa?
            } else {
                $tipoencodingout = $serverEncoding.'//IGNORE//TRANSLIT';
                $convdata = @iconv($tipoencoding, $tipoencodingout, $data);
                $data = $convdata;
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
