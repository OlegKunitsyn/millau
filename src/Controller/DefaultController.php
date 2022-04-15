<?php

namespace App\Controller;

use App\Service\DnsService;
use App\Service\SendGridService;
use App\Service\TelegramService;
use App\Type\DnsType;
use App\Type\DomainType;
use App\Type\TelegramType;
use App\Type\TestEmailType;
use Exception;
use SendGrid\Mail\TypeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    /**
     * @throws TypeException
     */
    public function index(Request $request, SendGridService $sendGrid): Response
    {
        switch ($request->request->get('step')) {
            case TestEmailType::class:
                $form = $this->createForm(TestEmailType::class, $request->request->all());
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $sendGrid->sendText(
                        $form->get('to')->getData(),
                        $form->get('to')->getData(),
                        $form->get('subject')->getData(),
                        $form->get('message')->getData(),
                    );
                    $this->addFlash('success', 'Email sent. Check your Telegram group for messages... and try to reply!');
                }
                break;
            case DnsType::class:
                $form = $this->createForm(DnsType::class, $request->request->all());
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $sendGrid->setInbox($form->get('tld')->getData(), $form->get('group')->getData());
                    $form = $this->createForm(TestEmailType::class, array_merge($form->getData()));
                }
                break;
            case DomainType::class:
                $form = $this->createForm(DomainType::class, $request->request->all());
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $form = $this->createForm(DnsType::class, array_merge($form->getData()));
                }
                break;
            default:
                $form = $this->createForm(TelegramType::class);
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $model = [];
                    $form = $this->createForm(DomainType::class, array_merge($form->getData(), $model));
                }
                break;
        }

        return $this->render('default/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function inbox(Request $request, TelegramService $telegram, DnsService $dns): Response
    {
        try {
            $telegram->log(json_encode($request->request->all()));

            $email = SendGridService::parseInbound($request->request->all());
            foreach ($dns->getGroups($email->getTo()) as $group) {
                $telegram->createPostEmail($email, $group);
            }
            return new Response();
        } catch (Exception $e) {
            $telegram->log($e->getMessage() . ': ' . json_encode($request->request->all()));
            return new Response($e->getMessage(), Response::HTTP_OK);
        }
    }

    public function outbox(Request $request, TelegramService $telegram, SendGridService $sendGrid, DnsService $dns): Response
    {
        try {
            $message = json_decode($request->getContent(), true);

            if ($group = $telegram->parseCommandI($message)) {
                $telegram->createPostId($group);
            } elseif ($email = $telegram->parseCommandE($message)) {
                foreach ($dns->getGroups($email->getTo()) as $group) {
                    $telegram->createPostEmail($email, $group);
                }
            } elseif ($email = $telegram->parseReply($message)) {
                $sendGrid->sendEmail($email);
            }
            return new Response();
        } catch (Exception $e) {
            $telegram->log($e->getMessage() . ': ' . $request->getContent());
            return new Response($e->getMessage(), Response::HTTP_OK);
        }
    }
}
