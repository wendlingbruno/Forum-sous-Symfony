<?php

namespace App\Controller;

use App\Entity\Topic;
use App\Entity\Message;
use App\Form\MessageType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class MessageController extends AbstractController
{
    /**
     * @Route("/message", name="message")
     */
    public function index()
    {
        return $this->render('message/index.html.twig', [
            'controller_name' => 'MessageController',
        ]);
    }


    /**
     * @Route("/listMessage/{id}", name="listMessage", methods="GET")
     */
    public function listMessage(Topic $message = null): Response
    {

            if ($message){
                return $this->render('message/index.html.twig', ['topic' => $message]);
            }else{
                return $this->redirectToRoute('homepage');
            }
    }

    /**
     * @Route("/message/{id}/edit/{id2}", name="message_edit")
     * @ParamConverter("topic", options={"id" = "id"})
     * @ParamConverter("message", options={"id" = "id2"})
     * @Route("/message/add/{id}", name="message_add")
     * @isGranted("ROLE_USER")
     */
    public function addMessage(Topic $topic = null, Message $message = null, Request $request, ManagerRegistry $manager): Response{
        
        dump($request->getClientIp());
        $bool = false;
        if (!$message){
            $message = new Message();
            $bool = true;
        }

        if ($topic->getLocked() == 0){

            if (!$bool){
                if ($this->getUser()->getId() !== $message->getUser()->getId() && $this->getUser()->hasRole("ROLE_ADMIN") == false){
                    $this->addFlash("error", "Vous ne pouvez pas faire cette action.");
                    return $this->redirectToRoute('listMessage', ['id' => $topic->getId()]);
                }
            }
            $form = $this->createForm(MessageType::class, $message);

            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()){
                $message->setSujet($topic);
                if ($bool){
                    $message->setUser($this->getUser());
                }            
                $em = $manager->getManager();
                $em->persist($message);
                $em->flush();

                return $this->redirectToRoute('listMessage', ['id' => $topic->getId()]);
            }
            return $this->render('message/addedit.html.twig',
            [
                'formMessage' => $form->createView(),
                'editMode' => $message->getId() !== null,
                'topic' => $topic
            ]);
        }else{
            $this->addFlash("error", "Ce topic est verrouillé.");
            return $this->redirectToRoute('listMessage', ['id' => $topic->getId()]);
        }
    }

    /**
     * @Route("/message/{id}/delete", name="message_delete")
     * @isGranted("ROLE_USER")
     */
    public function deleteMessage(Message $message = null, ManagerRegistry $manager): Response{
        

        
        if (!$message){
            return $this->redirectToRoute('homepage');
        }else{
            $topic = $message->getSujet();
        } 
        if ($topic->getLocked() == 0){
            if ($this->getUser()->getId() === $message->getUser()->getId() || $this->getUser()->hasRole("ROLE_ADMIN")){
                $em = $manager->getManager();
                $em->remove($message);
                $em->flush();
            }
            else{
                $this->addFlash("error", "Vous ne pouvez pas faire cette action.");
            }

            return $this->redirectToRoute('listMessage', ['id' => $topic->getId()]);
        }else{
            $this->addFlash("error", "Ce topic est verrouillé.");
            return $this->redirectToRoute('listMessage', ['id' => $topic->getId()]);
        }
    }

}
