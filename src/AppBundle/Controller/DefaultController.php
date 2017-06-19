<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Website;
use AppBundle\Form\WebsiteType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
        $parameters = [];
        $em = $this->get('doctrine.orm.entity_manager');

        $website = new Website();
        $formWebsite = $this->createForm(WebsiteType::class, $website);
        $formWebsite->handleRequest($request);
        if ($formWebsite->isSubmitted() && $formWebsite->isValid()) {
            $website->setState(false);

            $em->persist($website);
            $em->flush();

            $request->getSession()
                ->getFlashBag()
                ->add('success', 'Website successuflly added')
            ;
        }
        $parameters = array_merge($parameters, [
            'formWebsite' => $formWebsite->createView()
        ]);

        $websites = $em->getRepository(Website::class)->findAll();
        $states  = [];
        foreach ($websites as $website) {
            if ($this->isDomainAvailible($website->getDomain())) {
                $website->setState(true);
            }
            else {
                $website->setState(false);
                $this->sendAlertMail($website->getDomain());
            }
            $states[$website->getReference()] = $website;
        }
        $parameters = array_merge($parameters, [
            'domains' => $states
        ]);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($states, 200);
        }

        return $this->render('@App/Default/index.html.twig', $parameters);
    }

    public function websiteDeleteAction($reference)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $website = $em->getRepository(Website::class)->findOneByReference($reference);

        if ($website) {
            $em->remove($website);
            $em->flush();
        }
        else {
            return new JsonResponse('ko', 200);
        }

        return new JsonResponse('ok', 200);
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
        $message = \Swift_Message::newInstance($domain . ' offline');
        $message
            ->setFrom('availability@mail.com')
            ->setTo($this->getParameter('alert_email'))
            ->setBody(
                $this->renderView(
                    '@App/Email/alert.html.twig',
                    ['domain' => $domain]
                ),
                'text/html'
            )
        ;

        $this->get('mailer')->send($message);
    }
}
