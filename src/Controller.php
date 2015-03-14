<?php
namespace Songbird;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\Exception\ReflectionException;
use Songbird\Event\EventAwareInterface;
use Songbird\Event\EventAwareTrait;
use Songbird\Log\LoggerAwareInterface;
use Songbird\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Controller implements ContainerAwareInterface, LoggerAwareInterface, EventAwareInterface
{
    use ContainerAwareTrait, ContainerResolverTrait, LoggerAwareTrait, EventAwareTrait;

    /**
     * @param \Symfony\Component\HttpFoundation\Request  $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param array                                      $args
     *
     * @return mixed
     *
     */
    public function handle(Request $request, Response $response, array $args)
    {
        $document = $this->getDocument($args['documentId']);

        $this->emit('PrepareDocument', ['document' => &$document]);

        // Fire any events for the current document.
        $this->handleListeners($request, $response, $document);

        // We can assume that if the method is GET we want to set content body.
        if ($request->isMethod('get')) {
            try {
                $content = $this->resolve('Template')->render($document['_template'], $document);
            } catch (ReflectionException $e) {
                $content = $document['body'];
            }

            $response->setContent($content);
        }

        return $response;
    }

    /**
     * @param string $documentId
     *
     * @return mixed
     */
    protected function getDocument($documentId)
    {
        return $this->resolve('Document.Repository')->find($documentId ? $documentId : 'home');
    }

    /**
     * Register any listeners discovered for the current page and add them to the Event Emitter.
     *
     * @param \Symfony\Component\HttpFoundation\Request  $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param \Songbird\Document\DocumentInterface       $document
     */
    protected function handleListeners(Request $request, Response $response, $document)
    {
        $verb = strtolower($request->getMethod());

        if (isset($document['_listen'][$verb])) {
            $listeners = $document['_listen'][$verb];
        }

        if (isset($document['_listen']['all'])) {
            $listeners = $document['_listen']['all'];
        } else {
            $listeners = [];
        }

        if (!is_array($listeners)) {
            $listeners = [$listeners];
        }

        foreach ($listeners as $listener) {
            $this->addListener($listener,
                $this->resolve($listener, [$request, $response]));

            $this->emit($listener);
        }
    }
}
