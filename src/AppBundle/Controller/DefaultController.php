<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    const DOMAINS = [
        'FRF' => 'http://www.france-fermetures.fr',
        'AUT' => 'https://www.autos.fr',
        'BSO' => 'http://bernard-solfin.fr'
    ];

    public function indexAction(Request $request)
    {
        $parameters = [];

        $states  = [];
        foreach (self::DOMAINS as $key => $domain) {
            if ($this->isDomainAvailible($domain)) {
                $states[$key] = [
                    'domain' => $domain,
                    'state' => 'on'
                ];
            }
            else {
                $states[$key] = [
                    'domain' => $domain,
                    'state' => 'off'
                ];
                $this->sendAlertMail($domain);
            }
        }
        $parameters = array_merge($parameters, [
            'states' => $states
        ]);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($states, 200);
        }

        return $this->render('@App/Default/index.html.twig', $parameters);
    }

    /**
     * Is domain available - returns true if domain is availible, false if not
     *
     * @param $domain
     * @return bool
     */
    protected function isDomainAvailible($domain)
    {
        //check, if a valid url is provided
        if (!filter_var($domain, FILTER_VALIDATE_URL)) {
            return false;
        }

        //initialize curl
        $curlInit = curl_init($domain);
        curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
        curl_setopt($curlInit,CURLOPT_HEADER,true);
        curl_setopt($curlInit,CURLOPT_NOBODY,true);
        curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);

        //get answer
        $response = curl_exec($curlInit);
        curl_close($curlInit);

        if ($response) return true;

        return false;
    }

    protected function sendAlertMail($domain) {
        $message = new \Swift_Message('Hello Email');
        $message
            ->setFrom('availability@mail.com')
            ->setTo($this->getParameter('alert_email'))
            ->setBody(
                $this->renderView(
                // app/Resources/views/Emails/registration.html.twig
                    '@App/Email/alert.html.twig',
                    ['domain' => $domain]
                ),
                'text/html'
            )
        ;

        $mailer = new \Swift_Mailer($this->get('swiftmailer.transport'));
        try {
            $mailer->send($message);
        } catch (\Exception $e) {
            dump($e);
        }
    }
}
