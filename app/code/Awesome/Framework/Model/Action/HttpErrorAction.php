<?php
declare(strict_types=1);

namespace Awesome\Framework\Model\Action;

use Awesome\Framework\Model\Http\Response;
use Awesome\Framework\Model\Http\Request;

/**
 * Class HttpErrorAction
 * @method string|null getAcceptType()
 * @method \Exception getError()
 * @method bool getIsDeveloperMode()
 */
class HttpErrorAction extends \Awesome\Framework\Model\DataObject
{
    private const INTERNALERROR_PAGE_PATH = '/pub/pages/internal_error.html';

    /**
     * Show internal error response according to accept type.
     * @return Response
     */
    public function execute(): Response
    {
        $e = $this->getError();

        if ($this->getAcceptType() === Request::JSON_ACCEPT_HEADER) {
            $response = new Response(
                json_encode([
                    'status' => 'ERROR',
                    'message' => $this->getIsDeveloperMode()
                        ? get_class_name($e) . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString()
                        : 'Internal error occurred. Details are hidden and can be found in logs files.',
                ]),
                Response::INTERNAL_ERROR_STATUS_CODE,
                ['Content-Type' => 'application/json']
            );
        } elseif ($this->getAcceptType() === Request::HTML_ACCEPT_HEADER && $content = $this->getInternalErrorPage()) {
            $response = new Response(
                $this->getIsDeveloperMode()
                    ? '<pre>' . get_class_name($e) . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString() . '</pre>'
                    : $content,
                Response::INTERNAL_ERROR_STATUS_CODE,
                ['Content-Type' => 'text/html']
            );
        } else {
            $response = new Response('', Response::INTERNAL_ERROR_STATUS_CODE);
        }

        return $response;
    }

    /**
     * Get internal error page content.
     * @return string|null
     */
    private function getInternalErrorPage(): ?string
    {
        return @file_get_contents(BP . self::INTERNALERROR_PAGE_PATH) ?: null;
    }
}