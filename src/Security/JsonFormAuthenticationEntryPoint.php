<?php

namespace App\Security;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\FormAuthenticationEntryPoint;

/**
 * Class JsonFormAuthenticationEntryPoint
 *
 * @DI\Service("salt.authentication.json_form_entry_point", parent="security.authentication.form_entry_point.main")
 */
class JsonFormAuthenticationEntryPoint extends FormAuthenticationEntryPoint
{
    /**
     * Returns a response that directs the user to authenticate.
     *
     * This is called when an anonymous request accesses a resource that
     * requires authentication. The job of this method is to return some
     * response that "helps" the user start into the authentication process.
     *
     * @param Request $request The request that resulted in an AuthenticationException
     * @param AuthenticationException $authException The exception that started the authentication process
     *
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        if ($request->isXmlHttpRequest() || 'json' === $request->getRequestFormat()) {
            return new JsonResponse(
                [
                    'error' => [
                        'message' => 'Authentication Required',
                        'code' => 'AUTH-REQ',
                    ],
                ], 401);
        }

        return parent::start($request, $authException);
    }
}
