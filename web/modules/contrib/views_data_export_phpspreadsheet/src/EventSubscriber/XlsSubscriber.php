<?php

namespace Drupal\views_data_export_phpspreadsheet\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for adding xls content types to the request.
 */
class XlsSubscriber implements EventSubscriberInterface {

  /**
   * Register content type formats on the request object.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The Event to process.
   */
  public function onKernelRequest(RequestEvent $event) {
    $event->getRequest()->setFormat('ods', ['application/vnd.oasis.opendocument.spreadsheet']);
    $event->getRequest()->setFormat('xls', ['application/vnd.ms-excel']);
    $event->getRequest()->setFormat('xlsx', ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    $event->getRequest()->setFormat('xml', ['text/xml']);
    $event->getRequest()->setFormat('slk', ['application/sylk']);
    $event->getRequest()->setFormat('gnumeric', ['application/x-gnumeric']);
  }

  /**
   * Implements \Symfony\Component\EventDispatcher\EventSubscriberInterface::getSubscribedEvents().
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequest'];
    return $events;
  }

}
