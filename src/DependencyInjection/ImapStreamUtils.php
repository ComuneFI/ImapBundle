<?php

namespace Fi\ImapBundle\DependencyInjection;

class ImapStreamUtils
{
    public static function initImapStream($imappath, $user, $password, $options)
    {
        $imapStream = imap_open($imappath, $user, $password, $options);
        //user=nomecasellaoffice365@comune.fi.it
        //$imapStream = imap_open('{outlook.office365.com:993/imap/ssl/authuser=d99999@comune.fi.it}', $this->login, $this->password, OP_READONLY);
        if (!$imapStream) {
            throw new ImapMailboxException('Connection error: '.imap_last_error());
        }

        return $imapStream;
    }

    public static function getImapStream($imappath, $user, $password, $options, $forceConnection = true)
    {
        static $imapStream;
        if ($forceConnection) {
            if ($imapStream && (!is_resource($imapStream) || !imap_ping($imapStream))) {
                $this->disconnect();
                $imapStream = null;
            }
            if (!$imapStream) {
                $imapStream = self::initImapStream($imappath, $user, $password, $options);
            }
        }

        return $imapStream;
    }
}
