<?php

namespace App\Controller;

use App\Entity\Topic;
use App\Entity\Message;
use App\Form\TopicType;
use App\Entity\Categorie;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class TopicController extends AbstractController
{
    /**
     * @Route("/topic", name="topic")
     */
    public function index()
    {
        return $this->render('topic/index.html.twig', [
            'controller_name' => 'TopicController',
        ]);
    }

    /**
     * @Route("/listTopic/{id}", name="listTopic", methods="GET")
     */
    public function listTopic(Categorie $topic = null): Response
    {

        if ($topic){
            return $this->render('topic/index.html.twig', ['topic' => $topic]);
        }else{
            return $this->redirectToRoute('homepage');
        }
    }


            /**
     * @Route("/topic/add/{id}", name="topic_add")
     * @Route("/topic/{id}/edit", name="topic_edit")
     * @isGranted("ROLE_USER")
     */
    public function addTopic(Categorie $categorie = null, Topic $topic = null , Message $message = null, Request $request, ManagerRegistry $manager): Response{
        
        /*if (!$message){
            $message = new Message();
        }*/
        $topic = new Topic();
        $message = new Message();
        $form = $this->createForm(TopicType::class, $topic);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $topic->setCategorie($categorie);
            $topic->setUser($this->getUser());
            $em = $manager->getManager();
            $em->persist($topic);

            $message->setSujet($topic); // pour récupérer l'ID du topic
            $message->setUser($this->getUser());
            $message->setMessage($_POST['text']); // me permet de choper le texte du formulaire, vu que ici ça fonctionne un peu autrement que les autres car champ en +
            $em->persist($message);
            $em->flush();
            return $this->redirectToRoute('listMessage', ['id' => $topic->getId()]);
        }
        return $this->render('topic/addedit.html.twig',
        [
            'formTopic' => $form->createView(),
            'editMode' => $topic->getId() !== null,
            'categorie' => $categorie
        ]);
    }


     /**
     * @Route("/listTopic/{id}/delete/", name="topic_delete")
     * @isGranted("ROLE_USER")
     */
    public function deleteTopic(Topic $topic = null, ManagerRegistry $manager): Response{


        if (!$topic){
            return $this->redirectToRoute('homepage');
        }else{
            $categorie = $topic->getCategorie();
        } 
        if ($topic->getLocked() == 0){
            if ($this->getUser()->getId() === $topic->getUser()->getId() || $this->getUser()->hasRole("ROLE_ADMIN")){
                $em = $manager->getManager();
                $messages = $topic->getMessages();
                foreach ($messages as $message) {
                    $em->remove($message);
                }
                $em->remove($topic);
                $em->flush();
            }else{
                $this->addFlash("error", "Vous ne pouvez pas faire cette action.");
            }
                return $this->redirectToRoute('listTopic', ['id' => $categorie->getId()]);
        }else{
            $this->addFlash("error", "Ce topic est verrouillé.");
            return $this->redirectToRoute('listTopic', ['id' => $categorie->getId()]);
        }
    }

    /**
     * @Route("/listTopic/{id}/lock/", name="topic_lock")
     * @isGranted("ROLE_USER")
     */
    public function lockTopic(Topic $topic = null, ManagerRegistry $manager): Response{


        if (!$topic){
            return $this->redirectToRoute('homepage');
        }else{
            $categorie = $topic->getCategorie();
        }
         
        if ($this->getUser()->getId() === $topic->getUser()->getId() || $this->getUser()->hasRole("ROLE_ADMIN")){
            $em = $manager->getManager();
            if ($topic->getLocked()){
                $topic->setLocked(0);
            }else{
                $topic->setLocked(1);
            }
            $em->flush();
        }else{
            $this->addFlash("error", "Vous ne pouvez pas faire cette action.");
        }
            return $this->redirectToRoute('listMessage', ['id' => $topic->getId()]);
    }
    

}
