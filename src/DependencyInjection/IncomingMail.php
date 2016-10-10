<?php

namespace Fi\ImapBundle\DependencyInjection;

class IncomingMail
{
    public $id;
    public $date;
    public $subject;
    public $fromName;
    public $fromAddress;
    public $to = array();
    public $toString;
    public $cc = array();
    public $replyTo = array();
    public $textPlain;
    public $textHtml;

    /** @var IncomingMailAttachment[] */
    protected $attachments = array();

    public function addAttachment(IncomingMailAttachment $attachment)
    {
        $this->attachments[$attachment->id] = $attachment;
    }

    /**
     * @return IncomingMailAttachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /*
     * Get array of internal HTML links placeholders
     * @return array attachmentId => link placeholder
     */
    /*

      public function getInternalLinksPlaceholders() {
      return preg_match_all('/=["\'](ci?d:(\w+))["\']/i', $this->textHtml, $matches) ? array_combine($matches[2], $matches[1]) : array();
      }

     * ****** Se dovesse servire questa funzione ricordarsi che non esiste più "filePath" *******

      public function replaceInternalLinks($baseUri) {
      $baseUri = rtrim($baseUri, '\\/') . '/';
      $fetchedHtml = $this->textHtml;
      foreach ($this->getInternalLinksPlaceholders() as $attachmentId => $placeholder) {
      $fetchedHtml = str_replace($placeholder, $baseUri . basename($this->attachments[$attachmentId]->filePath), $fetchedHtml);
      }
      return $fetchedHtml;
      } */
}
