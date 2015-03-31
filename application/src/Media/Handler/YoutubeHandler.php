<?php
namespace Omeka\Media\Handler;

use Omeka\Api\Representation\Entity\MediaRepresentation;
use Omeka\Api\Request;
use Omeka\Media\Handler\AbstractHandler;
use Omeka\Model\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class YoutubeHandler extends AbstractHandler
{
    const WIDTH = 420;
    const HEIGHT = 315;
    const ALLOWFULLSCREEN = true;

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();

        if (!isset($data['o:source'])) {
            $errorStore->addError('o:source', 'No YouTube URL specified');
            return;
        }

        $uri = new HttpUri($data['o:source']);
        if (!($uri->isValid() && $uri->isAbsolute())) {
            $errorStore->addError('o:source', 'Invalid YouTube URL specified');
            return;
        }

        if ('www.youtube.com' !== $uri->getHost()) {
            $errorStore->addError('o:source', 'Invalid YouTube URL specified, not a YouTube URL');
            return;
        }

        if ('/watch' !== $uri->getPath()) {
            $errorStore->addError('o:source', 'Invalid YouTube URL specified, missing "/watch" path');
            return;
        }

        $query = $uri->getQueryAsArray();
        if (!isset($query['v'])) {
            $errorStore->addError('o:source', 'Invalid YouTube URL specified, missing "v" parameter');
            return;
        }

        $request->setMetadata('youtubeId', $query['v']);
    }

    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $id = $request->getMetadata('youtubeId');

        $file = $this->getServiceLocator()->get('Omeka\TempFile');
        $url = sprintf('http://img.youtube.com/vi/%s/0.jpg', $id);
        $this->downloadFile($url, $file->getTempPath());
        $hasThumbnails = $file->storeThumbnails();

        $media->setData(array('id' => $id));
        $media->setFilename($file->getStorageName());
        $media->setMediaType($file->getMediaType());
        $media->setHasThumbnails($hasThumbnails);
        $media->setHasOriginal(false);
    }

    public function form(PhpRenderer $view, array $options = array())
    {}

    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = array()
    ) {
        if (!isset($options['width'])) {
            $options['width'] = self::WIDTH;
        }
        if (!isset($options['height'])) {
            $options['height'] = self::HEIGHT;
        }
        if (!isset($options['allowfullscreen'])) {
            $options['allowfullscreen'] = self::ALLOWFULLSCREEN;
        }

        // Compose the YouTube embed URL and build the markup.
        $data = $media->mediaData();
        $url = sprintf('https://www.youtube.com/embed/%s', $data['id']);
        $embed = sprintf(
            '<iframe width="%s" height="%s" src="%s" frameborder="0"%s></iframe>',
            $view->escapeHtmlAttr($options['width']),
            $view->escapeHtmlAttr($options['height']),
            $view->escapeHtmlAttr($url),
            $options['allowfullscreen'] ? ' allowfullscreen' : ''
        );
        return $embed;
    }
}